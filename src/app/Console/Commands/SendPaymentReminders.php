<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\User;
use App\Notifications\PaymentDueReminder;
use App\Notifications\PaymentOverdue;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendPaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:send-reminders {--days=3 : Dias de antecedência para lembrete de vencimento}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia lembretes de pagamentos próximos do vencimento e atrasados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $users = User::all();
        $sentCount = 0;

        // 1. Lembretes de vencimento próximo
        $this->info("Buscando transações com vencimento nos próximos {$days} dia(s)...");

        $upcomingPayments = Transaction::with(['client', 'paymentMethod'])
            ->where('status', 'pending')
            ->whereBetween('due_date', [
                Carbon::now()->startOfDay(),
                Carbon::now()->addDays($days)->endOfDay()
            ])
            ->get();

        if ($upcomingPayments->isNotEmpty()) {
            $this->info("Encontrada(s) {$upcomingPayments->count()} transação(ões) próximas do vencimento.");
            
            foreach ($upcomingPayments as $transaction) {
                foreach ($users as $user) {
                    $user->notify(new PaymentDueReminder($transaction));
                }
                $sentCount++;
            }
        }

        // 2. Atualizar status de transações atrasadas e enviar alertas
        $this->info("Verificando transações atrasadas...");

        $overduePayments = Transaction::with(['client', 'paymentMethod'])
            ->where('status', 'pending')
            ->where('due_date', '<', Carbon::now()->startOfDay())
            ->get();

        if ($overduePayments->isNotEmpty()) {
            $this->info("Encontrada(s) {$overduePayments->count()} transação(ões) atrasada(s).");

            foreach ($overduePayments as $transaction) {
                // Atualizar status para atrasado
                $transaction->update(['status' => 'overdue']);

                // Calcular dias de atraso
                $daysOverdue = Carbon::now()->diffInDays($transaction->due_date);

                foreach ($users as $user) {
                    $user->notify(new PaymentOverdue($transaction, $daysOverdue));
                }
                $sentCount++;
            }
        }

        if ($sentCount === 0) {
            $this->info('Nenhuma transação encontrada para enviar lembretes.');
        } else {
            $this->info("✅ {$sentCount} lembrete(s) enviado(s) com sucesso!");
        }

        return Command::SUCCESS;
    }
}
