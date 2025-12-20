<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    protected Transaction $transaction;
    protected int $daysOverdue;

    /**
     * Create a new notification instance.
     */
    public function __construct(Transaction $transaction, int $daysOverdue = 0)
    {
        $this->transaction = $transaction;
        $this->daysOverdue = $daysOverdue;
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

        return (new MailMessage)
            ->subject("⚠️ ATENÇÃO: {$typeLabel} em Atraso - {$this->daysOverdue} dias")
            ->greeting('Atenção!')
            ->line("Você tem uma transação em atraso há {$this->daysOverdue} dia(s):")
            ->line("**Tipo:** {$typeLabel}")
            ->line("**Descrição:** {$this->transaction->description}")
            ->line("**Valor:** R$ " . number_format($this->transaction->amount, 2, ',', '.'))
            ->line("**Vencimento:** {$this->transaction->due_date->format('d/m/Y')}")
            ->line("**Cliente:** " . ($this->transaction->client->name ?? 'N/A'))
            ->action('Regularizar Pagamento', url('/funil/transactions/' . $this->transaction->id . '/edit'))
            ->line('Atualize o status assim que possível!')
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

        return [
            'title' => '⚠️ Pagamento em Atraso',
            'body' => "{$typeLabel} de R$ " . number_format($this->transaction->amount, 2, ',', '.') . " está em atraso há {$this->daysOverdue} dia(s)",
            'transaction_id' => $this->transaction->id,
            'amount' => $this->transaction->amount,
            'due_date' => $this->transaction->due_date->toISOString(),
            'days_overdue' => $this->daysOverdue,
            'type' => 'payment_overdue',
        ];
    }
}
