<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Pagamentos';

    protected static ?string $recordTitleAttribute = 'uid';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Valor')
                            ->numeric()
                            ->prefix('R$')
                            ->required()
                            ->default(fn () => $this->ownerRecord->balance),

                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Data do Pagamento')
                            ->required()
                            ->native(false)
                            ->default(now()),

                        Forms\Components\Select::make('payment_method')
                            ->label('Forma de Pagamento')
                            ->options(Invoice::getPaymentMethodOptions())
                            ->native(false),

                        Forms\Components\TextInput::make('reference')
                            ->label('Referência'),

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
            ->recordTitleAttribute('uid')
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->label('ID')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Forma')
                    ->formatStateUsing(fn (?string $state): string => 
                        $state ? Invoice::getPaymentMethodOptions()[$state] ?? $state : '-'
                    ),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Referência')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('recorder.name')
                    ->label('Registrado por'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado em')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['recorded_by'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('payment_date', 'desc');
    }
}
