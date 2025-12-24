<?php

namespace App\Filament\Resources\DeadlineResource\RelationManagers;

use App\Models\DeadlineAlert;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AlertsRelationManager extends RelationManager
{
    protected static string $relationship = 'alerts';

    protected static ?string $title = 'Alertas';

    protected static ?string $recordTitleAttribute = 'message';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Usuário')
                    ->relationship('user', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('type')
                    ->label('Tipo')
                    ->options(DeadlineAlert::TYPES)
                    ->required()
                    ->default('notification'),

                Forms\Components\TextInput::make('days_before')
                    ->label('Dias Antes')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->default(1),

                Forms\Components\Textarea::make('message')
                    ->label('Mensagem')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'email' => 'info',
                        'notification' => 'success',
                        'whatsapp' => 'success',
                        'sms' => 'warning',
                        'system' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => DeadlineAlert::TYPES[$state] ?? $state),

                Tables\Columns\TextColumn::make('days_before')
                    ->label('Dias Antes')
                    ->suffix(' dias')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuário'),

                Tables\Columns\IconColumn::make('is_sent')
                    ->label('Enviado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->trueColor('success')
                    ->falseIcon('heroicon-o-clock')
                    ->falseColor('warning'),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Enviado em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),

                Tables\Columns\IconColumn::make('read_at')
                    ->label('Lido')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->read_at !== null)
                    ->trueIcon('heroicon-o-eye')
                    ->trueColor('info')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('message')
                    ->label('Mensagem')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Novo Alerta'),
            ])
            ->actions([
                Tables\Actions\Action::make('send')
                    ->label('Enviar')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (DeadlineAlert $record) => $record->send())
                    ->visible(fn (DeadlineAlert $record) => !$record->is_sent),

                Tables\Actions\Action::make('markRead')
                    ->label('Marcar Lido')
                    ->icon('heroicon-o-eye')
                    ->action(fn (DeadlineAlert $record) => $record->markAsRead())
                    ->visible(fn (DeadlineAlert $record) => $record->is_sent && !$record->read_at),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
