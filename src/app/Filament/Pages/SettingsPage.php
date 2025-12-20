<?php

namespace App\Filament\Pages;

use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\ServiceReminder;
use App\Notifications\PaymentDueReminder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Artisan;

class SettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Configurações';
    protected static ?string $title = 'Configurações do Sistema';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.settings-page';

    public function sendTestServiceReminder(): void
    {
        $service = Service::with(['client', 'serviceType'])->first();

        if (!$service) {
            Notification::make()
                ->title('Nenhum serviço encontrado')
                ->body('Cadastre pelo menos um serviço para testar.')
                ->warning()
                ->send();
            return;
        }

        $user = auth()->user();
        $user->notify(new ServiceReminder($service));

        Notification::make()
            ->title('Notificação enviada!')
            ->body("Lembrete de serviço enviado para {$user->email}")
            ->success()
            ->send();
    }

    public function sendTestPaymentReminder(): void
    {
        $transaction = Transaction::with(['client', 'paymentMethod'])->first();

        if (!$transaction) {
            Notification::make()
                ->title('Nenhuma transação encontrada')
                ->body('Cadastre pelo menos uma transação para testar.')
                ->warning()
                ->send();
            return;
        }

        $user = auth()->user();
        $user->notify(new PaymentDueReminder($transaction));

        Notification::make()
            ->title('Notificação enviada!')
            ->body("Lembrete de pagamento enviado para {$user->email}")
            ->success()
            ->send();
    }

    public function runServiceReminders(): void
    {
        Artisan::call('services:send-reminders', ['--days' => 1]);
        $output = Artisan::output();

        Notification::make()
            ->title('Comando executado')
            ->body($output)
            ->success()
            ->send();
    }

    public function runPaymentReminders(): void
    {
        Artisan::call('payments:send-reminders', ['--days' => 3]);
        $output = Artisan::output();

        Notification::make()
            ->title('Comando executado')
            ->body($output)
            ->success()
            ->send();
    }

    public function clearNotifications(): void
    {
        auth()->user()->notifications()->delete();

        Notification::make()
            ->title('Notificações limpas')
            ->body('Todas as notificações foram removidas.')
            ->success()
            ->send();
    }

    public function runBackupDb(): void
    {
        Artisan::call('backup:run', ['--only-db' => true]);
        $output = Artisan::output();

        Notification::make()
            ->title('Backup do banco realizado')
            ->body('O backup do banco de dados foi criado com sucesso.')
            ->success()
            ->send();
    }

    public function runBackupFull(): void
    {
        Artisan::call('backup:run');
        $output = Artisan::output();

        Notification::make()
            ->title('Backup completo realizado')
            ->body('O backup completo foi criado com sucesso.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backup_db')
                ->label('Backup BD')
                ->icon('heroicon-o-circle-stack')
                ->color('success')
                ->action('runBackupDb'),
            Action::make('clear_cache')
                ->label('Limpar Cache')
                ->icon('heroicon-o-trash')
                ->color('gray')
                ->action(function () {
                    Artisan::call('optimize:clear');
                    Notification::make()
                        ->title('Cache limpo')
                        ->success()
                        ->send();
                }),
        ];
    }
}
