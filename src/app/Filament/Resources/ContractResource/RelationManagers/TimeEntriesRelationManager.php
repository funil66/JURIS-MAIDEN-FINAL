<?php

namespace App\Filament\Resources\ContractResource\RelationManagers;

use App\Models\TimeEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TimeEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'timeEntries';

    protected static ?string $title = 'Tempo Trabalhado';

    protected static ?string $recordTitleAttribute = 'description';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('description')
                            ->label('Descrição')
                            ->required()
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('activity_type')
                            ->label('Tipo')
                            ->options(TimeEntry::getActivityTypeOptions())
                            ->default('other')
                            ->required()
                            ->native(false)
                            ->searchable(),

                        Forms\Components\Select::make('user_id')
                            ->label('Colaborador')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->id())
                            ->required(),

                        Forms\Components\DatePicker::make('work_date')
                            ->label('Data')
                            ->required()
                            ->native(false)
                            ->default(now()),

                        Forms\Components\Select::make('duration_minutes')
                            ->label('Duração')
                            ->options(TimeEntry::getCommonDurations())
                            ->default(30)
                            ->required(),

                        Forms\Components\Toggle::make('is_billable')
                            ->label('Faturável')
                            ->default(true),

                        Forms\Components\TextInput::make('hourly_rate')
                            ->label('Taxa Horária')
                            ->numeric()
                            ->prefix('R$'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
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
                    ->label('Colaborador'),

                Tables\Columns\TextColumn::make('activity_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => TimeEntry::getActivityTypeOptions()[$state] ?? $state)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('formatted_duration')
                    ->label('Duração'),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Valor')
                    ->money('BRL'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => TimeEntry::getStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'submitted' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Colaborador')
                    ->relationship('user', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(TimeEntry::getStatusOptions()),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.funil.resources.time-entries.view', $record)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('work_date', 'desc');
    }
}
