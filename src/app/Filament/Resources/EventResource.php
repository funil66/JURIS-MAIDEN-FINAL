<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    protected static ?string $navigationGroup = 'Operacional';
    
    protected static ?string $modelLabel = 'Evento';
    
    protected static ?string $pluralModelLabel = 'Eventos';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Evento')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->options(Event::getTypeOptions())
                            ->default('task')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                                $set('color', Event::getTypeColors()[$state] ?? '#3b82f6')
                            ),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(Event::getStatusOptions())
                            ->default('scheduled')
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Data e Hora')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Toggle::make('all_day')
                            ->label('Dia Inteiro')
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Início')
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->default(now())
                            ->visible(fn (Get $get) => !$get('all_day')),

                        Forms\Components\DatePicker::make('starts_at')
                            ->label('Data')
                            ->required()
                            ->native(false)
                            ->default(now())
                            ->visible(fn (Get $get) => $get('all_day')),

                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Término')
                            ->native(false)
                            ->seconds(false)
                            ->after('starts_at')
                            ->visible(fn (Get $get) => !$get('all_day')),

                        Forms\Components\DatePicker::make('ends_at')
                            ->label('Data Fim')
                            ->native(false)
                            ->afterOrEqual('starts_at')
                            ->visible(fn (Get $get) => $get('all_day')),

                        Forms\Components\Select::make('reminder_minutes')
                            ->label('Lembrete')
                            ->options(Event::getReminderOptions())
                            ->native(false),
                    ]),

                Forms\Components\Section::make('Recorrência')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('recurrence')
                            ->label('Repetir')
                            ->options(Event::getRecurrenceOptions())
                            ->default('none')
                            ->live(),

                        Forms\Components\DatePicker::make('recurrence_end')
                            ->label('Repetir até')
                            ->native(false)
                            ->visible(fn (Get $get) => $get('recurrence') !== 'none'),
                    ]),

                Forms\Components\Section::make('Vinculação')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('service_id')
                            ->label('Serviço')
                            ->relationship('service', 'code')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ]),

                Forms\Components\Section::make('Local')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('location')
                            ->label('Local')
                            ->maxLength(255)
                            ->placeholder('Ex: Fórum Central'),

                        Forms\Components\TextInput::make('location_address')
                            ->label('Endereço')
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Aparência e Notas')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\ColorPicker::make('color')
                            ->label('Cor no Calendário')
                            ->default('#3b82f6'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color')
                    ->label('')
                    ->width(10),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Event::getTypeOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match($state) {
                        'hearing' => 'danger',
                        'deadline' => 'warning',
                        'meeting' => 'info',
                        'task' => 'success',
                        'reminder' => 'purple',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color(fn ($record) => $record->isPast() && $record->status !== 'completed' ? 'danger' : null),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->toggleable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('service.code')
                    ->label('Serviço')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Local')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Event::getStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => match($state) {
                        'scheduled' => 'warning',
                        'confirmed' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('starts_at', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(Event::getTypeOptions())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Event::getStatusOptions()),

                Tables\Filters\Filter::make('today')
                    ->label('Hoje')
                    ->query(fn (Builder $query): Builder => $query->today())
                    ->toggle(),

                Tables\Filters\Filter::make('this_week')
                    ->label('Esta Semana')
                    ->query(fn (Builder $query): Builder => $query->thisWeek())
                    ->toggle(),

                Tables\Filters\Filter::make('upcoming')
                    ->label('Próximos')
                    ->query(fn (Builder $query): Builder => $query->upcoming())
                    ->toggle()
                    ->default(),

                Tables\Filters\TrashedFilter::make()
                    ->label('Excluídos'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('complete')
                        ->label('Concluir')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->status !== 'completed')
                        ->action(fn ($record) => $record->update(['status' => 'completed'])),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Nenhum evento cadastrado')
            ->emptyStateDescription('Crie eventos para organizar sua agenda.')
            ->emptyStateIcon('heroicon-o-calendar-days');
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
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
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
        return static::getModel()::today()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
}
