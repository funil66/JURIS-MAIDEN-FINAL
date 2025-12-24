<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourtMovementResource\Pages;
use App\Models\Court;
use App\Models\CourtMovement;
use App\Models\Process;
use App\Services\CourtApiService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CourtMovementResource extends Resource
{
    protected static ?string $model = CourtMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationLabel = 'Movimentações API';

    protected static ?string $modelLabel = 'Movimentação API';

    protected static ?string $pluralModelLabel = 'Movimentações API';

    protected static ?string $navigationGroup = 'Jurídico';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações da Movimentação')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('court_id')
                            ->label('Tribunal')
                            ->relationship('court', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('process_number')
                            ->label('Número do Processo')
                            ->required()
                            ->maxLength(50),

                        Forms\Components\DateTimePicker::make('movement_date')
                            ->label('Data/Hora')
                            ->required()
                            ->displayFormat('d/m/Y H:i'),

                        Forms\Components\TextInput::make('movement_code')
                            ->label('Código')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('movement_name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(200)
                            ->columnSpan('full'),

                        Forms\Components\Textarea::make('movement_description')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpan('full'),

                        Forms\Components\TextInput::make('court_origin')
                            ->label('Vara/Origem')
                            ->maxLength(200),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(CourtMovement::STATUSES)
                            ->required(),
                    ]),

                Forms\Components\Section::make('Vinculação')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('process_id')
                            ->label('Processo (Sistema)')
                            ->relationship('process', 'number')
                            ->searchable()
                            ->preload()
                            ->placeholder('Vincular a um processo'),

                        Forms\Components\Select::make('proceeding_id')
                            ->label('Andamento Criado')
                            ->relationship('proceeding', 'uid')
                            ->searchable()
                            ->preload()
                            ->disabled()
                            ->placeholder('Será preenchido ao importar'),
                    ]),

                Forms\Components\Section::make('Dados Brutos')
                    ->collapsed()
                    ->schema([
                        Forms\Components\KeyValue::make('raw_data')
                            ->label('Dados da API')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('court.acronym')
                    ->label('Tribunal')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('process_number')
                    ->label('Processo')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Número copiado!')
                    ->limit(25),

                Tables\Columns\TextColumn::make('movement_date')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('movement_name')
                    ->label('Movimentação')
                    ->searchable()
                    ->wrap()
                    ->limit(50),

                Tables\Columns\TextColumn::make('movement_code')
                    ->label('Código')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => CourtMovement::STATUS_COLORS[$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => CourtMovement::STATUSES[$state] ?? $state)
                    ->sortable(),

                Tables\Columns\IconColumn::make('proceeding_id')
                    ->label('Importado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn (CourtMovement $record): bool => $record->proceeding_id !== null),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recebido em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('court_id')
                    ->label('Tribunal')
                    ->relationship('court', 'acronym')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(CourtMovement::STATUSES)
                    ->multiple(),

                Tables\Filters\Filter::make('not_imported')
                    ->label('Não Importados')
                    ->query(fn (Builder $query): Builder => $query->whereNull('proceeding_id')),

                Tables\Filters\Filter::make('imported')
                    ->label('Importados')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('proceeding_id')),

                Tables\Filters\Filter::make('movement_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('De'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date): Builder => $q->whereDate('movement_date', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date): Builder => $q->whereDate('movement_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('import')
                        ->label('Importar')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->visible(fn (CourtMovement $record): bool => 
                            $record->status !== CourtMovement::STATUS_IMPORTED &&
                            $record->status !== CourtMovement::STATUS_IGNORED
                        )
                        ->form([
                            Forms\Components\Select::make('process_id')
                                ->label('Processo')
                                ->options(function (CourtMovement $record) {
                                    // Buscar processos que podem corresponder
                                    $cleanNumber = preg_replace('/[^0-9]/', '', $record->process_number);
                                    
                                    return Process::query()
                                        ->where('number', 'like', "%{$cleanNumber}%")
                                        ->limit(10)
                                        ->pluck('number', 'id');
                                })
                                ->searchable()
                                ->required()
                                ->helperText('Selecione o processo para vincular esta movimentação'),
                        ])
                        ->action(function (CourtMovement $record, array $data): void {
                            $service = app(CourtApiService::class);
                            $proceeding = $service->importMovementToProceeding($record, $data['process_id']);

                            if ($proceeding) {
                                Notification::make()
                                    ->title('Movimentação Importada')
                                    ->body("Andamento {$proceeding->uid} criado com sucesso!")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Erro ao Importar')
                                    ->body('Não foi possível criar o andamento.')
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('ignore')
                        ->label('Ignorar')
                        ->icon('heroicon-o-eye-slash')
                        ->color('gray')
                        ->visible(fn (CourtMovement $record): bool => 
                            $record->status !== CourtMovement::STATUS_IGNORED
                        )
                        ->requiresConfirmation()
                        ->action(fn (CourtMovement $record) => $record->update(['status' => CourtMovement::STATUS_IGNORED])),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('bulk_import')
                        ->label('Importar Selecionados')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (Collection $records): void {
                            $service = app(CourtApiService::class);
                            $ids = $records->pluck('id')->toArray();
                            $result = $service->importMultipleMovements($ids);

                            Notification::make()
                                ->title('Importação em Lote')
                                ->body("Importados: {$result['imported']}, Falhas: {$result['failed']}")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('bulk_ignore')
                        ->label('Ignorar Selecionados')
                        ->icon('heroicon-o-eye-slash')
                        ->color('gray')
                        ->action(fn (Collection $records) => 
                            $records->each->update(['status' => CourtMovement::STATUS_IGNORED])
                        )
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('mark_pending')
                        ->label('Marcar como Pendente')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->action(fn (Collection $records) => 
                            $records->each->update(['status' => CourtMovement::STATUS_PENDING])
                        )
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('movement_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourtMovements::route('/'),
            'create' => Pages\CreateCourtMovement::route('/create'),
            'view' => Pages\ViewCourtMovement::route('/{record}'),
            'edit' => Pages\EditCourtMovement::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::where('status', CourtMovement::STATUS_PENDING)->count();
        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
