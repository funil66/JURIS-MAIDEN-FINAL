<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use App\Models\Contract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'contracts';

    protected static ?string $title = 'Contratos';

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

                        Forms\Components\TextInput::make('contract_number')
                            ->label('Número')
                            ->maxLength(50),

                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->options(Contract::getTypeOptions())
                            ->default('legal_services')
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('fee_type')
                            ->label('Tipo Honorário')
                            ->options(Contract::getFeeTypeOptions())
                            ->default('fixed')
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('total_value')
                            ->label('Valor Total')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Início')
                            ->native(false)
                            ->default(now()),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Término')
                            ->native(false),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(Contract::getStatusOptions())
                            ->default('draft')
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('responsible_user_id')
                            ->label('Responsável')
                            ->relationship('responsibleUser', 'name')
                            ->searchable()
                            ->preload()
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

                Tables\Columns\TextColumn::make('contract_number')
                    ->label('Número')
                    ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('fee_type')
                    ->label('Honorário')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (Contract::getFeeTypeOptions()[$state] ?? $state) : '-')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('total_value')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_paid')
                    ->label('Pago')
                    ->money('BRL')
                    ->color('success'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (Contract::getStatusOptions()[$state] ?? $state) : '-')
                    ->color(fn (?string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending_signature' => 'warning',
                        'active' => 'success',
                        'suspended' => 'danger',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Contract::getStatusOptions()),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.funil.resources.contracts.view', $record)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
