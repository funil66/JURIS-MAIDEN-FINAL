<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProcessResource\Pages;
use App\Filament\Resources\ProcessResource\RelationManagers;
use App\Models\Process;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProcessResource extends Resource
{
    protected static ?string $model = Process::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';
    
    protected static ?string $navigationGroup = 'Jurídico';
    
    protected static ?string $modelLabel = 'Processo';
    
    protected static ?string $pluralModelLabel = 'Processos';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Seção: Identificação
                Forms\Components\Section::make('Identificação')
                    ->description('Dados básicos do processo')
                    ->icon('heroicon-o-scale')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nome')
                                    ->required(),
                                Forms\Components\TextInput::make('document')
                                    ->label('CPF/CNPJ')
                                    ->required(),
                            ]),

                        Forms\Components\Select::make('parent_id')
                            ->label('Processo Principal')
                            ->relationship('parent', 'title', fn (Builder $query) => $query->main())
                            ->searchable()
                            ->preload()
                            ->placeholder('Selecione se for subprocesso')
                            ->helperText('Deixe vazio se for processo principal'),

                        Forms\Components\Select::make('responsible_user_id')
                            ->label('Responsável')
                            ->relationship('responsibleUser', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->id()),

                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->placeholder('Ex: Ação de Cobrança - João vs Empresa X'),

                        Forms\Components\TextInput::make('cnj_number')
                            ->label('Número CNJ')
                            ->placeholder('0000000-00.0000.0.00.0000')
                            ->mask('9999999-99.9999.9.99.9999')
                            ->maxLength(25),

                        Forms\Components\TextInput::make('old_number')
                            ->label('Número Antigo')
                            ->maxLength(50)
                            ->placeholder('Numeração anterior (se houver)'),

                        Forms\Components\TextInput::make('internal_code')
                            ->label('Código Interno')
                            ->maxLength(50)
                            ->placeholder('Referência interna do escritório'),
                    ]),

                // Seção: Localização do Processo
                Forms\Components\Section::make('Localização')
                    ->description('Tribunal e órgão julgador')
                    ->icon('heroicon-o-building-library')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Forms\Components\Select::make('court')
                            ->label('Tribunal')
                            ->options(Process::getCourtOptions())
                            ->searchable()
                            ->live(),

                        Forms\Components\Select::make('state')
                            ->label('UF')
                            ->options(Process::getStateOptions())
                            ->searchable(),

                        Forms\Components\TextInput::make('jurisdiction')
                            ->label('Comarca')
                            ->maxLength(255)
                            ->placeholder('Ex: São Paulo'),

                        Forms\Components\TextInput::make('court_division')
                            ->label('Vara')
                            ->maxLength(255)
                            ->placeholder('Ex: 1ª Vara Cível'),

                        Forms\Components\TextInput::make('court_section')
                            ->label('Seção/Turma')
                            ->maxLength(255)
                            ->placeholder('Ex: 3ª Turma'),
                    ]),

                // Seção: Partes
                Forms\Components\Section::make('Partes')
                    ->description('Informações sobre as partes do processo')
                    ->icon('heroicon-o-users')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('plaintiff')
                            ->label('Autor/Requerente')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('defendant')
                            ->label('Réu/Requerido')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('client_role')
                            ->label('Papel do Cliente')
                            ->options(Process::getClientRoleOptions())
                            ->default('plaintiff')
                            ->required(),
                    ]),

                // Seção: Classificação
                Forms\Components\Section::make('Classificação')
                    ->description('Tipo e área do processo')
                    ->icon('heroicon-o-tag')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Forms\Components\Select::make('matter_type')
                            ->label('Área do Direito')
                            ->options(Process::getMatterTypeOptions())
                            ->searchable(),

                        Forms\Components\TextInput::make('action_type')
                            ->label('Tipo de Ação')
                            ->maxLength(255)
                            ->placeholder('Ex: Ação de Cobrança'),

                        Forms\Components\TextInput::make('procedure_type')
                            ->label('Rito Processual')
                            ->maxLength(255)
                            ->placeholder('Ex: Procedimento Comum'),

                        Forms\Components\TextInput::make('subject')
                            ->label('Assunto Principal')
                            ->maxLength(255),
                    ]),

                // Seção: Status e Fase
                Forms\Components\Section::make('Status e Fase')
                    ->description('Situação atual do processo')
                    ->icon('heroicon-o-signal')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(Process::getStatusOptions())
                            ->default('active')
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('phase')
                            ->label('Fase')
                            ->options(Process::getPhaseOptions())
                            ->default('knowledge')
                            ->required(),

                        Forms\Components\Select::make('instance')
                            ->label('Instância')
                            ->options(Process::getInstanceOptions())
                            ->default('first')
                            ->required(),

                        Forms\Components\Toggle::make('is_urgent')
                            ->label('Urgente')
                            ->helperText('Marque para processos prioritários'),

                        Forms\Components\Toggle::make('is_confidential')
                            ->label('Sigiloso')
                            ->helperText('Processo com segredo de justiça'),

                        Forms\Components\Toggle::make('has_injunction')
                            ->label('Possui Liminar')
                            ->helperText('Há decisão liminar deferida'),

                        Forms\Components\Toggle::make('is_pro_bono')
                            ->label('Pro Bono')
                            ->helperText('Atendimento gratuito'),
                    ]),

                // Seção: Datas
                Forms\Components\Section::make('Datas')
                    ->description('Marcos temporais do processo')
                    ->icon('heroicon-o-calendar')
                    ->columns(4)
                    ->collapsible()
                    ->schema([
                        Forms\Components\DatePicker::make('distribution_date')
                            ->label('Distribuição')
                            ->native(false),

                        Forms\Components\DatePicker::make('filing_date')
                            ->label('Ajuizamento')
                            ->native(false),

                        Forms\Components\DatePicker::make('closing_date')
                            ->label('Encerramento')
                            ->native(false)
                            ->visible(fn (Get $get) => in_array($get('status'), [
                                'archived', 'closed_won', 'closed_lost', 'closed_settled', 'closed_other'
                            ])),

                        Forms\Components\DatePicker::make('transit_date')
                            ->label('Trânsito em Julgado')
                            ->native(false),
                    ]),

                // Seção: Valores
                Forms\Components\Section::make('Valores')
                    ->description('Valores envolvidos no processo')
                    ->icon('heroicon-o-currency-dollar')
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('case_value')
                            ->label('Valor da Causa')
                            ->numeric()
                            ->prefix('R$')
                            ->inputMode('decimal'),

                        Forms\Components\TextInput::make('contingency_value')
                            ->label('Valor Contingencial')
                            ->numeric()
                            ->prefix('R$')
                            ->inputMode('decimal')
                            ->helperText('Estimativa de risco'),

                        Forms\Components\TextInput::make('sentence_value')
                            ->label('Valor da Sentença')
                            ->numeric()
                            ->prefix('R$')
                            ->inputMode('decimal'),
                    ]),

                // Seção: Advogados
                Forms\Components\Section::make('Advogados')
                    ->description('Advogados envolvidos no processo')
                    ->icon('heroicon-o-user-group')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Fieldset::make('Advogado Correspondente')
                            ->schema([
                                Forms\Components\TextInput::make('external_lawyer')
                                    ->label('Nome')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('external_lawyer_oab')
                                    ->label('OAB')
                                    ->maxLength(20),

                                Forms\Components\TextInput::make('external_lawyer_email')
                                    ->label('E-mail')
                                    ->email()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('external_lawyer_phone')
                                    ->label('Telefone')
                                    ->tel()
                                    ->maxLength(20),
                            ]),

                        Forms\Components\Fieldset::make('Advogado da Contraparte')
                            ->schema([
                                Forms\Components\TextInput::make('opposing_lawyer')
                                    ->label('Nome')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('opposing_lawyer_oab')
                                    ->label('OAB')
                                    ->maxLength(20),
                            ]),
                    ]),

                // Seção: Observações
                Forms\Components\Section::make('Observações')
                    ->description('Informações adicionais')
                    ->icon('heroicon-o-document-text')
                    ->columns(1)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\RichEditor::make('strategy')
                            ->label('Estratégia do Caso')
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'bulletList', 'orderedList'
                            ]),

                        Forms\Components\Textarea::make('risk_assessment')
                            ->label('Avaliação de Risco')
                            ->rows(3),

                        Forms\Components\Textarea::make('notes')
                            ->label('Observações Gerais')
                            ->rows(3),

                        Forms\Components\TextInput::make('folder_location')
                            ->label('Localização da Pasta Física')
                            ->maxLength(255)
                            ->placeholder('Ex: Armário 2, Prateleira 3'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('ID copiado!')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\IconColumn::make('is_urgent')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('')
                    ->trueColor('danger')
                    ->width(30),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->limit(35)
                    ->weight('bold')
                    ->description(fn ($record) => $record->formatted_cnj),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('matter_type')
                    ->label('Área')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => 
                        $state ? (Process::getMatterTypeOptions()[$state] ?? $state) : '-'
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => 
                        Process::getStatusOptions()[$state] ?? $state
                    )
                    ->color(fn (string $state): string => match($state) {
                        'active' => 'success',
                        'suspended' => 'warning',
                        'prospecting' => 'info',
                        'closed_won' => 'success',
                        'closed_lost' => 'danger',
                        'closed_settled' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('phase')
                    ->label('Fase')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => 
                        Process::getPhaseOptions()[$state] ?? $state
                    )
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('instance')
                    ->label('Instância')
                    ->formatStateUsing(fn (string $state): string => 
                        Process::getInstanceOptions()[$state] ?? $state
                    )
                    ->toggleable(),

                Tables\Columns\TextColumn::make('court')
                    ->label('Tribunal')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('jurisdiction')
                    ->label('Comarca')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('responsibleUser.name')
                    ->label('Responsável')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('case_value')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('distribution_date')
                    ->label('Distribuição')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_confidential')
                    ->label('Sigiloso')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Process::getStatusOptions())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('phase')
                    ->label('Fase')
                    ->options(Process::getPhaseOptions()),

                Tables\Filters\SelectFilter::make('instance')
                    ->label('Instância')
                    ->options(Process::getInstanceOptions()),

                Tables\Filters\SelectFilter::make('matter_type')
                    ->label('Área do Direito')
                    ->options(Process::getMatterTypeOptions()),

                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('responsible_user_id')
                    ->label('Responsável')
                    ->relationship('responsibleUser', 'name'),

                Tables\Filters\TernaryFilter::make('is_urgent')
                    ->label('Urgente'),

                Tables\Filters\TernaryFilter::make('is_confidential')
                    ->label('Sigiloso'),

                Tables\Filters\Filter::make('main_only')
                    ->label('Apenas Principais')
                    ->query(fn (Builder $query) => $query->main())
                    ->toggle(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('addSubprocess')
                        ->label('Adicionar Subprocesso')
                        ->icon('heroicon-o-plus-circle')
                        ->url(fn ($record) => route('filament.funil.resources.processes.create', [
                            'parent_id' => $record->id,
                            'client_id' => $record->client_id,
                        ])),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubprocessesRelationManager::class,
            RelationManagers\ProceedingsRelationManager::class,
            RelationManagers\ServicesRelationManager::class,
            RelationManagers\EventsRelationManager::class,
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProcesses::route('/'),
            'create' => Pages\CreateProcess::route('/create'),
            'view' => Pages\ViewProcess::route('/{record}'),
            'edit' => Pages\EditProcess::route('/{record}/edit'),
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
        return static::getModel()::active()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['uid', 'title', 'cnj_number', 'old_number', 'plaintiff', 'defendant'];
    }
}
