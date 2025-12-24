<?php

namespace App\Console\Commands;

use App\Models\Court;
use App\Models\CourtSyncSchedule;
use App\Services\CourtApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncCourtMovements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'courts:sync 
                            {--court= : ID ou UID do tribunal específico}
                            {--all : Sincronizar todos os tribunais ativos}
                            {--scheduled : Executar apenas agendamentos pendentes}
                            {--process= : Sincronizar um processo específico}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza movimentações processuais com APIs dos tribunais';

    /**
     * Execute the console command.
     */
    public function handle(CourtApiService $service): int
    {
        $this->info('🏛️  Sincronização de Movimentações dos Tribunais');
        $this->newLine();

        try {
            if ($this->option('scheduled')) {
                return $this->runScheduled($service);
            }

            if ($courtOption = $this->option('court')) {
                return $this->syncSpecificCourt($service, $courtOption);
            }

            if ($this->option('all')) {
                return $this->syncAllCourts($service);
            }

            // Padrão: executar agendamentos pendentes
            return $this->runScheduled($service);

        } catch (\Exception $e) {
            $this->error("❌ Erro fatal: {$e->getMessage()}");
            Log::error('SyncCourtMovements: Erro fatal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Executar agendamentos pendentes
     */
    protected function runScheduled(CourtApiService $service): int
    {
        $this->info('⏰ Executando agendamentos pendentes...');

        $schedules = CourtSyncSchedule::query()
            ->active()
            ->readyToRun()
            ->with('court')
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('Nenhum agendamento pendente.');
            return Command::SUCCESS;
        }

        $this->info("Encontrados {$schedules->count()} agendamento(s) para executar.");
        $this->newLine();

        $results = $service->runPendingSchedules();

        $this->displayResults($results);

        return Command::SUCCESS;
    }

    /**
     * Sincronizar tribunal específico
     */
    protected function syncSpecificCourt(CourtApiService $service, string $courtIdentifier): int
    {
        // Buscar por ID ou UID
        $court = Court::query()
            ->where('id', $courtIdentifier)
            ->orWhere('uid', $courtIdentifier)
            ->orWhere('acronym', $courtIdentifier)
            ->first();

        if (!$court) {
            $this->error("❌ Tribunal não encontrado: {$courtIdentifier}");
            return Command::FAILURE;
        }

        if (!$court->is_active) {
            $this->warn("⚠️  Tribunal {$court->acronym} está inativo.");
            
            if (!$this->confirm('Deseja sincronizar mesmo assim?')) {
                return Command::SUCCESS;
            }
        }

        if (!$court->isApiConfigured()) {
            $this->error("❌ Tribunal {$court->acronym} não está configurado com credenciais de API.");
            return Command::FAILURE;
        }

        $this->info("🔄 Sincronizando {$court->display_name}...");

        if ($processNumber = $this->option('process')) {
            // Sincronizar processo específico
            $this->info("Processo: {$processNumber}");
            
            $result = $service->queryProcessMovements($court, $processNumber);

            if ($result['success']) {
                $this->info("✓ {$result['query']->results_count} movimentação(ões) encontrada(s)");
            } else {
                $this->error("✗ Erro: {$result['message']}");
                return Command::FAILURE;
            }
        } else {
            // Sincronizar todos os processos
            $syncLog = $service->syncAllActiveProcesses($court);

            $this->displaySyncLog($syncLog);
        }

        return Command::SUCCESS;
    }

    /**
     * Sincronizar todos os tribunais ativos
     */
    protected function syncAllCourts(CourtApiService $service): int
    {
        $courts = Court::active()
            ->configured()
            ->get();

        if ($courts->isEmpty()) {
            $this->warn('Nenhum tribunal ativo e configurado encontrado.');
            return Command::SUCCESS;
        }

        $this->info("🔄 Sincronizando {$courts->count()} tribunal(is)...");
        $this->newLine();

        $bar = $this->output->createProgressBar($courts->count());
        $bar->start();

        $results = [];

        foreach ($courts as $court) {
            try {
                $syncLog = $service->syncAllActiveProcesses($court);
                
                $results[] = [
                    'court' => $court->acronym,
                    'status' => $syncLog->status,
                    'new' => $syncLog->movements_new,
                    'errors' => $syncLog->errors_count,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'court' => $court->acronym,
                    'status' => 'error',
                    'error' => $e->getMessage(),
                ];
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->displayTableResults($results);

        return Command::SUCCESS;
    }

    /**
     * Exibir resultados formatados
     */
    protected function displayResults(array $results): void
    {
        if (empty($results)) {
            return;
        }

        $this->table(
            ['Tribunal', 'Status', 'Novas Mov.', 'Observação'],
            collect($results)->map(fn ($r) => [
                $r['court'],
                $r['status'],
                $r['movements_new'] ?? '-',
                $r['error'] ?? '-',
            ])->toArray()
        );

        $this->newLine();

        $success = collect($results)->where('status', 'success')->count();
        $partial = collect($results)->where('status', 'partial')->count();
        $errors = collect($results)->where('status', 'error')->count();
        $totalNew = collect($results)->sum('movements_new');

        $this->info("📊 Resumo:");
        $this->line("   ✓ Sucesso: {$success}");
        if ($partial > 0) $this->line("   ⚠ Parcial: {$partial}");
        if ($errors > 0) $this->line("   ✗ Erros: {$errors}");
        $this->line("   📥 Novas movimentações: {$totalNew}");
    }

    /**
     * Exibir resultados em tabela
     */
    protected function displayTableResults(array $results): void
    {
        $this->table(
            ['Tribunal', 'Status', 'Novas', 'Erros'],
            collect($results)->map(fn ($r) => [
                $r['court'],
                match ($r['status']) {
                    'success' => '✓ Sucesso',
                    'partial' => '⚠ Parcial',
                    'error' => '✗ Erro',
                    default => $r['status'],
                },
                $r['new'] ?? '-',
                $r['errors'] ?? ($r['error'] ?? '-'),
            ])->toArray()
        );

        $this->newLine();

        $success = collect($results)->where('status', 'success')->count();
        $totalNew = collect($results)->sum('new');
        $totalErrors = collect($results)->where('status', 'error')->count();

        $this->info("📊 Resumo: {$success} sucesso(s), {$totalNew} nova(s) movimentação(ões), {$totalErrors} erro(s)");
    }

    /**
     * Exibir log de sincronização
     */
    protected function displaySyncLog($syncLog): void
    {
        $this->newLine();

        $statusIcon = match ($syncLog->status) {
            'success' => '✓',
            'partial' => '⚠',
            'error' => '✗',
            default => '?',
        };

        $this->info("{$statusIcon} Status: {$syncLog->status_label}");
        $this->line("   Processos consultados: {$syncLog->processes_queried}");
        $this->line("   Movimentações encontradas: {$syncLog->movements_found}");
        $this->line("   Novas movimentações: {$syncLog->movements_new}");
        $this->line("   Importadas: {$syncLog->movements_imported}");
        
        if ($syncLog->errors_count > 0) {
            $this->warn("   Erros: {$syncLog->errors_count}");
        }

        $this->line("   Duração: {$syncLog->duration_formatted}");

        if ($syncLog->error_message) {
            $this->error("   Erro: {$syncLog->error_message}");
        }
    }
}
