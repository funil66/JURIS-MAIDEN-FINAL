<?php

namespace App\Filament\Pages;

use App\Models\GoogleDriveSetting;
use App\Models\GoogleDriveFile;
use App\Services\GoogleDriveService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;

class AdminGoogleDriveListPage extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-cloud';
    protected static ?string $navigationLabel = 'Google Drive (Admin)';
    protected static ?string $title = 'Google Drive - Admin';
    protected static ?string $slug = 'admin-google-drive';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 31;

    protected static string $view = 'filament.pages.admin-google-drive-list';

    public function table(Table $table): Table
    {
        return $table
            ->query(GoogleDriveSetting::with('user'))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Usuário')->sortable()->searchable(),
                Tables\Columns\IconColumn::make('is_connected')->label('Conectado')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('root_folder_name')->label('Pasta Raiz')->limit(40),
                Tables\Columns\TextColumn::make('last_sync_at')->label('Última Sincronização')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('last_error')->label('Último Erro')->limit(80)->wrap(),
            ])
            ->actions([
                Action::make('import_tokens')
                    ->label('Importar Tokens')
                    ->modalHeading('Importar Tokens')
                    ->form([
                        Forms\Components\Textarea::make('access')->label('Access Token')->required()->rows(4),
                        Forms\Components\Textarea::make('refresh')->label('Refresh Token')->rows(3),
                        Forms\Components\TextInput::make('expires_at')->label('Expires At')->helperText('Y-m-d H:i:s or seconds from now or timestamp'),
                    ])
                    ->action(function (GoogleDriveSetting $record, array $data) {
                        $expiresIn = null;
                        if (!empty($data['expires_at'])) {
                            if (is_numeric($data['expires_at'])) {
                                $i = (int) $data['expires_at'];
                                if ($i > 1000000000) {
                                    $expiresAt = \Carbon\Carbon::createFromTimestamp($i);
                                    $expiresIn = $expiresAt->diffInSeconds(now());
                                } else {
                                    $expiresIn = $i;
                                }
                            } else {
                                try {
                                    $dt = \Carbon\Carbon::parse($data['expires_at']);
                                    $expiresIn = $dt->diffInSeconds(now());
                                } catch (\Exception $e) {
                                    Notification::make()->title('Formato expires_at inválido')->danger()->send();
                                    return;
                                }
                            }
                        }

                        $record->updateTokens($data['access'], $data['refresh'] ?? null, $expiresIn);
                        $record->update(['is_connected' => true]);

                        Notification::make()->title('Tokens importados')->success()->send();
                    })
                    ->visible(fn () => auth()->user()?->hasRole('admin')),

                Action::make('clear')
                    ->label('Limpar Tokens')
                    ->requiresConfirmation()
                    ->action(function (GoogleDriveSetting $record) {
                        $record->disconnect();
                        Notification::make()->title('Tokens limpos')->success()->send();
                    })
                    ->visible(fn () => auth()->user()?->hasRole('admin')),

                Action::make('sync')
                    ->label('Sincronizar')
                    ->action(function (GoogleDriveSetting $record) {
                        $service = new GoogleDriveService($record);
                        $synced = $service->syncPendingFiles(50);
                        Notification::make()->title("{$synced} arquivo(s) sincronizado(s)")->success()->send();
                    })->visible(fn () => auth()->user()?->hasRole('admin') && $record->is_connected),

                Action::make('open_folder')
                    ->label('Abrir Pasta')
                    ->url(fn (GoogleDriveSetting $record) => $record->root_folder_id ? "https://drive.google.com/drive/folders/{$record->root_folder_id}" : null)
                    ->openUrlInNewTab()
                    ->visible(fn () => $record->root_folder_id !== null),
            ])
            ->filters([])
            ->defaultSort('last_sync_at', 'desc')
            ->paginate(15);
    }

    protected function getActions(): array
    {
        return [
            Action::make('refresh_all')
                ->label('Forçar Sincronização (todos)')
                ->requiresConfirmation()
                ->action(function () {
                    $count = 0;
                    foreach (GoogleDriveSetting::connected()->get() as $s) {
                        $service = new GoogleDriveService($s);
                        $count += $service->syncPendingFiles(50);
                    }

                    Notification::make()->title("Total sincronizado: {$count}")->success()->send();
                })->visible(fn () => auth()->user()?->hasRole('admin')),
        ];
    }

    public function mount(): void
    {
        if (!auth()->user()?->hasRole('admin')) {
            abort(403);
        }
    }

    public function getViewData(): array
    {
        return [];
    }
}
