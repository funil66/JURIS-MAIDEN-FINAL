<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Models\User;
use App\Notifications\ServiceReminder;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendServiceReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'services:send-reminders {--days=1 : Dias de antecedência para o lembrete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia lembretes de serviços agendados para os próximos dias';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $targetDate = Carbon::now()->addDays($days)->startOfDay();
        $endOfTargetDate = Carbon::now()->addDays($days)->endOfDay();

        $this->info("Buscando serviços agendados para {$targetDate->format('d/m/Y')}...");

        $services = Service::with(['client', 'serviceType'])
            ->whereBetween('scheduled_datetime', [$targetDate, $endOfTargetDate])
            ->whereIn('status', ['pending', 'in_progress'])
            ->get();

        if ($services->isEmpty()) {
            $this->info('Nenhum serviço encontrado para enviar lembretes.');
            return Command::SUCCESS;
        }

        $this->info("Encontrado(s) {$services->count()} serviço(s).");

        // Notificar todos os usuários administradores
        $users = User::all();

        $bar = $this->output->createProgressBar($services->count());
        $bar->start();

        foreach ($services as $service) {
            foreach ($users as $user) {
                $user->notify(new ServiceReminder($service));
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✅ {$services->count()} lembrete(s) enviado(s) com sucesso!");

        return Command::SUCCESS;
    }
}
