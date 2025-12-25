<?php

namespace App\Filament\Resources\ProcessResource\RelationManagers;

use App\Models\Process;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubprocessesRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $title = 'Subprocessos';

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
                            ->maxLength(500)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('cnj_number')
                            ->label('Número CNJ')
                            ->maxLength(25)
                            ->placeholder('NNNNNNN-DD.AAAA.J.TR.OOOO'),

                        Forms\Components\Select::make('matter_type')
                            ->label('Área do Direito')
                            ->options(Process::getMatterTypeOptions())
                            ->native(false)
                            ->searchable(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(Process::getStatusOptions())
                            ->default('prospecting')
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('phase')
                            ->label('Fase')
                            ->options(Process::getPhaseOptions())
                            ->default('knowledge')
                            ->native(false),

                        Forms\Components\Select::make('court')
                            ->label('Tribunal')
                            ->options(Process::getCourtOptions())
                            ->native(false)
                            ->searchable(),

                        Forms\Components\Select::make('instance')
                            ->label('Instância')
                            ->options(Process::getInstanceOptions())
                            ->default('first')
                            ->native(false),

                        Forms\Components\TextInput::make('jurisdiction')
                            ->label('Comarca')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('court_division')
                            ->label('Vara')
                            ->maxLength(100),

                        Forms\Components\Select::make('client_role')
                            ->label('Papel do Cliente')
                            ->options(Process::getClientRoleOptions())
                            ->default('plaintiff')
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('responsible_user_id')
                            ->label('Responsável')
                            ->relationship('responsibleUser', 'name')
                            ->native(false)
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('case_value')
                            ->label('Valor da Causa')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0),

                        Forms\Components\Toggle::make('is_urgent')
                            ->label('Urgente')
                            ->default(false),

                        Forms\Components\Toggle::make('is_confidential')
                            ->label('Sigiloso')
                            ->default(false),

                        Forms\Components\Textarea::make('notes')
                            ->label('Observações')
                            ->rows(3)
                            ->columnSpanFull(),
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

                Tables\Columns\IconColumn::make('is_urgent')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-s-exclamation-triangle')
                    ->falseIcon('')
                    ->trueColor('danger')
                    ->width(30),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('formatted_cnj')
                    ->label('Nº CNJ')
                    ->searchable(['cnj_number'])
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? (Process::getStatusOptions()[$state] ?? $state) : '-')
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'warning',
                        'prospecting' => 'info',
                        'archived' => 'gray',
                        'closed_won' => 'success',
                        'closed_lost' => 'danger',
                        'closed_settled', 'closed_other' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('phase')
                    ->label('Fase')
                    ->formatStateUsing(fn (?string $state): string => $state ? (Process::getPhaseOptions()[$state] ?? $state) : '-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('instance')
                    ->label('Instância')
                    ->formatStateUsing(fn (?string $state): string => $state ? (Process::getInstanceOptions()[$state] ?? $state) : '-')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('case_value')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Process::getStatusOptions()),

                Tables\Filters\SelectFilter::make('phase')
                    ->label('Fase')
                    ->options(Process::getPhaseOptions()),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Herda o client_id do processo pai
                        $data['client_id'] = $this->ownerRecord->client_id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.funil.resources.processes.view', $record)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
