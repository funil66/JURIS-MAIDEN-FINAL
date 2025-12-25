<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiligenceResource\Pages;
use App\Filament\Resources\DiligenceResource\RelationManagers;
use App\Models\Diligence;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DiligenceResource extends Resource
{
    protected static ?string $model = Diligence::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Operacional';

    protected static ?string $modelLabel = 'Diligência';

    protected static ?string $pluralModelLabel = 'Diligências';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Seção: Identificação
                Forms\Components\Section::make('Identificação')
                    ->description('Dados básicos da diligência')
                    ->icon('heroicon-o-truck')
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

                        Forms\Components\Select::make('assigned_user_id')
                            ->label('Responsável')
                            ->relationship('assignedUser', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->id()),

                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->placeholder('Ex: Retirada de certidão no Fórum Central'),

                        Forms\Components\Textarea::make('objective')
                            ->label('Objetivo')
                            ->rows(2)
                            ->columnSpanFull()
                            ->placeholder('Descreva o objetivo da diligência'),
                    ]),

                // Seção: Classificação
                Forms\Components\Section::make('Classificação')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->options(Diligence::getTypeOptions())
                            ->default('forum_visit')
                            ->required()
                            ->native(false)
                            ->searchable(),

                        Forms\Components\Select::make('priority')
                            ->label('Prioridade')
                            ->options(Diligence::getPriorityOptions())
                            ->default('normal')
                            ->native(false),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(Diligence::getStatusOptions())
                            ->default('pending')
                            ->required()
                            ->native(false),

                        Forms\Components\Toggle::make('is_billable')
                            ->label('Faturável')
                            ->default(true)
                            ->helperText('Cobrar do cliente'),
                    ]),

                // Seção: Agendamento
                Forms\Components\Section::make('Agendamento')
                    ->columns(4)
                    ->schema([
                        Forms\Components\DatePicker::make('scheduled_date')
                            ->label('Data')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\TimePicker::make('scheduled_time')
                            ->label('Hora Início')
                            ->native(false),

                        Forms\Components\TimePicker::make('scheduled_end_time')
                            ->label('Hora Término')
                            ->native(false),

                        Forms\Components\TextInput::make('estimated_duration_minutes')
                            ->label('Duração Estimada (min)')
                            ->numeric()
                            ->minValue(1)
                            ->suffix('min'),
                    ]),

                // Seção: Local
                Forms\Components\Section::make('Local')
                    ->collapsible()
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('location_name')
                            ->label('Nome do Local')
                            ->maxLength(255)
                            ->placeholder('Ex: Fórum Central de São Paulo'),

                        Forms\Components\TextInput::make('location_address')
                            ->label('Endereço')
                            ->maxLength(500)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('location_city')
                            ->label('Cidade')
                            ->maxLength(100),

                        Forms\Components\Select::make('location_state')
                            ->label('UF')
                            ->options(\App\Models\Process::getStateOptions())
                            ->native(false)
                            ->searchable(),

                        Forms\Components\TextInput::make('location_zip')
                            ->label('CEP')
                            ->maxLength(10)
                            ->mask('99999-999'),
                    ]),

                // Seção: Contato no Local
                Forms\Components\Section::make('Contato no Local')
                    ->collapsible()
                    ->collapsed()
                    ->columns(4)
                    ->schema([
                        Forms\Components\TextInput::make('contact_name')
                            ->label('Nome')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('contact_phone')
                            ->label('Telefone')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('contact_email')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('contact_department')
                            ->label('Setor/Departamento')
                            ->maxLength(100),
                    ]),

                // Seção: Custos
                Forms\Components\Section::make('Custos')
                    ->collapsible()
                    ->columns(4)
                    ->schema([
                        Forms\Components\TextInput::make('estimated_cost')
                            ->label('Custo Estimado')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0),

                        Forms\Components\TextInput::make('mileage_km')
                            ->label('Quilometragem')
                            ->numeric()
                            ->suffix('km')
                            ->default(0),

                        Forms\Components\TextInput::make('mileage_cost')
                            ->label('Custo km')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0),

                        Forms\Components\TextInput::make('parking_cost')
                            ->label('Estacionamento')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0),

                        Forms\Components\TextInput::make('toll_cost')
                            ->label('Pedágios')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0),

                        Forms\Components\TextInput::make('transport_cost')
                            ->label('Transporte')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0),

                        Forms\Components\TextInput::make('other_costs')
                            ->label('Outros Custos')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0),

                        Forms\Components\TextInput::make('actual_cost')
                            ->label('Custo Real Total')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->helperText('Preenchido após conclusão'),
                    ]),

                // Seção: Resultado (para edição)
                Forms\Components\Section::make('Resultado')
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn (string $operation): bool => $operation === 'edit')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('was_successful')
                            ->label('Foi Bem Sucedida')
                            ->live(),

                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Concluída em')
                            ->native(false),

                        Forms\Components\Textarea::make('result')
                            ->label('Descrição do Resultado')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('failure_reason')
                            ->label('Motivo da Falha')
                            ->rows(2)
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('was_successful') === false),
                    ]),

                // Seção: Observações
                Forms\Components\Section::make('Observações')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Descrição Detalhada')
                            ->rows(3),

                        Forms\Components\Textarea::make('notes')
                            ->label('Observações')
                            ->rows(3),

                        Forms\Components\Textarea::make('internal_notes')
                            ->label('Notas Internas')
                            ->rows(2)
                            ->columnSpanFull()
                            ->helperText('Visível apenas internamente'),

                        Forms\Components\Hidden::make('created_by_user_id')
                            ->default(fn () => auth()->id()),
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

                Tables\Columns\TextColumn::make('priority')
                    ->label('')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? match ($state) {
                        'urgent' => '!!!',
                        'high' => '!!',
                        'normal' => '',
                        'low' => '',
                        default => '',
                    } : '')
                    ->color(fn (?string $state): string => match ($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        default => 'gray',
                    })
                    ->visible(fn ($state) => in_array($state, ['urgent', 'high']))
                    ->width(30),

                Tables\Columns\TextColumn::make('scheduled_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record?->is_overdue ? 'danger' : null),

                Tables\Columns\TextColumn::make('scheduled_time')
                    ->label('Hora')
                    ->time('H:i')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (Diligence::getTypeOptions()[$state] ?? $state) : '-')
                    ->color(fn (?string $state): string => match ($state) {
                        'hearing', 'court_hearing' => 'danger',
                        'forum_visit', 'registry_visit' => 'info',
                        'document_pickup', 'document_delivery' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->limit(40)
                    ->searchable()
                    ->tooltip(fn ($record) => $record?->title),

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

                Tables\Columns\TextColumn::make('location_name')
                    ->label('Local')
                    ->limit(25)
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Responsável')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (Diligence::getStatusOptions()[$state] ?? $state) : '-')
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'scheduled' => 'info',
                        'in_progress' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'rescheduled' => 'gray',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Custo')
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_billable')
                    ->label('Fat.')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(Diligence::getTypeOptions())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Diligence::getStatusOptions())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Prioridade')
                    ->options(Diligence::getPriorityOptions()),

                Tables\Filters\SelectFilter::make('assigned_user_id')
                    ->label('Responsável')
                    ->relationship('assignedUser', 'name'),

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

                Tables\Filters\TernaryFilter::make('is_reimbursed')
                    ->label('Reembolsada'),

                Tables\Filters\Filter::make('today')
                    ->label('Hoje')
                    ->query(fn (Builder $query) => $query->today()),

                Tables\Filters\Filter::make('this_week')
                    ->label('Esta Semana')
                    ->query(fn (Builder $query) => $query->thisWeek()),

                Tables\Filters\Filter::make('overdue')
                    ->label('Atrasadas')
                    ->query(fn (Builder $query) => $query->overdue()),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('start')
                        ->label('Iniciar')
                        ->icon('heroicon-o-play')
                        ->color('primary')
                        ->visible(fn ($record) => in_array($record->status, ['pending', 'scheduled']))
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->start()),
                    Tables\Actions\Action::make('complete')
                        ->label('Concluir')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'in_progress')
                        ->form([
                            Forms\Components\Toggle::make('was_successful')
                                ->label('Foi Bem Sucedida')
                                ->default(true),
                            Forms\Components\Textarea::make('result')
                                ->label('Resultado')
                                ->rows(3),
                        ])
                        ->action(fn ($record, array $data) => $record->complete($data['was_successful'], $data['result'])),
                    Tables\Actions\Action::make('cancel')
                        ->label('Cancelar')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => in_array($record->status, Diligence::getActiveStatuses()))
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Motivo')
                                ->rows(2),
                        ])
                        ->action(fn ($record, array $data) => $record->cancel($data['reason'])),
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
                ]),
            ])
            ->defaultSort('scheduled_date', 'asc');
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
            'index' => Pages\ListDiligences::route('/'),
            'create' => Pages\CreateDiligence::route('/create'),
            'view' => Pages\ViewDiligence::route('/{record}'),
            'edit' => Pages\EditDiligence::route('/{record}/edit'),
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
        // Mostra quantidade de diligências de hoje ou atrasadas
        $today = static::getModel()::today()
            ->whereIn('status', Diligence::getActiveStatuses())
            ->count();
        $overdue = static::getModel()::overdue()->count();

        $total = $today + $overdue;
        return $total > 0 ? (string) $total : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $overdue = static::getModel()::overdue()->count();
        return $overdue > 0 ? 'danger' : 'info';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['uid', 'title', 'location_name', 'process.title', 'client.name'];
    }
}
