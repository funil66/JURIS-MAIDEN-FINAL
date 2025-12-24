<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Filament\Notifications\Notification;

class DeadlineAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'deadline_id',
        'user_id',
        'type',
        'days_before',
        'sent_at',
        'read_at',
        'is_sent',
        'message',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'is_sent' => 'boolean',
        'days_before' => 'integer',
        'metadata' => 'array',
    ];

    // Alert Types
    public const TYPE_EMAIL = 'email';
    public const TYPE_NOTIFICATION = 'notification';
    public const TYPE_WHATSAPP = 'whatsapp';
    public const TYPE_SMS = 'sms';
    public const TYPE_SYSTEM = 'system';

    public const TYPES = [
        self::TYPE_EMAIL => 'E-mail',
        self::TYPE_NOTIFICATION => 'Notificação',
        self::TYPE_WHATSAPP => 'WhatsApp',
        self::TYPE_SMS => 'SMS',
        self::TYPE_SYSTEM => 'Sistema',
    ];

    // Relationships
    public function deadline()
    {
        return $this->belongsTo(Deadline::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('is_sent', false);
    }

    public function scopeSent($query)
    {
        return $query->where('is_sent', true);
    }

    public function scopeUnread($query)
    {
        return $query->sent()->whereNull('read_at');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Actions
    public function send(): void
    {
        if ($this->is_sent) {
            return;
        }

        match ($this->type) {
            self::TYPE_NOTIFICATION => $this->sendNotification(),
            self::TYPE_EMAIL => $this->sendEmail(),
            self::TYPE_WHATSAPP => $this->sendWhatsApp(),
            self::TYPE_SMS => $this->sendSms(),
            default => $this->sendNotification(),
        };

        $this->update([
            'is_sent' => true,
            'sent_at' => now(),
        ]);
    }

    protected function sendNotification(): void
    {
        if (!$this->user) {
            return;
        }

        $deadline = $this->deadline;
        $title = $this->days_before === 0 
            ? '🚨 PRAZO VENCE HOJE!' 
            : ($this->days_before === 1 
                ? '⚠️ Prazo vence AMANHÃ!' 
                : "📅 Prazo em {$this->days_before} dias");

        Notification::make()
            ->title($title)
            ->body($this->message)
            ->icon($this->days_before <= 1 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-calendar')
            ->iconColor($this->days_before <= 1 ? 'danger' : 'warning')
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('Ver Prazo')
                    ->url(route('filament.funil.resources.deadlines.view', $deadline))
                    ->button(),
                \Filament\Notifications\Actions\Action::make('complete')
                    ->label('Marcar Cumprido')
                    ->color('success')
                    ->dispatch('completeDeadline', ['deadline' => $deadline->id]),
            ])
            ->sendToDatabase($this->user);
    }

    protected function sendEmail(): void
    {
        // TODO: Implementar envio de email
        // Mail::to($this->user)->send(new DeadlineAlertMail($this));
    }

    protected function sendWhatsApp(): void
    {
        // TODO: Implementar integração WhatsApp
        // Usar API do WhatsApp configurada no sistema
    }

    protected function sendSms(): void
    {
        // TODO: Implementar envio de SMS
    }

    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Processa e envia todos os alertas pendentes
     */
    public static function processPendingAlerts(): int
    {
        $count = 0;
        
        $pendingAlerts = static::pending()
            ->with(['deadline', 'user'])
            ->get();
        
        foreach ($pendingAlerts as $alert) {
            try {
                $alert->send();
                $count++;
            } catch (\Exception $e) {
                // Log error but continue processing
                \Log::error("Erro ao enviar alerta {$alert->id}: " . $e->getMessage());
            }
        }
        
        return $count;
    }
}
