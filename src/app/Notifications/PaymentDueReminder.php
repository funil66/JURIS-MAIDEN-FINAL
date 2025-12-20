<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentDueReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected Transaction $transaction;

    /**
     * Create a new notification instance.
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
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
        $typeLabel = $this->transaction->type === 'income' ? 'Receita' : 'Despesa';
        $emoji = $this->transaction->type === 'income' ? '💵' : '💸';

        return (new MailMessage)
            ->subject("{$emoji} Lembrete de Vencimento - {$typeLabel}")
            ->greeting('Olá!')
            ->line("Você tem uma transação com vencimento próximo:")
            ->line("**Tipo:** {$typeLabel}")
            ->line("**Descrição:** {$this->transaction->description}")
            ->line("**Valor:** R$ " . number_format($this->transaction->amount, 2, ',', '.'))
            ->line("**Vencimento:** {$this->transaction->due_date->format('d/m/Y')}")
            ->line("**Cliente:** " . ($this->transaction->client->name ?? 'N/A'))
            ->action('Ver Transação', url('/funil/transactions/' . $this->transaction->id . '/edit'))
            ->line('Não se esqueça de atualizar o status após o pagamento!')
            ->salutation('LogísticaJus - Sistema de Gestão');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $typeLabel = $this->transaction->type === 'income' ? 'Receita' : 'Despesa';
        $emoji = $this->transaction->type === 'income' ? '💵' : '💸';

        return [
            'title' => "{$emoji} Vencimento Próximo",
            'body' => "{$typeLabel} de R$ " . number_format($this->transaction->amount, 2, ',', '.') . " vence em {$this->transaction->due_date->format('d/m/Y')}",
            'transaction_id' => $this->transaction->id,
            'amount' => $this->transaction->amount,
            'due_date' => $this->transaction->due_date->toISOString(),
            'type' => 'payment_due',
        ];
    }
}
