<?php

namespace App\Filament\Pages;

use App\Services\GoogleCalendarService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class GoogleCalendarSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Google Calendar';
    protected static ?string $title = 'Integração Google Calendar';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 50;
    protected static string $view = 'filament.pages.google-calendar-settings';

    public bool $isConnected = false;
    public ?string $calendarId = null;
    public ?string $tokenExpires = null;
    public int $syncedEventsCount = 0;
    public int $pendingEventsCount = 0;
    public int $errorEventsCount = 0;

    public function mount(): void
    {
        $user = Auth::user();
        $this->isConnected = $user->isGoogleCalendarConnected();
        $this->calendarId = $user->google_calendar_id;
        $this->tokenExpires = $user->google_token_expires_at?->format('d/m/Y H:i');
        
        if ($this->isConnected) {
            $this->syncedEventsCount = $user->googleCalendarEvents()->synced()->count();
            $this->pendingEventsCount = $user->googleCalendarEvents()->pending()->count();
            $this->errorEventsCount = $user->googleCalendarEvents()->withErrors()->count();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connect')
                ->label('Conectar Google Calendar')
                ->icon('heroicon-o-link')
                ->color('primary')
                ->visible(!$this->isConnected)
                ->url(fn () => $this->getAuthUrl()),

            Action::make('disconnect')
                ->label('Desconectar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible($this->isConnected)
                ->requiresConfirmation()
                ->modalHeading('Desconectar Google Calendar')
                ->modalDescription('Tem certeza que deseja desconectar? Todos os eventos sincronizados serão removidos do registro local.')
                ->action(function () {
                    Auth::user()->disconnectGoogleCalendar();
                    
                    Notification::make()
                        ->title('Desconectado com sucesso')
                        ->success()
                        ->send();
                    
                    $this->redirect(static::getUrl());
                }),

            Action::make('sync')
                ->label('Sincronizar Agora')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible($this->isConnected)
                ->action(function () {
                    $this->syncAllEvents();
                }),
        ];
    }

    public function getAuthUrl(): string
    {
        try {
            $service = new GoogleCalendarService(Auth::user());
            return $service->getAuthUrl();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro na configuração')
                ->body('Verifique as credenciais do Google API no arquivo .env')
                ->danger()
                ->send();
            
            return '#';
        }
    }

    public function syncAllEvents(): void
    {
        try {
            $user = Auth::user();
            $service = new GoogleCalendarService($user);

            if (!$service->isAuthenticated()) {
                Notification::make()
                    ->title('Token expirado')
                    ->body('Por favor, reconecte sua conta Google.')
                    ->warning()
                    ->send();
                return;
            }

            $synced = 0;
            $errors = 0;

            // Sincronizar Events
            $events = \App\Models\Event::where('created_at', '>=', now()->subMonths(3))
                ->orWhere('date', '>=', now())
                ->get();

            foreach ($events as $event) {
                try {
                    $service->syncEvent($event, 'event');
                    $synced++;
                } catch (\Exception $e) {
                    $errors++;
                }
            }

            // Sincronizar Services com deadline
            $services = \App\Models\Service::whereNotNull('deadline')
                ->where('status', '!=', 'concluido')
                ->get();

            foreach ($services as $service_item) {
                try {
                    $service->syncEvent($service_item, 'service');
                    $synced++;
                } catch (\Exception $e) {
                    $errors++;
                }
            }

            Notification::make()
                ->title('Sincronização concluída')
                ->body("$synced eventos sincronizados" . ($errors > 0 ? ", $errors erros" : ""))
                ->success()
                ->send();

            $this->mount();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro na sincronização')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function createCalendar(): void
    {
        try {
            $user = Auth::user();
            $service = new GoogleCalendarService($user);

            if (!$service->isAuthenticated()) {
                Notification::make()
                    ->title('Não autenticado')
                    ->body('Por favor, conecte sua conta Google primeiro.')
                    ->warning()
                    ->send();
                return;
            }

            $calendarId = $service->createCalendar('LogísticaJus - Agenda Jurídica');
            
            $user->update(['google_calendar_id' => $calendarId]);

            Notification::make()
                ->title('Calendário criado')
                ->body('Um novo calendário "LogísticaJus" foi criado na sua conta Google.')
                ->success()
                ->send();

            $this->mount();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao criar calendário')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
