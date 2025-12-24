<?php

namespace App\Models;

use App\Models\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Deadline extends Model
{
    use HasFactory, SoftDeletes, HasGlobalUid;

    protected $fillable = [
        'uid',
        'process_id',
        'proceeding_id',
        'deadline_type_id',
        'start_date',
        'due_date',
        'original_due_date',
        'completed_at',
        'title',
        'description',
        'days_count',
        'counting_type',
        'status',
        'priority',
        'assigned_user_id',
        'created_by_user_id',
        'alerts_sent',
        'completion_notes',
        'document_protocol',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'original_due_date' => 'date',
        'completed_at' => 'datetime',
        'days_count' => 'integer',
        'alerts_sent' => 'array',
    ];

    // UID Prefix
    public static function getUidPrefix(): string
    {
        return 'PRZ';
    }

    // Status Constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXTENDED = 'extended';
    public const STATUS_MISSED = 'missed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pendente',
        self::STATUS_IN_PROGRESS => 'Em Andamento',
        self::STATUS_COMPLETED => 'Cumprido',
        self::STATUS_EXTENDED => 'Prorrogado',
        self::STATUS_MISSED => 'Perdido',
        self::STATUS_CANCELLED => 'Cancelado',
    ];

    // Priority Constants
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    public const PRIORITIES = [
        self::PRIORITY_LOW => 'Baixa',
        self::PRIORITY_NORMAL => 'Normal',
        self::PRIORITY_HIGH => 'Alta',
        self::PRIORITY_CRITICAL => 'Crítica',
    ];

    // Counting Types
    public const COUNTING_BUSINESS_DAYS = 'business_days';
    public const COUNTING_CALENDAR_DAYS = 'calendar_days';
    public const COUNTING_CONTINUOUS = 'continuous';

    public const COUNTING_TYPES = [
        self::COUNTING_BUSINESS_DAYS => 'Dias Úteis',
        self::COUNTING_CALENDAR_DAYS => 'Dias Corridos',
        self::COUNTING_CONTINUOUS => 'Contínuo',
    ];

    // Relationships
    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function proceeding()
    {
        return $this->belongsTo(Proceeding::class);
    }

    public function deadlineType()
    {
        return $this->belongsTo(DeadlineType::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function alerts()
    {
        return $this->hasMany(DeadlineAlert::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    public function scopeOverdue($query)
    {
        return $query->pending()->where('due_date', '<', today());
    }

    public function scopeDueToday($query)
    {
        return $query->pending()->whereDate('due_date', today());
    }

    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->pending()
            ->where('due_date', '>=', today())
            ->where('due_date', '<=', today()->addDays($days));
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeMissed($query)
    {
        return $query->where('status', self::STATUS_MISSED);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeCritical($query)
    {
        return $query->where('priority', self::PRIORITY_CRITICAL);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('assigned_user_id', $userId);
    }

    // Calculated Attributes
    public function getDaysRemainingAttribute(): int
    {
        return today()->diffInDays($this->due_date, false);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->isPending() && $this->due_date < today();
    }

    public function getIsDueTodayAttribute(): bool
    {
        return $this->isPending() && $this->due_date->isToday();
    }

    public function getIsDueSoonAttribute(): bool
    {
        $daysRemaining = $this->days_remaining;
        return $this->isPending() && $daysRemaining >= 0 && $daysRemaining <= 3;
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->is_overdue) return 'danger';
        if ($this->is_due_today) return 'danger';
        if ($this->is_due_soon) return 'warning';
        
        return match ($this->status) {
            self::STATUS_COMPLETED => 'success',
            self::STATUS_MISSED => 'danger',
            self::STATUS_CANCELLED => 'gray',
            self::STATUS_EXTENDED => 'info',
            default => 'primary',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'gray',
            self::PRIORITY_NORMAL => 'info',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_CRITICAL => 'danger',
            default => 'gray',
        };
    }

    // Status Methods
    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS, self::STATUS_EXTENDED]);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isMissed(): bool
    {
        return $this->status === self::STATUS_MISSED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    // Actions
    public function markAsInProgress(): void
    {
        $this->update(['status' => self::STATUS_IN_PROGRESS]);
    }

    public function complete(?string $notes = null, ?string $protocol = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'completion_notes' => $notes,
            'document_protocol' => $protocol,
        ]);
    }

    public function markAsMissed(?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_MISSED,
            'completion_notes' => $notes,
        ]);
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'completion_notes' => $reason,
        ]);
    }

    public function extend(Carbon $newDueDate, ?string $reason = null): void
    {
        $originalDate = $this->original_due_date ?? $this->due_date;
        
        $notes = $this->completion_notes ?? '';
        $notes .= "\n[" . now()->format('d/m/Y H:i') . "] Prorrogado de " . $originalDate->format('d/m/Y') . " para " . $newDueDate->format('d/m/Y');
        if ($reason) {
            $notes .= " - Motivo: $reason";
        }
        
        $this->update([
            'status' => self::STATUS_EXTENDED,
            'due_date' => $newDueDate,
            'original_due_date' => $originalDate,
            'completion_notes' => trim($notes),
        ]);
    }

    /**
     * Cria um prazo a partir de uma data de início e tipo
     */
    public static function createFromType(
        Process $process,
        DeadlineType $type,
        Carbon $startDate,
        ?string $title = null,
        ?User $assignedUser = null,
        ?Proceeding $proceeding = null
    ): self {
        $dueDate = self::calculateDueDate(
            $startDate,
            $type->default_days,
            $type->counting_type,
            $type->excludes_start_date,
            $type->extends_to_next_business_day,
            $process->state
        );

        return self::create([
            'process_id' => $process->id,
            'proceeding_id' => $proceeding?->id,
            'deadline_type_id' => $type->id,
            'start_date' => $startDate,
            'due_date' => $dueDate,
            'title' => $title ?? $type->name,
            'description' => $type->description,
            'days_count' => $type->default_days,
            'counting_type' => $type->counting_type,
            'priority' => $type->priority,
            'assigned_user_id' => $assignedUser?->id,
            'created_by_user_id' => auth()->id(),
        ]);
    }

    /**
     * Calcula a data de vencimento baseado nas regras
     */
    public static function calculateDueDate(
        Carbon $startDate,
        int $days,
        string $countingType = self::COUNTING_BUSINESS_DAYS,
        bool $excludesStartDate = true,
        bool $extendsToNextBusinessDay = true,
        ?string $state = null
    ): Carbon {
        $date = $startDate->copy();
        
        // Se exclui dia inicial, começar do próximo dia
        if ($excludesStartDate) {
            $date->addDay();
        }
        
        if ($countingType === self::COUNTING_BUSINESS_DAYS) {
            // Contar apenas dias úteis
            $daysAdded = 0;
            
            while ($daysAdded < $days) {
                // Se é dia útil, conta
                if (self::isBusinessDay($date, $state)) {
                    $daysAdded++;
                }
                
                if ($daysAdded < $days) {
                    $date->addDay();
                }
            }
        } else {
            // Dias corridos ou contínuos
            $date->addDays($days - ($excludesStartDate ? 1 : 0));
        }
        
        // Se cai em dia não-útil e deve prorrogar
        if ($extendsToNextBusinessDay) {
            while (!self::isBusinessDay($date, $state)) {
                $date->addDay();
            }
        }
        
        return $date;
    }

    /**
     * Verifica se é dia útil
     */
    public static function isBusinessDay(Carbon $date, ?string $state = null): bool
    {
        // Sábado ou Domingo não é dia útil
        if ($date->isWeekend()) {
            return false;
        }
        
        // Verificar feriados
        if (Holiday::isHoliday($date, $state)) {
            return false;
        }
        
        return true;
    }

    /**
     * Verificar e marcar prazos vencidos como perdidos
     */
    public static function checkAndMarkMissedDeadlines(): int
    {
        $count = 0;
        
        $overdueDeadlines = self::pending()
            ->where('due_date', '<', today()->subDay()) // Venceu ontem ou antes
            ->get();
        
        foreach ($overdueDeadlines as $deadline) {
            $deadline->markAsMissed('Prazo vencido automaticamente');
            $count++;
        }
        
        return $count;
    }

    /**
     * Gerar alertas pendentes para todos os prazos
     */
    public static function generatePendingAlerts(): int
    {
        $count = 0;
        
        $deadlines = self::pending()
            ->with('deadlineType')
            ->where('due_date', '>=', today())
            ->where('due_date', '<=', today()->addDays(30))
            ->get();
        
        foreach ($deadlines as $deadline) {
            $daysUntilDue = today()->diffInDays($deadline->due_date, false);
            $alertDays = $deadline->deadlineType?->alert_days ?? [5, 2, 1];
            $alertsSent = $deadline->alerts_sent ?? [];
            
            foreach ($alertDays as $daysBefore) {
                if ($daysUntilDue === $daysBefore && !in_array($daysBefore, $alertsSent)) {
                    // Criar alerta
                    DeadlineAlert::create([
                        'deadline_id' => $deadline->id,
                        'user_id' => $deadline->assigned_user_id ?? $deadline->process->responsible_user_id ?? 1,
                        'type' => 'notification',
                        'days_before' => $daysBefore,
                        'message' => "Prazo '{$deadline->title}' vence em {$daysBefore} dia(s) - {$deadline->due_date->format('d/m/Y')}",
                    ]);
                    
                    $alertsSent[] = $daysBefore;
                    $count++;
                }
            }
            
            if (count($alertsSent) !== count($deadline->alerts_sent ?? [])) {
                $deadline->update(['alerts_sent' => $alertsSent]);
            }
        }
        
        return $count;
    }
}
