<?php

namespace App\Filament\Resources\ProcessResource\RelationManagers;

use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

    protected static ?string $title = 'Serviços';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('service_type_id')
                            ->label('Tipo de Serviço')
                            ->relationship('serviceType', 'name')
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nome')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->label('Descrição')
                                    ->rows(2),
                                Forms\Components\TextInput::make('default_value')
                                    ->label('Valor Padrão')
                                    ->numeric()
                                    ->prefix('R$'),
                            ]),

                        Forms\Components\Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'name')
                            ->default(fn () => $this->ownerRecord->client_id)
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pendente',
                                'in_progress' => 'Em Andamento',
                                'completed' => 'Concluído',
                                'cancelled' => 'Cancelado',
                            ])
                            ->default('pending')
                            ->required()
                            ->native(false),

                        Forms\Components\DateTimePicker::make('scheduled_datetime')
                            ->label('Agendado para')
                            ->native(false),

                        Forms\Components\DateTimePicker::make('completion_date')
                            ->label('Concluído em')
                            ->native(false),

                        Forms\Components\TextInput::make('value')
                            ->label('Valor')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Duração (min)')
                            ->numeric()
                            ->minValue(0),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Observações Internas')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->label('ID')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->limit(40)
                    ->searchable()
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('serviceType.name')
                    ->label('Tipo')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? match ($state) {
                        'pending' => 'Pendente',
                        'in_progress' => 'Em Andamento',
                        'completed' => 'Concluído',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    } : '-')
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('value')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('scheduled_datetime')
                    ->label('Agendado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('completion_date')
                    ->label('Concluído')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pendente',
                        'in_progress' => 'Em Andamento',
                        'completed' => 'Concluído',
                        'cancelled' => 'Cancelado',
                    ]),

                Tables\Filters\SelectFilter::make('service_type_id')
                    ->label('Tipo')
                    ->relationship('serviceType', 'name'),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Garante que o client_id está preenchido
                        if (empty($data['client_id'])) {
                            $data['client_id'] = $this->ownerRecord->client_id;
                        }
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.funil.resources.services.view', $record)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('scheduled_datetime', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
