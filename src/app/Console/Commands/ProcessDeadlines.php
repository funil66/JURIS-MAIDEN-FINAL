<?php

namespace App\Console\Commands;

use App\Models\Deadline;
use App\Models\DeadlineAlert;
use App\Models\Holiday;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class ProcessDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deadlines:process 
                            {--check-missed : Verificar e marcar prazos perdidos}
                            {--send-alerts : Enviar alertas de prazos próximos}
                            {--recalculate : Recalcular datas considerando feriados}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processa prazos: verifica perdidos, envia alertas e recalcula datas';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('⏰ Processamento de Prazos');
        $this->newLine();

        $checkMissed = $this->option('check-missed');
        $sendAlerts = $this->option('send-alerts');
        $recalculate = $this->option('recalculate');

        // Se nenhuma opção especificada, executar todas
        if (!$checkMissed && !$sendAlerts && !$recalculate) {
            $checkMissed = true;
            $sendAlerts = true;
        }

        $results = [
            'missed' => 0,
            'alerts_sent' => 0,
            'recalculated' => 0,
        ];

        try {
            if ($checkMissed) {
                $results['missed'] = $this->checkMissedDeadlines();
            }

            if ($sendAlerts) {
                $results['alerts_sent'] = $this->sendDeadlineAlerts();
            }

            if ($recalculate) {
                $results['recalculated'] = $this->recalculateDeadlines();
            }

            $this->displayResults($results);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Erro: {$e->getMessage()}");
            Log::error('ProcessDeadlines: Erro', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Verificar e marcar prazos perdidos
     */
    protected function checkMissedDeadlines(): int
    {
        $this->info('🔍 Verificando prazos perdidos...');

        $missedDeadlines = Deadline::query()
            ->whereIn('status', [Deadline::STATUS_PENDING, Deadline::STATUS_IN_PROGRESS])
            ->where('due_date', '<', now()->startOfDay())
            ->get();

        $count = 0;

        foreach ($missedDeadlines as $deadline) {
            $deadline->update([
                'status' => Deadline::STATUS_MISSED,
            ]);

            // Registrar alerta de prazo perdido
            DeadlineAlert::create([
                'deadline_id' => $deadline->id,
                'alert_type' => 'missed',
                'alert_date' => now(),
                'sent_at' => now(),
                'sent_via' => 'system',
                'recipient_user_id' => $deadline->assigned_user_id,
            ]);

            $count++;

            Log::warning('ProcessDeadlines: Prazo marcado como perdido', [
                'deadline_id' => $deadline->id,
                'uid' => $deadline->uid,
                'due_date' => $deadline->due_date->format('Y-m-d'),
            ]);
        }

        $this->line("   ✓ {$count} prazo(s) marcado(s) como perdido(s)");

        return $count;
    }

    /**
     * Enviar alertas de prazos próximos
     */
    protected function sendDeadlineAlerts(): int
    {
        $this->info('📧 Enviando alertas de prazos...');

        $alertsSent = 0;

        // Buscar prazos que precisam de alerta
        $deadlines = Deadline::query()
            ->whereIn('status', [Deadline::STATUS_PENDING, Deadline::STATUS_IN_PROGRESS])
            ->where('due_date', '>=', now()->startOfDay())
            ->where('due_date', '<=', now()->addDays(7))
            ->with(['assignedUser', 'process', 'deadlineType'])
            ->get();

        foreach ($deadlines as $deadline) {
            $daysRemaining = now()->startOfDay()->diffInDays($deadline->due_date, false);
            
            // Determinar tipo de alerta baseado nos dias restantes
            $alertType = match (true) {
                $daysRemaining <= 0 => 'today',
                $daysRemaining === 1 => 'tomorrow',
                $daysRemaining <= 3 => '3_days',
                $daysRemaining <= 7 => '7_days',
                default => null,
            };

            if (!$alertType) {
                continue;
            }

            // Verificar se alerta já foi enviado hoje para este tipo
            $alreadySent = DeadlineAlert::query()
                ->where('deadline_id', $deadline->id)
                ->where('alert_type', $alertType)
                ->whereDate('alert_date', today())
                ->exists();

            if ($alreadySent) {
                continue;
            }

            // Criar registro do alerta
            DeadlineAlert::create([
                'deadline_id' => $deadline->id,
                'alert_type' => $alertType,
                'alert_date' => now(),
                'sent_at' => now(),
                'sent_via' => 'email',
                'recipient_user_id' => $deadline->assigned_user_id,
            ]);

            // Atualizar registro de alertas enviados no prazo
            $alertsSentArray = $deadline->alerts_sent ?? [];
            $alertsSentArray[] = [
                'type' => $alertType,
                'sent_at' => now()->toIso8601String(),
            ];
            $deadline->update(['alerts_sent' => $alertsSentArray]);

            $alertsSent++;

            Log::info('ProcessDeadlines: Alerta enviado', [
                'deadline_id' => $deadline->id,
                'alert_type' => $alertType,
                'days_remaining' => $daysRemaining,
            ]);
        }

        $this->line("   ✓ {$alertsSent} alerta(s) enviado(s)");

        return $alertsSent;
    }

    /**
     * Recalcular datas de prazos considerando feriados
     */
    protected function recalculateDeadlines(): int
    {
        $this->info('📅 Recalculando prazos...');

        $deadlines = Deadline::query()
            ->whereIn('status', [Deadline::STATUS_PENDING, Deadline::STATUS_IN_PROGRESS])
            ->where('counting_type', Deadline::COUNTING_BUSINESS_DAYS)
            ->where('due_date', '>=', now()->startOfDay())
            ->get();

        $count = 0;

        foreach ($deadlines as $deadline) {
            $originalDueDate = $deadline->due_date;
            $newDueDate = $this->calculateBusinessDayDeadline(
                $deadline->start_date,
                $deadline->days_count,
                $deadline->process?->court_state
            );

            if ($newDueDate && !$newDueDate->equalTo($originalDueDate)) {
                $deadline->update([
                    'due_date' => $newDueDate,
                    'original_due_date' => $deadline->original_due_date ?? $originalDueDate,
                ]);

                $count++;

                Log::info('ProcessDeadlines: Prazo recalculado', [
                    'deadline_id' => $deadline->id,
                    'original' => $originalDueDate->format('Y-m-d'),
                    'new' => $newDueDate->format('Y-m-d'),
                ]);
            }
        }

        $this->line("   ✓ {$count} prazo(s) recalculado(s)");

        return $count;
    }

    /**
     * Calcular data final considerando dias úteis
     */
    protected function calculateBusinessDayDeadline(
        Carbon $startDate,
        int $days,
        ?string $state = null
    ): Carbon {
        $currentDate = $startDate->copy();
        $daysAdded = 0;

        while ($daysAdded < $days) {
            $currentDate->addDay();

            // Verificar se é dia útil
            if ($this->isBusinessDay($currentDate, $state)) {
                $daysAdded++;
            }
        }

        // Se cair em dia não útil, avançar para próximo dia útil
        while (!$this->isBusinessDay($currentDate, $state)) {
            $currentDate->addDay();
        }

        return $currentDate;
    }

    /**
     * Verificar se é dia útil
     */
    protected function isBusinessDay(Carbon $date, ?string $state = null): bool
    {
        // Verificar se é fim de semana
        if ($date->isWeekend()) {
            return false;
        }

        // Verificar se é feriado
        $isHoliday = Holiday::query()
            ->where('date', $date->format('Y-m-d'))
            ->where('is_active', true)
            ->where(function ($query) use ($state) {
                $query->where('scope', 'national')
                    ->orWhere(function ($q) use ($state) {
                        $q->where('scope', 'state')
                            ->where('state', $state);
                    });
            })
            ->exists();

        return !$isHoliday;
    }

    /**
     * Exibir resultados
     */
    protected function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('📊 Resumo:');
        
        if ($results['missed'] > 0) {
            $this->warn("   ⚠ Prazos perdidos: {$results['missed']}");
        }
        
        if ($results['alerts_sent'] > 0) {
            $this->line("   📧 Alertas enviados: {$results['alerts_sent']}");
        }
        
        if ($results['recalculated'] > 0) {
            $this->line("   📅 Prazos recalculados: {$results['recalculated']}");
        }

        if (array_sum($results) === 0) {
            $this->line("   ✓ Nenhuma ação necessária");
        }
    }
}
