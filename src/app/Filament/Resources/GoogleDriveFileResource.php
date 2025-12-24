<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoogleDriveFileResource\Pages;
use App\Models\GoogleDriveFile;
use App\Services\GoogleDriveService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GoogleDriveFileResource extends Resource
{
    protected static ?string $model = GoogleDriveFile::class;

    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-up';
    protected static ?string $navigationLabel = 'Arquivos do Drive';
    protected static ?string $modelLabel = 'Arquivo do Drive';
    protected static ?string $pluralModelLabel = 'Arquivos do Drive';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 31;
    protected static ?string $slug = 'google-drive-files';
    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['uid', 'name', 'drive_path'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'UID' => $record->uid,
            'Status' => $record->sync_status_badge,
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Arquivo')
                    ->schema([
                        Forms\Components\TextInput::make('uid')
                            ->label('UID')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('name')
                            ->label('Nome do Arquivo')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('mime_type')
                            ->label('Tipo MIME')
                            ->disabled(),

                        Forms\Components\TextInput::make('formatted_size')
                            ->label('Tamanho')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Caminhos')
                    ->schema([
                        Forms\Components\TextInput::make('local_path')
                            ->label('Caminho Local')
                            ->disabled(),

                        Forms\Components\TextInput::make('drive_path')
                            ->label('Caminho no Drive')
                            ->disabled(),

                        Forms\Components\TextInput::make('web_view_link')
                            ->label('Link de Visualização')
                            ->disabled()
                            ->url()
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('open')
                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                    ->url(fn ($state) => $state)
                                    ->openUrlInNewTab()
                                    ->visible(fn ($state) => filled($state))
                            ),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status de Sincronização')
                    ->schema([
                        Forms\Components\Select::make('sync_status')
                            ->label('Status')
                            ->options(GoogleDriveFile::getSyncStatusOptions())
                            ->disabled(),

                        Forms\Components\Select::make('sync_direction')
                            ->label('Direção')
                            ->options(GoogleDriveFile::getSyncDirectionOptions())
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('synced_at')
                            ->label('Sincronizado em')
                            ->disabled(),

                        Forms\Components\Textarea::make('error_message')
                            ->label('Mensagem de Erro')
                            ->disabled()
                            ->visible(fn ($record) => $record?->sync_status === 'failed')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->label('UID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('file_icon')
                    ->label('')
                    ->html()
                    ->getStateUsing(fn ($record) => "<span class='text-2xl'>{$record->file_icon}</span>"),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->name),

                Tables\Columns\TextColumn::make('formatted_size')
                    ->label('Tamanho')
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('size', $direction)),

                Tables\Columns\TextColumn::make('sync_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => GoogleDriveFile::getSyncStatusOptions()[$state] ?? $state)
                    ->color(fn ($state) => GoogleDriveFile::getSyncStatusColors()[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('fileable_type')
                    ->label('Tipo')
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'App\\Models\\GeneratedDocument' => '📄 Documento',
                            'App\\Models\\GeneratedReport' => '📊 Relatório',
                            'App\\Models\\Invoice' => '💰 Fatura',
                            'App\\Models\\Contract' => '📝 Contrato',
                            default => '📁 Arquivo',
                        };
                    }),

                Tables\Columns\TextColumn::make('synced_at')
                    ->label('Sincronizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Não sincronizado'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('sync_status')
                    ->label('Status')
                    ->options(GoogleDriveFile::getSyncStatusOptions()),

                Tables\Filters\SelectFilter::make('sync_direction')
                    ->label('Direção')
                    ->options(GoogleDriveFile::getSyncDirectionOptions()),

                Tables\Filters\Filter::make('pending')
                    ->label('Apenas Pendentes')
                    ->query(fn (Builder $query) => $query->pending())
                    ->toggle(),

                Tables\Filters\Filter::make('failed')
                    ->label('Apenas com Falha')
                    ->query(fn (Builder $query) => $query->failed())
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('sync')
                    ->label('Sincronizar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn ($record) => $record->sync_status !== 'synced')
                    ->action(function ($record) {
                        $service = new GoogleDriveService();
                        $success = $service->syncFile($record);

                        if ($success) {
                            Notification::make()
                                ->title('Arquivo sincronizado com sucesso')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Falha na sincronização')
                                ->body($record->fresh()->error_message)
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('view_drive')
                    ->label('Abrir no Drive')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn ($record) => $record->web_view_link)
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => filled($record->web_view_link)),

                Tables\Actions\ViewAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        // Deletar do Drive também se sincronizado
                        if ($record->google_file_id) {
                            $service = new GoogleDriveService();
                            $service->deleteFile($record->google_file_id);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('sync_selected')
                        ->label('Sincronizar Selecionados')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->action(function ($records) {
                            $service = new GoogleDriveService();
                            $synced = 0;

                            foreach ($records as $record) {
                                if ($service->syncFile($record)) {
                                    $synced++;
                                }
                            }

                            Notification::make()
                                ->title("{$synced} arquivo(s) sincronizado(s)")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nenhum arquivo do Drive')
            ->emptyStateDescription('Os arquivos sincronizados com o Google Drive aparecerão aqui.')
            ->emptyStateIcon('heroicon-o-cloud');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoogleDriveFiles::route('/'),
            'view' => Pages\ViewGoogleDriveFile::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = GoogleDriveFile::pending()->count();
        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
