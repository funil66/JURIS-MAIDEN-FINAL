<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimeEntryResource\Pages;
use App\Filament\Resources\TimeEntryResource\RelationManagers;
use App\Models\TimeEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TimeEntryResource extends Resource
{
    protected static ?string $model = TimeEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Operacional';

    protected static ?string $modelLabel = 'Lançamento de Tempo';

    protected static ?string $pluralModelLabel = 'Lançamentos de Tempo';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Seção: Identificação
                Forms\Components\Section::make('Identificação')
                    ->description('Informe o trabalho realizado')
                    ->icon('heroicon-o-clock')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('process_id')
                            ->label('Processo')
                            ->relationship('process', 'title')
                            ->searchable()
                            ->preload()
                            ->placeholder('Selecione o processo')
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if ($state) {
                                    $process = \App\Models\Process::find($state);
                                    if ($process) {
                                        $set('client_id', $process->client_id);
                                    }
                                }
                            }),

                        Forms\Components\Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('user_id')
                            ->label('Colaborador')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->id())
                            ->required(),

                        Forms\Components\TextInput::make('description')
                            ->label('Descrição do Trabalho')
                            ->required()
                            ->maxLength(1000)
                            ->columnSpanFull()
                            ->placeholder('Descreva a atividade realizada'),
                    ]),

                // Seção: Classificação
                Forms\Components\Section::make('Classificação')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Select::make('activity_type')
                            ->label('Tipo de Atividade')
                            ->options(TimeEntry::getActivityTypeOptions())
                            ->default('other')
                            ->required()
                            ->native(false)
                            ->searchable(),

                        Forms\Components\Select::make('service_id')
                            ->label('Serviço Relacionado')
                            ->relationship(
                                'service',
                                'title',
                                fn (Builder $query, Get $get) => $get('process_id') 
                                    ? $query->where('process_id', $get('process_id'))
                                    : $query
                            )
                            ->searchable()
                            ->preload()
                            ->placeholder('Nenhum'),

                        Forms\Components\Select::make('proceeding_id')
                            ->label('Andamento Relacionado')
                            ->relationship(
                                'proceeding',
                                'title',
                                fn (Builder $query, Get $get) => $get('process_id')
                                    ? $query->where('process_id', $get('process_id'))->latest('proceeding_date')->limit(50)
                                    : $query->latest('proceeding_date')->limit(50)
                            )
                            ->searchable()
                            ->preload()
                            ->placeholder('Nenhum'),

                        Forms\Components\Select::make('diligence_id')
                            ->label('Diligência Relacionada')
                            ->relationship(
                                'diligence',
                                'title',
                                fn (Builder $query, Get $get) => $get('process_id')
                                    ? $query->where('process_id', $get('process_id'))->latest('scheduled_date')->limit(50)
                                    : $query->latest('scheduled_date')->limit(50)
                            )
                            ->searchable()
                            ->preload()
                            ->placeholder('Nenhuma'),
                    ]),

                // Seção: Tempo
                Forms\Components\Section::make('Tempo')
                    ->columns(4)
                    ->schema([
                        Forms\Components\DatePicker::make('work_date')
                            ->label('Data do Trabalho')
                            ->required()
                            ->native(false)
                            ->default(now())
                            ->displayFormat('d/m/Y'),

                        Forms\Components\TimePicker::make('start_time')
                            ->label('Início')
                            ->native(false),

                        Forms\Components\TimePicker::make('end_time')
                            ->label('Término')
                            ->native(false)
                            ->after('start_time')
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                if ($state && $get('start_time')) {
                                    $start = \Carbon\Carbon::parse($get('start_time'));
                                    $end = \Carbon\Carbon::parse($state);
                                    $minutes = $start->diffInMinutes($end);
                                    if ($minutes > 0) {
                                        $set('duration_minutes', $minutes);
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Duração (minutos)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(30)
                            ->suffix('min')
                            ->helperText(fn (Get $get) => $get('duration_minutes') 
                                ? sprintf('%.2f horas', $get('duration_minutes') / 60)
                                : ''),

                        Forms\Components\Select::make('quick_duration')
                            ->label('Duração Rápida')
                            ->options(TimeEntry::getCommonDurations())
                            ->placeholder('Selecione')
                            ->columnSpan(2)
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?int $state) {
                                if ($state) {
                                    $set('duration_minutes', $state);
                                }
                            })
                            ->dehydrated(false),
                    ]),

                // Seção: Faturamento
                Forms\Components\Section::make('Faturamento')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Toggle::make('is_billable')
                            ->label('Faturável')
                            ->default(true)
                            ->live(),

                        Forms\Components\TextInput::make('hourly_rate')
                            ->label('Taxa Horária')
                            ->numeric()
                            ->prefix('R$')
                            ->visible(fn (Get $get) => $get('is_billable'))
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                if ($state && $get('duration_minutes')) {
                                    $hours = $get('duration_minutes') / 60;
                                    $set('total_amount', round($state * $hours, 2));
                                }
                            }),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Valor Total')
                            ->numeric()
                            ->prefix('R$')
                            ->visible(fn (Get $get) => $get('is_billable'))
                            ->disabled()
                            ->dehydrated(true),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(TimeEntry::getStatusOptions())
                            ->default('draft')
                            ->required()
                            ->native(false),
                    ]),

                // Seção: Observações
                Forms\Components\Section::make('Observações')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Observações')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->label('ID')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('work_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Colaborador')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('activity_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => TimeEntry::getActivityTypeOptions()[$state] ?? $state)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(40)
                    ->searchable()
                    ->tooltip(fn ($record) => $record->description),

                Tables\Columns\TextColumn::make('process.title')
                    ->label('Processo')
                    ->limit(25)
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->limit(20)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('formatted_duration')
                    ->label('Duração')
                    ->sortable(query: fn (Builder $query, string $direction) => 
                        $query->orderBy('duration_minutes', $direction)
                    ),

                Tables\Columns\IconColumn::make('is_running')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-s-play')
                    ->trueColor('success')
                    ->falseIcon('')
                    ->width(30),

                Tables\Columns\IconColumn::make('is_billable')
                    ->label('Fat.')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => TimeEntry::getStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'billed' => 'info',
                        'paid' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Colaborador')
                    ->relationship('user', 'name'),

                Tables\Filters\SelectFilter::make('activity_type')
                    ->label('Tipo de Atividade')
                    ->options(TimeEntry::getActivityTypeOptions())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(TimeEntry::getStatusOptions())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('process_id')
                    ->label('Processo')
                    ->relationship('process', 'title')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_billable')
                    ->label('Faturável'),

                Tables\Filters\Filter::make('today')
                    ->label('Hoje')
                    ->query(fn (Builder $query) => $query->today()),

                Tables\Filters\Filter::make('this_week')
                    ->label('Esta Semana')
                    ->query(fn (Builder $query) => $query->thisWeek()),

                Tables\Filters\Filter::make('this_month')
                    ->label('Este Mês')
                    ->query(fn (Builder $query) => $query->thisMonth()),

                Tables\Filters\Filter::make('running')
                    ->label('Com Timer Ativo')
                    ->query(fn (Builder $query) => $query->running()),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('startTimer')
                        ->label('Iniciar Timer')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->visible(fn ($record) => !$record->is_running && $record->status === 'draft')
                        ->action(fn ($record) => $record->startTimer()),
                    Tables\Actions\Action::make('stopTimer')
                        ->label('Parar Timer')
                        ->icon('heroicon-o-stop')
                        ->color('danger')
                        ->visible(fn ($record) => $record->is_running)
                        ->action(fn ($record) => $record->stopTimer()),
                    Tables\Actions\Action::make('submit')
                        ->label('Submeter')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->visible(fn ($record) => $record->status === 'draft')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->submit()),
                    Tables\Actions\Action::make('approve')
                        ->label('Aprovar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'submitted')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->approve()),
                    Tables\Actions\Action::make('reject')
                        ->label('Rejeitar')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => $record->status === 'submitted')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Motivo')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(fn ($record, array $data) => $record->reject($data['reason'])),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('submit')
                        ->label('Submeter Selecionados')
                        ->icon('heroicon-o-paper-airplane')
                        ->action(fn ($records) => $records->each->submit())
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('approve')
                        ->label('Aprovar Selecionados')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->approve())
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('work_date', 'desc');
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
            'index' => Pages\ListTimeEntries::route('/'),
            'create' => Pages\CreateTimeEntry::route('/create'),
            'view' => Pages\ViewTimeEntry::route('/{record}'),
            'edit' => Pages\EditTimeEntry::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        // Mostra timers ativos
        $running = static::getModel()::running()->count();
        return $running > 0 ? (string) $running : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['uid', 'description', 'process.title', 'client.name', 'user.name'];
    }
}
