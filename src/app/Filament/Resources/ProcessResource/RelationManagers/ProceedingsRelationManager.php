<?php

namespace App\Filament\Resources\ProcessResource\RelationManagers;

use App\Models\Proceeding;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProceedingsRelationManager extends RelationManager
{
    protected static string $relationship = 'proceedings';

    protected static ?string $title = 'Andamentos';

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
                            }),

                        Forms\Components\Select::make('source')
                            ->label('Fonte')
                            ->options(Proceeding::getSourceOptions())
                            ->default('manual')
                            ->native(false),

                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('proceeding_date')
                            ->label('Data')
                            ->required()
                            ->native(false)
                            ->default(now()),

                        Forms\Components\TimePicker::make('proceeding_time')
                            ->label('Hora')
                            ->native(false),

                        Forms\Components\RichEditor::make('content')
                            ->label('Conteúdo')
                            ->columnSpanFull()
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                            ]),

                        Forms\Components\Toggle::make('has_deadline')
                            ->label('Possui Prazo')
                            ->default(false)
                            ->live(),

                        Forms\Components\DatePicker::make('deadline_date')
                            ->label('Data do Prazo')
                            ->native(false)
                            ->visible(fn (Get $get) => $get('has_deadline'))
                            ->required(fn (Get $get) => $get('has_deadline')),

                        Forms\Components\Toggle::make('is_important')
                            ->label('Importante')
                            ->default(false),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(Proceeding::getStatusOptions())
                            ->default('pending')
                            ->native(false),

                        Forms\Components\Textarea::make('notes')
                            ->label('Observações')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Hidden::make('user_id')
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
                    ->color(fn (string $state): string => match ($state) {
                        'decision', 'sentence' => 'success',
                        'deadline', 'citation' => 'warning',
                        'appeal', 'petition' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->limit(40)
                    ->searchable()
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('deadline_date')
                    ->label('Prazo')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('-')
                    ->color(fn ($record) => $record->deadline_color),

                Tables\Columns\IconColumn::make('deadline_completed')
                    ->label('Cumpr.')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (Proceeding::getStatusOptions()[$state] ?? $state) : '-')
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'analyzed' => 'info',
                        'actioned' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(Proceeding::getTypeOptions()),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Proceeding::getStatusOptions()),

                Tables\Filters\TernaryFilter::make('has_deadline')
                    ->label('Com Prazo'),

                Tables\Filters\Filter::make('overdue_deadlines')
                    ->label('Prazos Vencidos')
                    ->query(fn (Builder $query) => $query->overdueDeadlines()),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = $data['user_id'] ?? auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.funil.resources.proceedings.view', $record)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('completeDeadline')
                    ->label('Cumprir Prazo')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->has_deadline && !$record->deadline_completed)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->completeDeadline()),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('proceeding_date', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
