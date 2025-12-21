<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Services\WhatsAppService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class WhatsAppSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'WhatsApp';
    protected static ?string $title = 'Integração WhatsApp';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 51;
    protected static string $view = 'filament.pages.whatsapp-settings';

    public bool $isConfigured = false;
    public ?string $phoneNumberId = null;

    // Formulário de envio de mensagem
    public ?string $selectedClientId = null;
    public ?string $customPhone = null;
    public ?string $messageSubject = null;
    public ?string $messageBody = null;

    public function mount(): void
    {
        $service = new WhatsAppService();
        $this->isConfigured = $service->isConfigured();
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testConnection')
                ->label('Testar Conexão')
                ->icon('heroicon-o-signal')
                ->color('warning')
                ->visible($this->isConfigured)
                ->action(function () {
                    $this->testConnection();
                }),
        ];
    }

    public function testConnection(): void
    {
        try {
            $service = new WhatsAppService();
            
            if (!$service->isConfigured()) {
                Notification::make()
                    ->title('WhatsApp não configurado')
                    ->body('Configure as credenciais no arquivo .env')
                    ->danger()
                    ->send();
                return;
            }

            Notification::make()
                ->title('Conexão OK')
                ->body('As credenciais do WhatsApp estão configuradas. Envie uma mensagem de teste para verificar.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro na conexão')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function sendTestMessage(): void
    {
        if (empty($this->customPhone) && empty($this->selectedClientId)) {
            Notification::make()
                ->title('Erro')
                ->body('Selecione um cliente ou informe um número de telefone.')
                ->danger()
                ->send();
            return;
        }

        if (empty($this->messageBody)) {
            Notification::make()
                ->title('Erro')
                ->body('Informe o conteúdo da mensagem.')
                ->danger()
                ->send();
            return;
        }

        try {
            $service = new WhatsAppService();
            
            $phone = $this->customPhone;
            
            if (!empty($this->selectedClientId)) {
                $client = Client::find($this->selectedClientId);
                $phone = $client?->whatsapp ?? $client?->phone;
            }

            if (empty($phone)) {
                Notification::make()
                    ->title('Erro')
                    ->body('Número de telefone não encontrado.')
                    ->danger()
                    ->send();
                return;
            }

            $result = $service->sendCustomMessage(
                $phone,
                $this->messageSubject ?? 'Mensagem de Teste',
                $this->messageBody
            );

            if ($result['success']) {
                Notification::make()
                    ->title('Mensagem enviada!')
                    ->body('A mensagem foi enviada com sucesso.')
                    ->success()
                    ->send();

                $this->reset(['selectedClientId', 'customPhone', 'messageSubject', 'messageBody']);
            } else {
                Notification::make()
                    ->title('Erro ao enviar')
                    ->body($result['error'] ?? 'Erro desconhecido')
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getClients(): array
    {
        return Client::query()
            ->where('is_active', true)
            ->whereNotNull('whatsapp')
            ->orWhereNotNull('phone')
            ->pluck('name', 'id')
            ->toArray();
    }
}
