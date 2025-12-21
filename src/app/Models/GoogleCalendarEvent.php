<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GoogleCalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'eventable_type',
        'eventable_id',
        'google_event_id',
        'google_calendar_id',
        'sync_status',
        'last_error',
        'last_synced_at',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    /**
     * Relacionamento com o usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento polimórfico (Event ou Service)
     */
    public function eventable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Verificar se está sincronizado
     */
    public function isSynced(): bool
    {
        return $this->sync_status === 'synced';
    }

    /**
     * Verificar se tem erro
     */
    public function hasError(): bool
    {
        return $this->sync_status === 'error';
    }

    /**
     * Marcar como sincronizado
     */
    public function markAsSynced(): void
    {
        $this->update([
            'sync_status' => 'synced',
            'last_error' => null,
            'last_synced_at' => now(),
        ]);
    }

    /**
     * Marcar como erro
     */
    public function markAsError(string $error): void
    {
        $this->update([
            'sync_status' => 'error',
            'last_error' => $error,
        ]);
    }

    /**
     * Scope para eventos sincronizados
     */
    public function scopeSynced($query)
    {
        return $query->where('sync_status', 'synced');
    }

    /**
     * Scope para eventos com erro
     */
    public function scopeWithErrors($query)
    {
        return $query->where('sync_status', 'error');
    }

    /**
     * Scope para eventos pendentes
     */
    public function scopePending($query)
    {
        return $query->where('sync_status', 'pending');
    }
}
