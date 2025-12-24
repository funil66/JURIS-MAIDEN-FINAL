<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use App\Models\InvoiceItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Itens da Fatura';

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
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('item_type')
                            ->label('Tipo')
                            ->options(InvoiceItem::getItemTypeOptions())
                            ->default('service')
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('unit')
                            ->label('Unidade')
                            ->default('un')
                            ->maxLength(20),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantidade')
                            ->numeric()
                            ->default(1)
                            ->minValue(0.01)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => 
                                $set('total', 
                                    round((float) $get('quantity') * (float) $get('unit_price') - (float) ($get('discount') ?? 0), 2)
                                )
                            ),

                        Forms\Components\TextInput::make('unit_price')
                            ->label('Valor Unitário')
                            ->numeric()
                            ->prefix('R$')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => 
                                $set('total', 
                                    round((float) $get('quantity') * (float) $get('unit_price') - (float) ($get('discount') ?? 0), 2)
                                )
                            ),

                        Forms\Components\TextInput::make('discount')
                            ->label('Desconto')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => 
                                $set('total', 
                                    round((float) $get('quantity') * (float) $get('unit_price') - (float) $get('discount'), 2)
                                )
                            ),

                        Forms\Components\TextInput::make('total')
                            ->label('Total')
                            ->numeric()
                            ->prefix('R$')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Observações')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('item_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => InvoiceItem::getItemTypeOptions()[$state] ?? $state)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qtd.')
                    ->alignRight(),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Un.')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Valor Unit.')
                    ->money('BRL')
                    ->alignRight(),

                Tables\Columns\TextColumn::make('discount')
                    ->label('Desc.')
                    ->money('BRL')
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('BRL')
                    ->weight('bold')
                    ->alignRight(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['total'] = round(
                            (float) $data['quantity'] * (float) $data['unit_price'] - (float) ($data['discount'] ?? 0),
                            2
                        );
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['total'] = round(
                            (float) $data['quantity'] * (float) $data['unit_price'] - (float) ($data['discount'] ?? 0),
                            2
                        );
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
