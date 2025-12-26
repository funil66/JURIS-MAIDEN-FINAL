<?php

namespace App\Filament\Pages;

use App\Models\GoogleDriveActivityLog;
use App\Models\GoogleDriveFile;
use App\Models\GoogleDriveSetting;
use App\Services\GoogleDriveService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

class GoogleDriveSettingsPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-cloud';
    protected static ?string $navigationLabel = 'Google Drive';
    protected static ?string $title = 'Configurações do Google Drive';
    protected static ?string $slug = 'google-drive-settings';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 30;

    protected static string $view = 'filament.pages.google-drive-settings';

    public ?array $data = [];
    public ?GoogleDriveSetting $settings = null;
    protected ?GoogleDriveService $driveService = null;
    public array $stats = [];
    public string $activeTab = 'connection';

    public function mount(): void
    {
        $this->settings = GoogleDriveSetting::getForCurrentUser();
        $this->driveService = new GoogleDriveService($this->settings);
        $this->refreshStats();

        if ($this->settings) {
            $this->form->fill([
                'auto_sync' => $this->settings->auto_sync,
                'sync_reports' => $this->settings->sync_reports,
                'sync_documents' => $this->settings->sync_documents,
                'sync_invoices' => $this->settings->sync_invoices,
                'sync_contracts' => $this->settings->sync_contracts,
                'folder_structure' => $this->settings->folder_structure,
            ]);
        }
    }

    public function refreshStats(): void
    {
        $this->stats = $this->driveService->getStats();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Sincronização Automática')
                    ->description('Configure quais tipos de documentos devem ser sincronizados automaticamente')
                    ->schema([
                        Forms\Components\Toggle::make('auto_sync')
                            ->label('Sincronização Automática')
                            ->helperText('Sincroniza automaticamente novos arquivos quando criados')
                            ->live(),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('sync_documents')
                                    ->label('📄 Documentos Gerados')
                                    ->helperText('Documentos criados a partir de templates'),
                                
                                Forms\Components\Toggle::make('sync_reports')
                                    ->label('📊 Relatórios')
                                    ->helperText('Relatórios PDF exportados'),
                                
                                Forms\Components\Toggle::make('sync_invoices')
                                    ->label('💰 Faturas')
                                    ->helperText('Faturas em PDF'),
                                
                                Forms\Components\Toggle::make('sync_contracts')
                                    ->label('📝 Contratos')
                                    ->helperText('Contratos assinados'),
                            ]),
                    ]),

                Forms\Components\Section::make('Organização de Pastas')
                    ->description('Como os arquivos serão organizados no Google Drive')
                    ->schema([
                        Forms\Components\Radio::make('folder_structure')
                            ->label('Estrutura de Pastas')
                            ->options(GoogleDriveSetting::getFolderStructureOptions())
                            ->default('by_client')
                            ->columns(2),
                    ]),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                GoogleDriveActivityLog::query()
                    ->where('user_id', auth()->id())
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('action')
                    ->label('Ação')
                    ->badge()
                    ->formatStateUsing(fn ($state) => GoogleDriveActivityLog::getActionOptions()[$state] ?? $state)
                    ->color(fn ($state) => GoogleDriveActivityLog::getActionColors()[$state] ?? 'gray'),
                
                Tables\Columns\TextColumn::make('file_name')
                    ->label('Arquivo')
                    ->limit(40)
                    ->placeholder('-'),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(50)
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Nenhuma atividade registrada')
            ->emptyStateDescription('As atividades do Google Drive aparecerão aqui.')
            ->emptyStateIcon('heroicon-o-cloud');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connect')
                ->label('Conectar Google Drive')
                ->icon('heroicon-o-link')
                ->color('primary')
                ->url(fn () => $this->driveService->getAuthUrl())
                ->openUrlInNewTab()
                ->visible(fn () => !$this->settings?->is_connected),

            Action::make('disconnect')
                ->label('Desconectar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Desconectar Google Drive')
                ->modalDescription('Tem certeza que deseja desconectar sua conta do Google Drive? Os arquivos já sincronizados permanecerão no Drive.')
                ->action(function () {
                    $this->driveService->disconnect();
                    $this->settings->refresh();
                    $this->refreshStats();
                    
                    Notification::make()
                        ->title('Google Drive desconectado')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->settings?->is_connected),

            Action::make('sync_now')
                ->label('Sincronizar Agora')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action(function () {
                    $synced = $this->driveService->syncPendingFiles(50);
                    $this->refreshStats();
                    
                    Notification::make()
                        ->title("{$synced} arquivo(s) sincronizado(s)")
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->settings?->is_connected && $this->stats['pending_files'] > 0),

            // Admin-only action to import tokens for any user
            Action::make('import_tokens_admin')
                ->label('Importar Tokens (Admin)')
                ->icon('heroicon-o-download')
                ->modalHeading('Importar Tokens para Usuário')
                ->form([
                    Forms\Components\TextInput::make('email')
                        ->label('Email do Usuário')
                        ->required(),
                    Forms\Components\Textarea::make('access')
                        ->label('Access Token')
                        ->required()
                        ->rows(4),
                    Forms\Components\Textarea::make('refresh')
                        ->label('Refresh Token')
                        ->rows(3),
                    Forms\Components\TextInput::make('expires_at')
                        ->label('Expires At (datetime or seconds)')
                        ->helperText('Formato: Y-m-d H:i:s ou segundos a partir de agora ou timestamp')
                ])
                ->action(function (array $data) {
                    $user = \App\Models\User::where('email', $data['email'])->first();
                    if (!$user) {
                        Notification::make()->title('Usuário não encontrado')->danger()->send();
                        return;
                    }

                    $setting = \App\Models\GoogleDriveSetting::firstOrCreate([
                        'user_id' => $user->id,
                    ], [
                        'auto_sync' => false,
                        'is_connected' => false,
                    ]);

                    $expiresIn = null;
                    if (!empty($data['expires_at'])) {
                        if (is_numeric($data['expires_at'])) {
                            $int = (int) $data['expires_at'];
                            // treat as seconds if small, timestamp if large
                            if ($int > 1000000000) {
                                $expiresAt = \Carbon\Carbon::createFromTimestamp($int);
                                $expiresIn = $expiresAt->diffInSeconds(now());
                            } else {
                                $expiresIn = $int;
                            }
                        } else {
                            try {
                                $dt = \Carbon\Carbon::parse($data['expires_at']);
                                $expiresIn = $dt->diffInSeconds(now());
                            } catch (\Exception $e) {
                                Notification::make()->title('Formato de expires_at inválido')->danger()->send();
                                return;
                            }
                        }
                    }

                    $setting->updateTokens($data['access'], $data['refresh'] ?? null, $expiresIn);
                    $setting->update(['is_connected' => true]);

                    Notification::make()->title('Tokens importados com sucesso')->success()->send();
                })
                ->visible(fn () => auth()->user()?->hasRole('admin')),
        ];
    }

    public function saveSettings(): void
    {
        $data = $this->form->getState();

        $this->settings->update($data);

        Notification::make()
            ->title('Configurações salvas')
            ->success()
            ->send();
    }

    public function openDriveFolder(): void
    {
        if ($this->settings?->root_folder_id) {
            $url = "https://drive.google.com/drive/folders/{$this->settings->root_folder_id}";
            $this->dispatch('open-url', url: $url);
        }
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getViewData(): array
    {
        return [
            'settings' => $this->settings,
            'stats' => $this->stats,
            'isConnected' => $this->settings?->is_connected ?? false,
            'activeTab' => $this->activeTab,
            'recentFiles' => GoogleDriveFile::where('uploaded_by', auth()->id())
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }
}
