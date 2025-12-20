<?php

namespace App\Notifications;

use App\Models\Service;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Notifications\Actions\Action;

class ServiceReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected Service $service;

    /**
     * Create a new notification instance.
     */
    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusLabels = [
            'pending' => 'Pendente',
            'in_progress' => 'Em Andamento',
            'completed' => 'Concluído',
            'cancelled' => 'Cancelado',
        ];

        return (new MailMessage)
            ->subject('🔔 Lembrete de Serviço - ' . $this->service->code)
            ->greeting('Olá!')
            ->line('Você tem um serviço agendado para breve:')
            ->line('**Código:** ' . $this->service->code)
            ->line('**Data:** ' . $this->service->scheduled_datetime->format('d/m/Y H:i'))
            ->line('**Cliente:** ' . ($this->service->client->name ?? 'N/A'))
            ->line('**Tipo:** ' . ($this->service->serviceType->name ?? 'N/A'))
            ->line('**Local:** ' . ($this->service->location ?? 'N/A'))
            ->line('**Status:** ' . ($statusLabels[$this->service->status] ?? $this->service->status))
            ->action('Ver Serviço', url('/funil/services/' . $this->service->id . '/edit'))
            ->line('Não se esqueça de atualizar o status após a conclusão!')
            ->salutation('LogísticaJus - Sistema de Gestão');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => '🔔 Serviço Agendado',
            'body' => "Serviço {$this->service->code} agendado para {$this->service->scheduled_datetime->format('d/m/Y H:i')}",
            'service_id' => $this->service->id,
            'service_code' => $this->service->code,
            'scheduled_datetime' => $this->service->scheduled_datetime->toISOString(),
            'client_name' => $this->service->client->name ?? 'N/A',
            'type' => 'service_reminder',
        ];
    }
}
