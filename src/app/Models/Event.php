<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Event extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'title',
        'description',
        'type',
        'starts_at',
        'ends_at',
        'all_day',
        'recurrence',
        'recurrence_end',
        'service_id',
        'client_id',
        'location',
        'location_address',
        'color',
        'status',
        'reminder_minutes',
        'reminder_sent',
        'google_event_id',
        'google_synced_at',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'all_day' => 'boolean',
        'recurrence_end' => 'date',
        'reminder_sent' => 'boolean',
        'google_synced_at' => 'datetime',
    ];

    /**
     * Activity Log Options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'starts_at', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Relacionamento com Serviço
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Relacionamento com Cliente
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relacionamento com eventos sincronizados no Google Calendar
     */
    public function googleCalendarEvents(): MorphMany
    {
        return $this->morphMany(GoogleCalendarEvent::class, 'eventable');
    }

    /**
     * Tipos de evento
     */
    public static function getTypeOptions(): array
    {
        return [
            'hearing' => 'Audiência',
            'deadline' => 'Prazo',
            'meeting' => 'Reunião',
            'task' => 'Tarefa',
            'reminder' => 'Lembrete',
            'appointment' => 'Compromisso',
            'other' => 'Outro',
        ];
    }

    /**
     * Cores por tipo
     */
    public static function getTypeColors(): array
    {
        return [
            'hearing' => '#dc2626',      // Vermelho
            'deadline' => '#f59e0b',     // Amarelo
            'meeting' => '#3b82f6',      // Azul
            'task' => '#10b981',         // Verde
            'reminder' => '#8b5cf6',     // Roxo
            'appointment' => '#06b6d4',  // Ciano
            'other' => '#6b7280',        // Cinza
        ];
    }

    /**
     * Status options
     */
    public static function getStatusOptions(): array
    {
        return [
            'scheduled' => 'Agendado',
            'confirmed' => 'Confirmado',
            'completed' => 'Concluído',
            'cancelled' => 'Cancelado',
        ];
    }

    /**
     * Recurrence options
     */
    public static function getRecurrenceOptions(): array
    {
        return [
            'none' => 'Não repete',
            'daily' => 'Diariamente',
            'weekly' => 'Semanalmente',
            'monthly' => 'Mensalmente',
            'yearly' => 'Anualmente',
        ];
    }

    /**
     * Reminder options
     */
    public static function getReminderOptions(): array
    {
        return [
            null => 'Sem lembrete',
            15 => '15 minutos antes',
            30 => '30 minutos antes',
            60 => '1 hora antes',
            120 => '2 horas antes',
            1440 => '1 dia antes',
            2880 => '2 dias antes',
            10080 => '1 semana antes',
        ];
    }

    /**
     * Scopes
     */
    public function scopeUpcoming($query)
    {
        return $query->where('starts_at', '>=', now())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('starts_at');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('starts_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('starts_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopePendingReminders($query)
    {
        return $query->whereNotNull('reminder_minutes')
            ->where('reminder_sent', false)
            ->where('starts_at', '>', now())
            ->whereRaw('starts_at <= DATE_ADD(NOW(), INTERVAL reminder_minutes MINUTE)');
    }

    /**
     * Verifica se evento já passou
     */
    public function isPast(): bool
    {
        return $this->starts_at->isPast();
    }

    /**
     * Verifica se é hoje
     */
    public function isToday(): bool
    {
        return $this->starts_at->isToday();
    }

    /**
     * Retorna duração formatada
     */
    public function getDurationAttribute(): ?string
    {
        if (!$this->ends_at) {
            return null;
        }

        $diff = $this->starts_at->diff($this->ends_at);
        
        if ($diff->h > 0) {
            return $diff->h . 'h' . ($diff->i > 0 ? ' ' . $diff->i . 'min' : '');
        }
        
        return $diff->i . 'min';
    }
}
