<?php

namespace App\Filament\Resources\ProcessResource\RelationManagers;

use App\Models\Diligence;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DiligencesRelationManager extends RelationManager
{
    protected static string $relationship = 'diligences';

    protected static ?string $title = 'Diligências';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
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

                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('scheduled_date')
                            ->label('Data')
                            ->required()
                            ->native(false),

                        Forms\Components\TimePicker::make('scheduled_time')
                            ->label('Hora')
                            ->native(false),

                        Forms\Components\TextInput::make('location_name')
                            ->label('Local')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('location_address')
                            ->label('Endereço')
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('assigned_user_id')
                            ->label('Responsável')
                            ->relationship('assignedUser', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->id()),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(Diligence::getStatusOptions())
                            ->default('pending')
                            ->native(false),

                        Forms\Components\Toggle::make('is_billable')
                            ->label('Faturável')
                            ->default(true),

                        Forms\Components\TextInput::make('estimated_cost')
                            ->label('Custo Estimado')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0),

                        Forms\Components\Textarea::make('objective')
                            ->label('Objetivo')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('client_id')
                            ->default(fn () => $this->ownerRecord->client_id),

                        Forms\Components\Hidden::make('created_by_user_id')
                            ->default(fn () => auth()->id()),
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
                    ->color('gray'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->limit(35)
                    ->searchable()
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('location_name')
                    ->label('Local')
                    ->limit(20)
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Responsável')
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
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_cost')
                    ->label('Custo')
                    ->money('BRL')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Diligence::getStatusOptions()),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(Diligence::getTypeOptions()),

                Tables\Filters\Filter::make('overdue')
                    ->label('Atrasadas')
                    ->query(fn (Builder $query) => $query->overdue()),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['client_id'] = $data['client_id'] ?? $this->ownerRecord->client_id;
                        $data['created_by_user_id'] = $data['created_by_user_id'] ?? auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.funil.resources.diligences.view', $record)),
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
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->complete()),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('scheduled_date', 'asc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
