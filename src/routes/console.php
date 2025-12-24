<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Agendamento de Tarefas - LogísticaJus
|--------------------------------------------------------------------------
|
| Lembretes de serviços e pagamentos são enviados automaticamente.
| Para ativar o scheduler, adicione ao crontab do servidor:
| * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
|
*/

// Lembrete de serviços - 1 dia antes, às 8h da manhã
Schedule::command('services:send-reminders --days=1')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// Lembrete de pagamentos - 3 dias antes, às 9h da manhã
Schedule::command('payments:send-reminders --days=3')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// Backup diário do banco de dados - às 3h da manhã
Schedule::command('backup:run --only-db')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/backup.log'));

// Backup completo semanal - Domingos às 4h da manhã
Schedule::command('backup:run')
    ->weeklyOn(0, '04:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/backup.log'));

// Limpeza de backups antigos - Domingos às 5h da manhã
Schedule::command('backup:clean')
    ->weeklyOn(0, '05:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/backup.log'));

/*
|--------------------------------------------------------------------------
| Novos Módulos - Sprints 27-32
|--------------------------------------------------------------------------
*/

// Processar prazos - a cada hora (verifica vencidos e envia alertas)
Schedule::command('deadlines:process')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/deadlines.log'));

// Atualizar status de assinaturas - a cada 6 horas
Schedule::command('signatures:update-status')
    ->everySixHours()
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/signatures.log'));

// Sincronizar movimentações dos tribunais - agendamentos pendentes
Schedule::command('courts:sync --scheduled')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/courts.log'));

// Sincronizar Google Drive - a cada hora
Schedule::command('drive:sync')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/drive.log'));
