<?php

namespace App\Models;

use App\Traits\HasGlobalUid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Proceeding extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasGlobalUid;

    /**
     * Prefixo do UID para Andamentos
     */
    public static function getUidPrefix(): string
    {
        return 'AND';
    }

    protected $fillable = [
        'process_id',
        'user_id',
        'title',
        'content',
        'proceeding_date',
        'proceeding_time',
        'published_at',
        'type',
        'source',
        'has_deadline',
        'deadline_date',
        'deadline_days',
        'deadline_completed',
        'deadline_completed_at',
        'requires_action',
        'action_description',
        'action_completed',
        'action_completed_at',
        'action_responsible_id',
        'status',
        'is_important',
        'is_favorable',
        'external_id',
        'metadata',
        'notes',
        'internal_notes',
        'has_attachments',
        'attachments_count',
    ];

    protected $casts = [
        'proceeding_date' => 'date',
        'published_at' => 'datetime',
        'deadline_date' => 'date',
        'deadline_completed' => 'boolean',
        'deadline_completed_at' => 'datetime',
        'requires_action' => 'boolean',
        'action_completed' => 'boolean',
        'action_completed_at' => 'datetime',
        'has_deadline' => 'boolean',
        'is_important' => 'boolean',
        'is_favorable' => 'boolean',
        'has_attachments' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Activity Log Options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'deadline_completed', 'action_completed'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // ==========================================
    // RELACIONAMENTOS
    // ==========================================

    /**
     * Processo do andamento
     */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /**
     * Usuário que registrou
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Responsável pela ação
     */
    public function actionResponsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_responsible_id');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Andamentos pendentes de análise
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Andamentos com prazo
     */
    public function scopeWithDeadline($query)
    {
        return $query->where('has_deadline', true);
    }

    /**
     * Prazos pendentes
     */
    public function scopePendingDeadlines($query)
    {
        return $query->where('has_deadline', true)
            ->where('deadline_completed', false)
            ->whereNotNull('deadline_date');
    }

    /**
     * Prazos vencendo
     */
    public function scopeDeadlinesExpiring($query, $days = 5)
    {
        return $query->pendingDeadlines()
            ->where('deadline_date', '<=', now()->addDays($days))
            ->where('deadline_date', '>=', now());
    }

    /**
     * Prazos vencidos
     */
    public function scopeOverdueDeadlines($query)
    {
        return $query->pendingDeadlines()
            ->where('deadline_date', '<', now());
    }

    /**
     * Que requerem ação
     */
    public function scopeRequiresAction($query)
    {
        return $query->where('requires_action', true)
            ->where('action_completed', false);
    }

    /**
     * Importantes
     */
    public function scopeImportant($query)
    {
        return $query->where('is_important', true);
    }

    /**
     * Por tipo
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Por fonte
     */
    public function scopeFromSource($query, $source)
    {
        return $query->where('source', $source);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Label do tipo
     */
    public function getTypeLabelAttribute(): string
    {
        return self::getTypeOptions()[$this->type] ?? $this->type;
    }

    /**
     * Label da fonte
     */
    public function getSourceLabelAttribute(): string
    {
        return self::getSourceOptions()[$this->source] ?? $this->source;
    }

    /**
     * Label do status
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    /**
     * Dias restantes para o prazo
     */
    public function getDaysUntilDeadlineAttribute(): ?int
    {
        if (!$this->has_deadline || !$this->deadline_date || $this->deadline_completed) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->deadline_date, false);
    }

    /**
     * Está atrasado?
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->has_deadline || !$this->deadline_date || $this->deadline_completed) {
            return false;
        }

        return $this->deadline_date->isPast();
    }

    /**
     * Cor do status do prazo
     */
    public function getDeadlineColorAttribute(): string
    {
        if (!$this->has_deadline || $this->deadline_completed) {
            return 'gray';
        }

        $days = $this->days_until_deadline;

        if ($days === null) {
            return 'gray';
        }

        if ($days < 0) {
            return 'danger'; // Vencido
        }

        if ($days <= 2) {
            return 'danger'; // Urgente
        }

        if ($days <= 5) {
            return 'warning'; // Atenção
        }

        return 'success'; // OK
    }

    // ==========================================
    // MÉTODOS
    // ==========================================

    /**
     * Marcar prazo como cumprido
     */
    public function completeDeadline(): bool
    {
        if (!$this->has_deadline) {
            return false;
        }

        $this->update([
            'deadline_completed' => true,
            'deadline_completed_at' => now(),
        ]);

        return true;
    }

    /**
     * Marcar ação como concluída
     */
    public function completeAction(): bool
    {
        if (!$this->requires_action) {
            return false;
        }

        $this->update([
            'action_completed' => true,
            'action_completed_at' => now(),
            'status' => 'actioned',
        ]);

        return true;
    }

    /**
     * Marcar como analisado
     */
    public function markAsAnalyzed(): bool
    {
        $this->update([
            'status' => 'analyzed',
        ]);

        return true;
    }

    // ==========================================
    // OPTIONS
    // ==========================================

    /**
     * Opções de tipo
     */
    public static function getTypeOptions(): array
    {
        return [
            'movement' => 'Movimentação',
            'decision' => 'Decisão',
            'sentence' => 'Sentença',
            'dispatch' => 'Despacho',
            'petition' => 'Petição',
            'hearing' => 'Audiência',
            'publication' => 'Publicação DJE',
            'citation' => 'Citação/Intimação',
            'deadline' => 'Prazo',
            'appeal' => 'Recurso',
            'transit' => 'Trânsito em Julgado',
            'archive' => 'Arquivamento',
            'unarchive' => 'Desarquivamento',
            'distribution' => 'Distribuição',
            'conclusion' => 'Conclusão ao Juiz',
            'other' => 'Outro',
        ];
    }

    /**
     * Opções de fonte
     */
    public static function getSourceOptions(): array
    {
        return [
            'manual' => 'Cadastro Manual',
            'datajud' => 'DataJud (CNJ)',
            'escavador' => 'Escavador',
            'projudi' => 'ProJudi',
            'pje' => 'PJe',
            'esaj' => 'E-SAJ',
            'eproc' => 'E-Proc',
            'sei' => 'SEI',
            'tjdft' => 'TJDFT',
            'other_api' => 'Outra API',
            'import' => 'Importação',
        ];
    }

    /**
     * Opções de status
     */
    public static function getStatusOptions(): array
    {
        return [
            'pending' => 'Pendente',
            'analyzed' => 'Analisado',
            'actioned' => 'Ação Tomada',
            'archived' => 'Arquivado',
        ];
    }

    /**
     * Tipos que geralmente têm prazo
     */
    public static function getTypesWithDeadline(): array
    {
        return ['citation', 'deadline', 'decision', 'dispatch', 'publication'];
    }

    /**
     * Tipos importantes
     */
    public static function getImportantTypes(): array
    {
        return ['decision', 'sentence', 'transit', 'appeal'];
    }
}
