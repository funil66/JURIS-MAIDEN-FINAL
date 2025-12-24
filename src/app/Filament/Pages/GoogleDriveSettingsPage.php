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
    public ?GoogleDriveService $driveService = null;
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
