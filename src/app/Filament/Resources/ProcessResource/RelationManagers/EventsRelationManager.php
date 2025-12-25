<?php

namespace App\Filament\Resources\ProcessResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    protected static ?string $title = 'Compromissos';

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

                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'hearing' => 'Audiência',
                                'meeting' => 'Reunião',
                                'deadline' => 'Prazo',
                                'task' => 'Tarefa',
                                'reminder' => 'Lembrete',
                                'other' => 'Outro',
                            ])
                            ->default('task')
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'name')
                            ->default(fn () => $this->ownerRecord->client_id)
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->preload(),

                        Forms\Components\DateTimePicker::make('start_at')
                            ->label('Início')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y H:i'),

                        Forms\Components\DateTimePicker::make('end_at')
                            ->label('Término')
                            ->native(false)
                            ->displayFormat('d/m/Y H:i')
                            ->after('start_at'),

                        Forms\Components\Toggle::make('is_all_day')
                            ->label('Dia Inteiro')
                            ->default(false),

                        Forms\Components\Toggle::make('is_recurring')
                            ->label('Recorrente')
                            ->default(false),

                        Forms\Components\TextInput::make('location')
                            ->label('Local')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'scheduled' => 'Agendado',
                                'confirmed' => 'Confirmado',
                                'completed' => 'Concluído',
                                'cancelled' => 'Cancelado',
                                'rescheduled' => 'Remarcado',
                            ])
                            ->default('scheduled')
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('priority')
                            ->label('Prioridade')
                            ->options([
                                'low' => 'Baixa',
                                'normal' => 'Normal',
                                'high' => 'Alta',
                                'urgent' => 'Urgente',
                            ])
                            ->default('normal')
                            ->native(false),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Observações')
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

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? match ($state) {
                        'hearing' => 'Audiência',
                        'meeting' => 'Reunião',
                        'deadline' => 'Prazo',
                        'task' => 'Tarefa',
                        'reminder' => 'Lembrete',
                        'other' => 'Outro',
                        default => $state,
                    } : '-')
                    ->color(fn (string $state): string => match ($state) {
                        'hearing' => 'danger',
                        'meeting' => 'info',
                        'deadline' => 'warning',
                        'task' => 'success',
                        'reminder' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->limit(40)
                    ->searchable()
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('start_at')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Local')
                    ->limit(30)
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? match ($state) {
                        'scheduled' => 'Agendado',
                        'confirmed' => 'Confirmado',
                        'completed' => 'Concluído',
                        'cancelled' => 'Cancelado',
                        'rescheduled' => 'Remarcado',
                        default => $state,
                    } : '-')
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'warning',
                        'confirmed' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'rescheduled' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioridade')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'low' => 'Baixa',
                        'normal' => 'Normal',
                        'high' => 'Alta',
                        'urgent' => 'Urgente',
                        default => 'Normal',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'low' => 'gray',
                        'normal' => 'info',
                        'high' => 'warning',
                        'urgent' => 'danger',
                        default => 'info',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'hearing' => 'Audiência',
                        'meeting' => 'Reunião',
                        'deadline' => 'Prazo',
                        'task' => 'Tarefa',
                        'reminder' => 'Lembrete',
                        'other' => 'Outro',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'scheduled' => 'Agendado',
                        'confirmed' => 'Confirmado',
                        'completed' => 'Concluído',
                        'cancelled' => 'Cancelado',
                        'rescheduled' => 'Remarcado',
                    ]),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        if (empty($data['client_id'])) {
                            $data['client_id'] = $this->ownerRecord->client_id;
                        }
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.funil.resources.events.view', $record)),
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
            ->defaultSort('start_at', 'asc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
