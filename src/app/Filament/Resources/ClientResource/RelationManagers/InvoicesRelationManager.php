<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'Faturas';

    protected static ?string $recordTitleAttribute = 'invoice_number';

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

                        Forms\Components\Select::make('invoice_type')
                            ->label('Tipo')
                            ->options(Invoice::getInvoiceTypeOptions())
                            ->default('services')
                            ->required()
                            ->native(false),

                        Forms\Components\TextInput::make('total')
                            ->label('Valor Total')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),

                        Forms\Components\DatePicker::make('issue_date')
                            ->label('Emissão')
                            ->required()
                            ->native(false)
                            ->default(now()),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Vencimento')
                            ->required()
                            ->native(false)
                            ->default(now()->addDays(30)),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(Invoice::getStatusOptions())
                            ->default('draft')
                            ->required()
                            ->native(false),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->label('ID')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Número')
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->limit(25),

                Tables\Columns\TextColumn::make('issue_date')
                    ->label('Emissão')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => 
                        $record->is_overdue 
                            ? 'danger' 
                            : ($record->days_until_due <= 7 && $record->days_until_due >= 0 
                                ? 'warning' 
                                : null)
                    ),

                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('BRL'),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Saldo')
                    ->money('BRL')
                    ->color('warning'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (Invoice::getStatusOptions()[$state] ?? $state) : '-')
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending' => 'warning',
                        'partial' => 'info',
                        'paid' => 'success',
                        'overdue' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Invoice::getStatusOptions()),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();
                        $data['subtotal'] = $data['total'];
                        $data['balance'] = $data['total'];
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.funil.resources.invoices.view', $record)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('issue_date', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
