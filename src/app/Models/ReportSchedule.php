<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ReportSchedule extends Model
{
    use SoftDeletes, HasGlobalUid;

    protected $fillable = [
        'uid',
        'user_id',
        'report_template_id',
        'name',
        'frequency',
        'day_of_week',
        'day_of_month',
        'scheduled_time',
        'recipients',
        'format',
        'period',
        'is_active',
        'last_run_at',
        'next_run_at',
        'run_count',
        'failure_count',
        'last_error',
    ];

    protected $casts = [
        'recipients' => 'array',
        'scheduled_time' => 'datetime:H:i:s',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public static function getUidPrefix(): string
    {
        return 'RSC';
    }

    // === CONSTANTES ===
    
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_BIWEEKLY = 'biweekly';
    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_QUARTERLY = 'quarterly';

    public const PERIOD_YESTERDAY = 'yesterday';
    public const PERIOD_LAST_7_DAYS = 'last_7_days';
    public const PERIOD_LAST_30_DAYS = 'last_30_days';
    public const PERIOD_LAST_MONTH = 'last_month';
    public const PERIOD_CURRENT_MONTH = 'current_month';
    public const PERIOD_LAST_QUARTER = 'last_quarter';
    public const PERIOD_CURRENT_YEAR = 'current_year';

    // === RELATIONSHIPS ===

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }

    // === ACCESSORS ===

    public function getFrequencyNameAttribute(): string
    {
        return self::getFrequencyOptions()[$this->frequency] ?? $this->frequency;
    }

    public function getPeriodNameAttribute(): string
    {
        return self::getPeriodOptions()[$this->period] ?? $this->period;
    }

    public function getNextRunDescriptionAttribute(): string
    {
        if (!$this->next_run_at) {
            return 'Não agendado';
        }

        if ($this->next_run_at->isToday()) {
            return 'Hoje às ' . $this->next_run_at->format('H:i');
        }

        if ($this->next_run_at->isTomorrow()) {
            return 'Amanhã às ' . $this->next_run_at->format('H:i');
        }

        return $this->next_run_at->format('d/m/Y H:i');
    }

    public function getRecipientsListAttribute(): string
    {
        if (empty($this->recipients)) {
            return '-';
        }

        $recipients = is_array($this->recipients) ? $this->recipients : [$this->recipients];
        
        if (count($recipients) === 1) {
            return $recipients[0];
        }

        return $recipients[0] . ' +' . (count($recipients) - 1);
    }

    // === STATIC METHODS ===

    public static function getFrequencyOptions(): array
    {
        return [
            self::FREQUENCY_DAILY => 'Diário',
            self::FREQUENCY_WEEKLY => 'Semanal',
            self::FREQUENCY_BIWEEKLY => 'Quinzenal',
            self::FREQUENCY_MONTHLY => 'Mensal',
            self::FREQUENCY_QUARTERLY => 'Trimestral',
        ];
    }

    public static function getPeriodOptions(): array
    {
        return [
            self::PERIOD_YESTERDAY => 'Ontem',
            self::PERIOD_LAST_7_DAYS => 'Últimos 7 dias',
            self::PERIOD_LAST_30_DAYS => 'Últimos 30 dias',
            self::PERIOD_LAST_MONTH => 'Mês anterior',
            self::PERIOD_CURRENT_MONTH => 'Mês atual',
            self::PERIOD_LAST_QUARTER => 'Trimestre anterior',
            self::PERIOD_CURRENT_YEAR => 'Ano atual',
        ];
    }

    public static function getDayOfWeekOptions(): array
    {
        return [
            0 => 'Domingo',
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
        ];
    }

    // === METHODS ===

    /**
     * Calcula a próxima data de execução
     */
    public function calculateNextRun(): ?Carbon
    {
        $now = now();
        $time = Carbon::parse($this->scheduled_time ?? '08:00:00');
        
        $next = match($this->frequency) {
            self::FREQUENCY_DAILY => $this->getNextDaily($now, $time),
            self::FREQUENCY_WEEKLY => $this->getNextWeekly($now, $time),
            self::FREQUENCY_BIWEEKLY => $this->getNextBiweekly($now, $time),
            self::FREQUENCY_MONTHLY => $this->getNextMonthly($now, $time),
            self::FREQUENCY_QUARTERLY => $this->getNextQuarterly($now, $time),
            default => null,
        };

        return $next;
    }

    protected function getNextDaily(Carbon $now, Carbon $time): Carbon
    {
        $next = $now->copy()->setTimeFrom($time);
        
        if ($next->lte($now)) {
            $next->addDay();
        }

        return $next;
    }

    protected function getNextWeekly(Carbon $now, Carbon $time): Carbon
    {
        $dayOfWeek = $this->day_of_week ?? 1; // Default: Segunda
        $next = $now->copy()->next($dayOfWeek)->setTimeFrom($time);
        
        // Se hoje é o dia e ainda não passou o horário
        if ($now->dayOfWeek === $dayOfWeek && $now->copy()->setTimeFrom($time)->gt($now)) {
            $next = $now->copy()->setTimeFrom($time);
        }

        return $next;
    }

    protected function getNextBiweekly(Carbon $now, Carbon $time): Carbon
    {
        $dayOfWeek = $this->day_of_week ?? 1;
        $next = $this->getNextWeekly($now, $time);
        
        // A cada duas semanas a partir da última execução
        if ($this->last_run_at) {
            $weeksSince = $this->last_run_at->diffInWeeks($now);
            if ($weeksSince < 2) {
                $next = $this->last_run_at->copy()->addWeeks(2)->setTimeFrom($time);
            }
        }

        return $next;
    }

    protected function getNextMonthly(Carbon $now, Carbon $time): Carbon
    {
        $dayOfMonth = $this->day_of_month ?? 1;
        $next = $now->copy()->startOfMonth()->addDays($dayOfMonth - 1)->setTimeFrom($time);
        
        if ($next->lte($now)) {
            $next->addMonth();
        }

        // Ajusta para último dia do mês se necessário
        if ($dayOfMonth > $next->daysInMonth) {
            $next->endOfMonth()->setTimeFrom($time);
        }

        return $next;
    }

    protected function getNextQuarterly(Carbon $now, Carbon $time): Carbon
    {
        $dayOfMonth = $this->day_of_month ?? 1;
        $quarter = $now->quarter;
        $next = $now->copy()->startOfQuarter()->addMonths(3)->addDays($dayOfMonth - 1)->setTimeFrom($time);
        
        // Se o próximo trimestre já passou
        if ($next->lte($now)) {
            $next->addMonths(3);
        }

        return $next;
    }

    /**
     * Obtém o período de datas baseado na configuração
     */
    public function getPeriodDates(): array
    {
        $now = now();

        return match($this->period) {
            self::PERIOD_YESTERDAY => [
                'from' => $now->copy()->subDay()->startOfDay(),
                'to' => $now->copy()->subDay()->endOfDay(),
            ],
            self::PERIOD_LAST_7_DAYS => [
                'from' => $now->copy()->subDays(7)->startOfDay(),
                'to' => $now->copy()->subDay()->endOfDay(),
            ],
            self::PERIOD_LAST_30_DAYS => [
                'from' => $now->copy()->subDays(30)->startOfDay(),
                'to' => $now->copy()->subDay()->endOfDay(),
            ],
            self::PERIOD_LAST_MONTH => [
                'from' => $now->copy()->subMonth()->startOfMonth(),
                'to' => $now->copy()->subMonth()->endOfMonth(),
            ],
            self::PERIOD_CURRENT_MONTH => [
                'from' => $now->copy()->startOfMonth(),
                'to' => $now->copy()->endOfMonth(),
            ],
            self::PERIOD_LAST_QUARTER => [
                'from' => $now->copy()->subQuarter()->startOfQuarter(),
                'to' => $now->copy()->subQuarter()->endOfQuarter(),
            ],
            self::PERIOD_CURRENT_YEAR => [
                'from' => $now->copy()->startOfYear(),
                'to' => $now->copy()->endOfYear(),
            ],
            default => [
                'from' => $now->copy()->subDays(30),
                'to' => $now->copy(),
            ],
        };
    }

    /**
     * Atualiza próxima execução
     */
    public function updateNextRun(): void
    {
        $this->update(['next_run_at' => $this->calculateNextRun()]);
    }

    /**
     * Marca como executado
     */
    public function markAsRun(bool $success = true, ?string $error = null): void
    {
        $data = [
            'last_run_at' => now(),
            'run_count' => $this->run_count + 1,
        ];

        if (!$success) {
            $data['failure_count'] = $this->failure_count + 1;
            $data['last_error'] = $error;
        } else {
            $data['last_error'] = null;
        }

        $this->update($data);
        $this->updateNextRun();
    }

    /**
     * Ativa/Desativa o agendamento
     */
    public function toggle(): void
    {
        $this->update(['is_active' => !$this->is_active]);
        
        if ($this->is_active) {
            $this->updateNextRun();
        }
    }

    // === SCOPES ===

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->active()
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now());
    }
}
