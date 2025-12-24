<?php

namespace App\Filament\Resources\ProcessResource\RelationManagers;

use App\Models\Deadline;
use App\Models\DeadlineType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DeadlinesRelationManager extends RelationManager
{
    protected static string $relationship = 'deadlines';

    protected static ?string $title = 'Prazos';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('deadline_type_id')
                    ->label('Tipo de Prazo')
                    ->options(DeadlineType::active()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                        if ($state) {
                            $type = DeadlineType::find($state);
                            if ($type) {
                                $set('days_count', $type->default_days);
                                $set('counting_type', $type->counting_type);
                                $set('priority', $type->priority);
                                $set('title', $type->name);
                            }
                        }
                    }),

                Forms\Components\TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(255),

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

                Forms\Components\TextInput::make('days_count')
                    ->label('Dias')
                    ->numeric()
                    ->required()
                    ->default(15)
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                        if ($state && $get('start_date')) {
                            $dueDate = Deadline::calculateDueDate(
                                \Carbon\Carbon::parse($get('start_date')),
                                (int) $state,
                                $get('counting_type') ?? Deadline::COUNTING_BUSINESS_DAYS
                            );
                            $set('due_date', $dueDate->format('Y-m-d'));
                        }
                    }),

                Forms\Components\Select::make('counting_type')
                    ->label('Contagem')
                    ->options(Deadline::COUNTING_TYPES)
                    ->required()
                    ->default(Deadline::COUNTING_BUSINESS_DAYS),

                Forms\Components\DatePicker::make('due_date')
                    ->label('Vencimento')
                    ->required(),

                Forms\Components\Select::make('priority')
                    ->label('Prioridade')
                    ->options(Deadline::PRIORITIES)
                    ->required()
                    ->default(Deadline::PRIORITY_NORMAL),

                Forms\Components\Select::make('assigned_user_id')
                    ->label('Responsável')
                    ->relationship('assignedUser', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn () => auth()->id()),

                Forms\Components\Textarea::make('description')
                    ->label('Descrição')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->label('UID')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->status_color)
                    ->weight('bold')
                    ->description(fn ($record) => $this->getDaysDescription($record)),

                Tables\Columns\TextColumn::make('title')
                    ->label('Prazo')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioridade')
                    ->badge()
                    ->color(fn ($record) => $record->priority_color)
                    ->formatStateUsing(fn ($state) => Deadline::PRIORITIES[$state] ?? $state),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => $record->status_color)
                    ->formatStateUsing(fn ($state) => Deadline::STATUSES[$state] ?? $state),

                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Responsável')
                    ->limit(15)
                    ->toggleable(),
            ])
            ->defaultSort('due_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(Deadline::STATUSES),

                Tables\Filters\Filter::make('pending')
                    ->label('Pendentes')
                    ->query(fn (Builder $query) => $query->pending())
                    ->default(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Novo Prazo')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by_user_id'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('complete')
                    ->label('Cumprido')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Deadline $record) => $record->complete())
                    ->visible(fn (Deadline $record) => $record->isPending()),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function getDaysDescription($record): string
    {
        if (!$record->isPending()) {
            return Deadline::STATUSES[$record->status] ?? '';
        }

        $days = $record->days_remaining;
        
        if ($days < 0) {
            return abs($days) . ' dia(s) vencido!';
        }
        
        if ($days === 0) {
            return 'HOJE!';
        }
        
        if ($days === 1) {
            return 'Amanhã';
        }
        
        return "Em $days dias";
    }
}
