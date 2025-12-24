<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeadlineResource\Pages;
use App\Filament\Resources\DeadlineResource\RelationManagers;
use App\Models\Deadline;
use App\Models\DeadlineType;
use App\Models\Process;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeadlineResource extends Resource
{
    protected static ?string $model = Deadline::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Prazos';

    protected static ?string $modelLabel = 'Prazo';

    protected static ?string $pluralModelLabel = 'Prazos';

    protected static ?string $navigationGroup = 'Jurídico';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationBadge(): ?string
    {
        $overdue = static::getModel()::overdue()->count();
        $dueToday = static::getModel()::dueToday()->count();
        $total = $overdue + $dueToday;
        
        return $total > 0 ? (string) $total : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $overdue = static::getModel()::overdue()->count();
        
        if ($overdue > 0) {
            return 'danger';
        }
        
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identificação')
                    ->description('Informações básicas do prazo')
                    ->schema([
                        Forms\Components\TextInput::make('uid')
                            ->label('UID')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        Forms\Components\Select::make('process_id')
                            ->label('Processo')
                            ->relationship('process', 'title')
                            ->getOptionLabelFromRecordUsing(fn (Process $record) => "{$record->uid} - {$record->title}")
                            ->searchable(['uid', 'title', 'cnj_number'])
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('proceeding_id', null)),

                        Forms\Components\Select::make('proceeding_id')
                            ->label('Andamento Origem')
                            ->relationship(
                                'proceeding',
                                'title',
                                fn (Builder $query, Forms\Get $get) => $query->where('process_id', $get('process_id'))
                            )
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => filled($get('process_id'))),

                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Tipo e Configuração')
                    ->description('Selecione o tipo de prazo ou configure manualmente')
                    ->schema([
                        Forms\Components\Select::make('deadline_type_id')
                            ->label('Tipo de Prazo')
                            ->relationship('deadlineType', 'name')
                            ->getOptionLabelFromRecordUsing(fn (DeadlineType $record) => "{$record->code} - {$record->name}")
                            ->searchable(['code', 'name'])
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $type = DeadlineType::find($state);
                                    if ($type) {
                                        $set('days_count', $type->default_days);
                                        $set('counting_type', $type->counting_type);
                                        $set('priority', $type->priority);
                                        if (!$set('title')) {
                                            $set('title', $type->name);
                                        }
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('days_count')
                            ->label('Quantidade de Dias')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(15),

                        Forms\Components\Select::make('counting_type')
                            ->label('Tipo de Contagem')
                            ->options(Deadline::COUNTING_TYPES)
                            ->required()
                            ->default(Deadline::COUNTING_BUSINESS_DAYS),

                        Forms\Components\Select::make('priority')
                            ->label('Prioridade')
                            ->options(Deadline::PRIORITIES)
                            ->required()
                            ->default(Deadline::PRIORITY_NORMAL),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Datas')
                    ->description('Datas de início e vencimento')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Data de Início')
                            ->required()
                            ->default(now())
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                if ($state && $get('days_count')) {
                                    $dueDate = Deadline::calculateDueDate(
                                        \Carbon\Carbon::parse($state),
                                        (int) $get('days_count'),
                                        $get('counting_type') ?? Deadline::COUNTING_BUSINESS_DAYS
                                    );
                                    $set('due_date', $dueDate->format('Y-m-d'));
                                }
                            }),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Data de Vencimento')
                            ->required()
                            ->afterOrEqual('start_date'),

                        Forms\Components\DatePicker::make('original_due_date')
                            ->label('Vencimento Original')
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->original_due_date),

                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Data de Cumprimento')
                            ->visible(fn ($record) => $record && $record->isCompleted()),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Responsável e Status')
                    ->schema([
                        Forms\Components\Select::make('assigned_user_id')
                            ->label('Responsável')
                            ->relationship('assignedUser', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->id()),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(Deadline::STATUSES)
                            ->required()
                            ->default(Deadline::STATUS_PENDING)
                            ->disabled(fn ($record) => $record === null),

                        Forms\Components\TextInput::make('document_protocol')
                            ->label('Protocolo')
                            ->maxLength(100)
                            ->visible(fn ($record) => $record && $record->status === Deadline::STATUS_COMPLETED),

                        Forms\Components\Textarea::make('completion_notes')
                            ->label('Observações')
                            ->rows(3)
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
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->status_color)
                    ->weight('bold')
                    ->description(fn ($record) => static::getDaysRemainingDescription($record)),

                Tables\Columns\TextColumn::make('title')
                    ->label('Prazo')
                    ->limit(40)
                    ->searchable()
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('process.uid')
                    ->label('Processo')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->url(fn ($record) => $record->process ? route('filament.funil.resources.processes.view', $record->process) : null),

                Tables\Columns\TextColumn::make('process.client.name')
                    ->label('Cliente')
                    ->limit(20)
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioridade')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'low' => 'gray',
                        'normal' => 'info',
                        'high' => 'warning',
                        'critical' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => Deadline::PRIORITIES[$state] ?? $state),

                Tables\Columns\TextColumn::make('counting_type')
                    ->label('Contagem')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'business_days' => 'Úteis',
                        'calendar_days' => 'Corridos',
                        'continuous' => 'Contínuo',
                        default => $state,
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state, $record) => $record->status_color)
                    ->formatStateUsing(fn ($state) => Deadline::STATUSES[$state] ?? $state),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Responsável')
                    ->limit(15)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('deadlineType.code')
                    ->label('Tipo')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('due_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Deadline::STATUSES)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Prioridade')
                    ->options(Deadline::PRIORITIES),

                Tables\Filters\SelectFilter::make('counting_type')
                    ->label('Tipo de Contagem')
                    ->options(Deadline::COUNTING_TYPES),

                Tables\Filters\SelectFilter::make('assigned_user_id')
                    ->label('Responsável')
                    ->relationship('assignedUser', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('overdue')
                    ->label('Vencidos')
                    ->query(fn (Builder $query) => $query->overdue()),

                Tables\Filters\Filter::make('due_today')
                    ->label('Vencem Hoje')
                    ->query(fn (Builder $query) => $query->dueToday()),

                Tables\Filters\Filter::make('due_this_week')
                    ->label('Esta Semana')
                    ->query(fn (Builder $query) => $query->dueSoon(7)),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('complete')
                        ->label('Marcar Cumprido')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Marcar Prazo como Cumprido')
                        ->form([
                            Forms\Components\TextInput::make('protocol')
                                ->label('Número do Protocolo')
                                ->maxLength(100),
                            Forms\Components\Textarea::make('notes')
                                ->label('Observações')
                                ->rows(3),
                        ])
                        ->action(fn (Deadline $record, array $data) => $record->complete($data['notes'] ?? null, $data['protocol'] ?? null))
                        ->visible(fn (Deadline $record) => $record->isPending()),

                    Tables\Actions\Action::make('extend')
                        ->label('Prorrogar')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->form([
                            Forms\Components\DatePicker::make('new_due_date')
                                ->label('Nova Data de Vencimento')
                                ->required()
                                ->afterOrEqual('today'),
                            Forms\Components\Textarea::make('reason')
                                ->label('Motivo da Prorrogação')
                                ->rows(2),
                        ])
                        ->action(fn (Deadline $record, array $data) => $record->extend(
                            \Carbon\Carbon::parse($data['new_due_date']),
                            $data['reason'] ?? null
                        ))
                        ->visible(fn (Deadline $record) => $record->isPending()),

                    Tables\Actions\Action::make('missed')
                        ->label('Marcar Perdido')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Marcar Prazo como Perdido')
                        ->form([
                            Forms\Components\Textarea::make('notes')
                                ->label('Justificativa')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(fn (Deadline $record, array $data) => $record->markAsMissed($data['notes']))
                        ->visible(fn (Deadline $record) => $record->isPending()),

                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Identificação')
                    ->schema([
                        Infolists\Components\TextEntry::make('uid')
                            ->label('UID')
                            ->badge()
                            ->color('gray')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('title')
                            ->label('Título'),

                        Infolists\Components\TextEntry::make('process.uid')
                            ->label('Processo')
                            ->badge()
                            ->color('info')
                            ->url(fn ($record) => $record->process ? route('filament.funil.resources.processes.view', $record->process) : null),

                        Infolists\Components\TextEntry::make('process.client.name')
                            ->label('Cliente'),

                        Infolists\Components\TextEntry::make('description')
                            ->label('Descrição')
                            ->columnSpanFull(),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Datas e Prazos')
                    ->schema([
                        Infolists\Components\TextEntry::make('start_date')
                            ->label('Data de Início')
                            ->date('d/m/Y'),

                        Infolists\Components\TextEntry::make('due_date')
                            ->label('Data de Vencimento')
                            ->date('d/m/Y')
                            ->color(fn ($record) => $record->status_color)
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('original_due_date')
                            ->label('Vencimento Original')
                            ->date('d/m/Y')
                            ->visible(fn ($record) => $record->original_due_date),

                        Infolists\Components\TextEntry::make('days_remaining')
                            ->label('Dias Restantes')
                            ->getStateUsing(fn ($record) => $record->isPending() 
                                ? ($record->days_remaining >= 0 ? $record->days_remaining . ' dias' : abs($record->days_remaining) . ' dias vencido')
                                : '-'
                            )
                            ->color(fn ($record) => $record->status_color),

                        Infolists\Components\TextEntry::make('days_count')
                            ->label('Quantidade de Dias'),

                        Infolists\Components\TextEntry::make('counting_type')
                            ->label('Tipo de Contagem')
                            ->formatStateUsing(fn ($state) => Deadline::COUNTING_TYPES[$state] ?? $state),

                        Infolists\Components\TextEntry::make('completed_at')
                            ->label('Cumprido em')
                            ->dateTime('d/m/Y H:i')
                            ->visible(fn ($record) => $record->completed_at),
                    ])
                    ->columns(4),

                Infolists\Components\Section::make('Status e Responsável')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($record) => $record->status_color)
                            ->formatStateUsing(fn ($state) => Deadline::STATUSES[$state] ?? $state),

                        Infolists\Components\TextEntry::make('priority')
                            ->label('Prioridade')
                            ->badge()
                            ->color(fn ($record) => $record->priority_color)
                            ->formatStateUsing(fn ($state) => Deadline::PRIORITIES[$state] ?? $state),

                        Infolists\Components\TextEntry::make('assignedUser.name')
                            ->label('Responsável'),

                        Infolists\Components\TextEntry::make('createdByUser.name')
                            ->label('Criado por'),

                        Infolists\Components\TextEntry::make('document_protocol')
                            ->label('Protocolo')
                            ->visible(fn ($record) => $record->document_protocol),

                        Infolists\Components\TextEntry::make('completion_notes')
                            ->label('Observações')
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record->completion_notes),
                    ])
                    ->columns(4),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AlertsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeadlines::route('/'),
            'create' => Pages\CreateDeadline::route('/create'),
            'calculate' => Pages\CalculateDeadline::route('/calculate'),
            'view' => Pages\ViewDeadline::route('/{record}'),
            'edit' => Pages\EditDeadline::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static function getDaysRemainingDescription($record): string
    {
        if (!$record->isPending()) {
            return Deadline::STATUSES[$record->status] ?? '';
        }

        $days = $record->days_remaining;
        
        if ($days < 0) {
            return abs($days) . ' dia(s) vencido!';
        }
        
        if ($days === 0) {
            return 'VENCE HOJE!';
        }
        
        if ($days === 1) {
            return 'Vence amanhã';
        }
        
        return "Em $days dias";
    }
}
