<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProceedingResource\Pages;
use App\Filament\Resources\ProceedingResource\RelationManagers;
use App\Models\Proceeding;
use App\Models\Process;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProceedingResource extends Resource
{
    protected static ?string $model = Proceeding::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationGroup = 'Jurídico';

    protected static ?string $modelLabel = 'Andamento';

    protected static ?string $pluralModelLabel = 'Andamentos';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Seção: Identificação
                Forms\Components\Section::make('Identificação')
                    ->description('Dados do andamento processual')
                    ->icon('heroicon-o-queue-list')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('process_id')
                            ->label('Processo')
                            ->relationship('process', 'title')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2)
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if ($state) {
                                    $process = Process::find($state);
                                    if ($process) {
                                        $set('_process_info', "{$process->uid} - {$process->client->name}");
                                    }
                                }
                            }),

                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->options(Proceeding::getTypeOptions())
                            ->default('movement')
                            ->required()
                            ->native(false)
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if (in_array($state, Proceeding::getTypesWithDeadline())) {
                                    $set('has_deadline', true);
                                }
                                if (in_array($state, Proceeding::getImportantTypes())) {
                                    $set('is_important', true);
                                }
                            }),

                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->placeholder('Resumo do andamento'),

                        Forms\Components\RichEditor::make('content')
                            ->label('Conteúdo')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'link',
                            ])
                            ->placeholder('Conteúdo detalhado do andamento...'),
                    ]),

                // Seção: Data e Origem
                Forms\Components\Section::make('Data e Origem')
                    ->columns(4)
                    ->schema([
                        Forms\Components\DatePicker::make('proceeding_date')
                            ->label('Data do Andamento')
                            ->required()
                            ->native(false)
                            ->default(now())
                            ->displayFormat('d/m/Y'),

                        Forms\Components\TimePicker::make('proceeding_time')
                            ->label('Hora')
                            ->native(false),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Publicado DJE')
                            ->native(false)
                            ->displayFormat('d/m/Y H:i'),

                        Forms\Components\Select::make('source')
                            ->label('Fonte')
                            ->options(Proceeding::getSourceOptions())
                            ->default('manual')
                            ->native(false),
                    ]),

                // Seção: Prazo
                Forms\Components\Section::make('Prazo')
                    ->description('Configure se o andamento gera prazo')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Toggle::make('has_deadline')
                            ->label('Possui Prazo')
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('deadline_date')
                            ->label('Data do Prazo')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->visible(fn (Get $get) => $get('has_deadline'))
                            ->required(fn (Get $get) => $get('has_deadline')),

                        Forms\Components\TextInput::make('deadline_days')
                            ->label('Dias (úteis)')
                            ->numeric()
                            ->minValue(1)
                            ->visible(fn (Get $get) => $get('has_deadline'))
                            ->helperText('Prazo em dias úteis'),

                        Forms\Components\Toggle::make('deadline_completed')
                            ->label('Prazo Cumprido')
                            ->default(false)
                            ->visible(fn (Get $get) => $get('has_deadline'))
                            ->live()
                            ->afterStateUpdated(function (Set $set, bool $state) {
                                if ($state) {
                                    $set('deadline_completed_at', now());
                                } else {
                                    $set('deadline_completed_at', null);
                                }
                            }),

                        Forms\Components\DateTimePicker::make('deadline_completed_at')
                            ->label('Cumprido em')
                            ->native(false)
                            ->visible(fn (Get $get) => $get('has_deadline') && $get('deadline_completed')),
                    ]),

                // Seção: Ação Necessária
                Forms\Components\Section::make('Ação Necessária')
                    ->description('Defina se o andamento requer ação')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Forms\Components\Toggle::make('requires_action')
                            ->label('Requer Ação')
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('action_description')
                            ->label('Descrição da Ação')
                            ->rows(2)
                            ->visible(fn (Get $get) => $get('requires_action'))
                            ->columnSpanFull(),

                        Forms\Components\Select::make('action_responsible_id')
                            ->label('Responsável pela Ação')
                            ->relationship('actionResponsible', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => $get('requires_action')),

                        Forms\Components\Toggle::make('action_completed')
                            ->label('Ação Concluída')
                            ->default(false)
                            ->visible(fn (Get $get) => $get('requires_action'))
                            ->live(),
                    ]),

                // Seção: Classificação
                Forms\Components\Section::make('Classificação')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(Proceeding::getStatusOptions())
                            ->default('pending')
                            ->required()
                            ->native(false),

                        Forms\Components\Toggle::make('is_important')
                            ->label('Importante')
                            ->default(false)
                            ->helperText('Marcar como destaque'),

                        Forms\Components\Toggle::make('is_favorable')
                            ->label('Favorável')
                            ->helperText('Decisão favorável?')
                            ->visible(fn (Get $get) => in_array($get('type'), ['decision', 'sentence', 'dispatch'])),

                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => auth()->id()),
                    ]),

                // Seção: Observações
                Forms\Components\Section::make('Observações')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Observações')
                            ->rows(3)
                            ->placeholder('Visível para todos'),

                        Forms\Components\Textarea::make('internal_notes')
                            ->label('Notas Internas')
                            ->rows(3)
                            ->placeholder('Não visível ao cliente'),
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

                Tables\Columns\IconColumn::make('is_important')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('')
                    ->trueColor('warning')
                    ->width(30),

                Tables\Columns\TextColumn::make('proceeding_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (Proceeding::getTypeOptions()[$state] ?? $state) : '-')
                    ->color(fn (?string $state): string => match ($state) {
                        'decision', 'sentence' => 'success',
                        'deadline', 'citation' => 'warning',
                        'appeal', 'petition' => 'info',
                        'transit' => 'primary',
                        'archive' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(fn ($record) => $record?->title),

                Tables\Columns\TextColumn::make('process.title')
                    ->label('Processo')
                    ->limit(30)
                    ->searchable()
                    ->formatStateUsing(fn ($record) => $record?->process?->title ?? '')
                    ->default('')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('deadline_date')
                    ->label('Prazo')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('-')
                    ->color(fn ($record) => $record?->deadline_color)
                    ->icon(fn ($record) => $record?->is_overdue ? 'heroicon-o-exclamation-triangle' : null),

                Tables\Columns\IconColumn::make('deadline_completed')
                    ->label('Cumpr.')
                    ->boolean()
                    ->visible(fn ($livewire) => request()->has('tableFilters.has_deadline')),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (Proceeding::getStatusOptions()[$state] ?? $state) : '-')
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'analyzed' => 'info',
                        'actioned' => 'success',
                        'archived' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('source')
                    ->label('Fonte')
                    ->formatStateUsing(fn (?string $state): string => $state ? (Proceeding::getSourceOptions()[$state] ?? $state) : '-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Registrado por')
                    ->formatStateUsing(fn ($record) => $record?->user?->name ?? '')
                    ->default('')
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
                    ->options(Proceeding::getTypeOptions())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Proceeding::getStatusOptions()),

                Tables\Filters\SelectFilter::make('source')
                    ->label('Fonte')
                    ->options(Proceeding::getSourceOptions()),

                Tables\Filters\SelectFilter::make('process_id')
                    ->label('Processo')
                    ->relationship('process', 'title')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('has_deadline')
                    ->label('Com Prazo'),

                // Compatibilidade: filtro legado 'is_deadline' usado em URLs/links externos
                Tables\Filters\TernaryFilter::make('is_deadline')
                    ->label('Com Prazo (legacy)')
                    ->query(fn (Builder $query, array $data) => $query->where('has_deadline', $data['value'])),

                Tables\Filters\TernaryFilter::make('deadline_completed')
                    ->label('Prazo Cumprido'),

                Tables\Filters\TernaryFilter::make('requires_action')
                    ->label('Requer Ação'),

                Tables\Filters\TernaryFilter::make('is_important')
                    ->label('Importante'),

                Tables\Filters\Filter::make('overdue_deadlines')
                    ->label('Prazos Vencidos')
                    ->query(fn (Builder $query) => $query->overdueDeadlines()),

                Tables\Filters\Filter::make('deadlines_expiring')
                    ->label('Prazos Próximos (5 dias)')
                    ->query(fn (Builder $query) => $query->deadlinesExpiring(5)),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('completeDeadline')
                        ->label('Cumprir Prazo')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->has_deadline && !$record->deadline_completed)
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->completeDeadline()),
                    Tables\Actions\Action::make('completeAction')
                        ->label('Concluir Ação')
                        ->icon('heroicon-o-check')
                        ->color('info')
                        ->visible(fn ($record) => $record->requires_action && !$record->action_completed)
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->completeAction()),
                    Tables\Actions\Action::make('markAnalyzed')
                        ->label('Marcar Analisado')
                        ->icon('heroicon-o-eye')
                        ->visible(fn ($record) => $record->status === 'pending')
                        ->action(fn ($record) => $record->markAsAnalyzed()),
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
                    Tables\Actions\BulkAction::make('markAsAnalyzed')
                        ->label('Marcar como Analisado')
                        ->icon('heroicon-o-eye')
                        ->action(fn ($records) => $records->each->markAsAnalyzed())
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('proceeding_date', 'desc');
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
            'index' => Pages\ListProceedings::route('/'),
            'create' => Pages\CreateProceeding::route('/create'),
            'view' => Pages\ViewProceeding::route('/{record}'),
            'edit' => Pages\EditProceeding::route('/{record}/edit'),
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
        // Mostra quantidade de prazos vencidos ou próximos
        $overdueCount = static::getModel()::overdueDeadlines()->count();
        $expiringCount = static::getModel()::deadlinesExpiring(3)->count();

        $total = $overdueCount + $expiringCount;
        return $total > 0 ? (string) $total : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $overdueCount = static::getModel()::overdueDeadlines()->count();
        return $overdueCount > 0 ? 'danger' : 'warning';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['uid', 'title', 'content', 'process.title', 'process.cnj_number'];
    }
}
