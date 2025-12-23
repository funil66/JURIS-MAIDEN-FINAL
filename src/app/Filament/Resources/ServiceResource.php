<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    
    protected static ?string $navigationGroup = 'Operacional';
    
    protected static ?string $modelLabel = 'Serviço';
    
    protected static ?string $pluralModelLabel = 'Serviços';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identificação')
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
                                Forms\Components\TextInput::make('phone')
                                    ->label('Telefone'),
                            ]),

                        Forms\Components\Select::make('service_type_id')
                            ->label('Tipo de Serviço')
                            ->relationship('serviceType', 'name', fn (Builder $query) => $query->active()->ordered())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                if ($state) {
                                    $serviceType = ServiceType::find($state);
                                    if ($serviceType) {
                                        $set('agreed_price', $serviceType->default_price);
                                        if ($serviceType->default_deadline_days > 0) {
                                            $set('deadline_date', now()->addDays($serviceType->default_deadline_days)->format('Y-m-d'));
                                        }
                                    }
                                }
                            }),

                        Forms\Components\Select::make('priority')
                            ->label('Prioridade')
                            ->options(Service::getPriorityOptions())
                            ->default('normal')
                            ->required(),
                    ]),

                Forms\Components\Section::make('Dados do Processo')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('process_number')
                            ->label('Número do Processo')
                            ->placeholder('0000000-00.0000.0.00.0000')
                            ->maxLength(30),

                        Forms\Components\TextInput::make('court')
                            ->label('Vara/Tribunal')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('jurisdiction')
                            ->label('Comarca')
                            ->maxLength(255),

                        Forms\Components\Select::make('state')
                            ->label('UF')
                            ->options([
                                'AC' => 'AC', 'AL' => 'AL', 'AP' => 'AP', 'AM' => 'AM', 'BA' => 'BA',
                                'CE' => 'CE', 'DF' => 'DF', 'ES' => 'ES', 'GO' => 'GO', 'MA' => 'MA',
                                'MT' => 'MT', 'MS' => 'MS', 'MG' => 'MG', 'PA' => 'PA', 'PB' => 'PB',
                                'PR' => 'PR', 'PE' => 'PE', 'PI' => 'PI', 'RJ' => 'RJ', 'RN' => 'RN',
                                'RS' => 'RS', 'RO' => 'RO', 'RR' => 'RR', 'SC' => 'SC', 'SP' => 'SP',
                                'SE' => 'SE', 'TO' => 'TO',
                            ])
                            ->searchable(),

                        Forms\Components\TextInput::make('plaintiff')
                            ->label('Autor/Requerente')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('defendant')
                            ->label('Réu/Requerido')
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Datas e Prazos')
                    ->columns(4)
                    ->schema([
                        Forms\Components\DatePicker::make('request_date')
                            ->label('Data da Solicitação')
                            ->required()
                            ->default(now())
                            ->native(false),

                        Forms\Components\DatePicker::make('deadline_date')
                            ->label('Prazo/Data Limite')
                            ->native(false),

                        Forms\Components\DateTimePicker::make('scheduled_datetime')
                            ->label('Data/Hora Agendada')
                            ->native(false)
                            ->seconds(false),

                        Forms\Components\DatePicker::make('completion_date')
                            ->label('Data de Conclusão')
                            ->native(false),
                    ]),

                Forms\Components\Section::make('Local da Diligência')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('location_name')
                            ->label('Nome do Local')
                            ->placeholder('Ex: Fórum Central, Cartório 1º Ofício')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('location_address')
                            ->label('Endereço')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('location_cep')
                            ->label('CEP')
                            ->mask('99999-999'),

                        Forms\Components\TextInput::make('location_city')
                            ->label('Cidade')
                            ->maxLength(255),

                        Forms\Components\Select::make('location_state')
                            ->label('UF')
                            ->options([
                                'AC' => 'AC', 'AL' => 'AL', 'AP' => 'AP', 'AM' => 'AM', 'BA' => 'BA',
                                'CE' => 'CE', 'DF' => 'DF', 'ES' => 'ES', 'GO' => 'GO', 'MA' => 'MA',
                                'MT' => 'MT', 'MS' => 'MS', 'MG' => 'MG', 'PA' => 'PA', 'PB' => 'PB',
                                'PR' => 'PR', 'PE' => 'PE', 'PI' => 'PI', 'RJ' => 'RJ', 'RN' => 'RN',
                                'RS' => 'RS', 'RO' => 'RO', 'RR' => 'RR', 'SC' => 'SC', 'SP' => 'SP',
                                'SE' => 'SE', 'TO' => 'TO',
                            ])
                            ->searchable(),
                    ]),

                Forms\Components\Section::make('Valores')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('agreed_price')
                            ->label('Valor Acordado')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->step(0.01)
                            ->live(onBlur: true),

                        Forms\Components\TextInput::make('expenses')
                            ->label('Despesas')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->step(0.01)
                            ->helperText('Custas, deslocamento, etc.')
                            ->live(onBlur: true),

                        Forms\Components\Placeholder::make('total_display')
                            ->label('Total')
                            ->content(function (Get $get): string {
                                $total = floatval($get('agreed_price') ?? 0) + floatval($get('expenses') ?? 0);
                                return 'R$ ' . number_format($total, 2, ',', '.');
                            }),
                    ]),

                Forms\Components\Section::make('Status')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status do Serviço')
                            ->options(Service::getStatusOptions())
                            ->default('pending')
                            ->required(),

                        Forms\Components\Select::make('payment_status')
                            ->label('Status do Pagamento')
                            ->options(Service::getPaymentStatusOptions())
                            ->default('pending')
                            ->required(),
                    ]),

                Forms\Components\Section::make('Detalhes e Observações')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Descrição do Serviço')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('instructions')
                            ->label('Instruções Específicas')
                            ->rows(3)
                            ->placeholder('Instruções para realização do serviço...')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('result_notes')
                            ->label('Resultado/Observações Finais')
                            ->rows(3)
                            ->placeholder('Preenchido após conclusão...')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('internal_notes')
                            ->label('Notas Internas')
                            ->rows(2)
                            ->placeholder('Anotações internas (não visíveis ao cliente)')
                            ->columnSpanFull(),
                    ]),

                // ==========================================
                // Sprint 13: Seções Estendidas
                // ==========================================
                
                Forms\Components\Section::make('Dados do Juízo')
                    ->description('Informações sobre o juiz e secretaria')
                    ->icon('heroicon-o-scale')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('judge_name')
                            ->label('Nome do(a) Juiz(a)')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('court_secretary')
                            ->label('Secretário(a) da Vara')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('court_phone')
                            ->label('Telefone do Fórum')
                            ->tel()
                            ->mask('(99) 9999-9999'),

                        Forms\Components\TextInput::make('court_email')
                            ->label('E-mail do Fórum')
                            ->email()
                            ->maxLength(255),
                    ]),

                Forms\Components\Section::make('Solicitante')
                    ->description('Advogado ou escritório que solicitou o serviço')
                    ->icon('heroicon-o-user-group')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('requester_name')
                            ->label('Nome do Solicitante')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('requester_oab')
                            ->label('OAB do Solicitante')
                            ->placeholder('Ex: SP 123.456')
                            ->maxLength(20),

                        Forms\Components\TextInput::make('requester_email')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('requester_phone')
                            ->label('Telefone')
                            ->tel()
                            ->mask('(99) 99999-9999'),
                    ]),

                Forms\Components\Section::make('Deslocamento')
                    ->description('Informações sobre viagem e custos de deslocamento')
                    ->icon('heroicon-o-truck')
                    ->columns(4)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('travel_type')
                            ->label('Tipo de Deslocamento')
                            ->options(Service::getTravelTypeOptions())
                            ->default('none'),

                        Forms\Components\TextInput::make('travel_distance_km')
                            ->label('Distância (km)')
                            ->numeric()
                            ->suffix('km')
                            ->step(0.1),

                        Forms\Components\TextInput::make('travel_cost')
                            ->label('Custo de Deslocamento')
                            ->numeric()
                            ->prefix('R$')
                            ->step(0.01)
                            ->default(0),

                        Forms\Components\Textarea::make('travel_notes')
                            ->label('Observações de Viagem')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Documentos')
                    ->description('Controle de documentação necessária')
                    ->icon('heroicon-o-document-text')
                    ->columns(3)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Toggle::make('has_substabelecimento')
                            ->label('Possui Substabelecimento?')
                            ->inline(false),

                        Forms\Components\Toggle::make('has_procuracao')
                            ->label('Possui Procuração?')
                            ->inline(false),

                        Forms\Components\Toggle::make('documents_received')
                            ->label('Documentos Recebidos?')
                            ->inline(false),

                        Forms\Components\DatePicker::make('documents_received_at')
                            ->label('Data Recebimento')
                            ->native(false)
                            ->visible(fn (Get $get) => $get('documents_received')),

                        Forms\Components\FileUpload::make('attachments')
                            ->label('Anexos')
                            ->multiple()
                            ->directory('service-attachments')
                            ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(10240)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Resultado')
                    ->description('Informações sobre a realização do serviço')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('result_type')
                            ->label('Tipo de Resultado')
                            ->options(Service::getResultTypeOptions())
                            ->default('pending'),

                        Forms\Components\DateTimePicker::make('actual_datetime')
                            ->label('Data/Hora Real da Realização')
                            ->native(false)
                            ->seconds(false),

                        Forms\Components\Textarea::make('result_summary')
                            ->label('Resumo do Resultado')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('result_attachments')
                            ->label('Comprovantes')
                            ->multiple()
                            ->directory('service-results')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(10240)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Avaliação e Follow-up')
                    ->description('Feedback do cliente e necessidade de acompanhamento')
                    ->icon('heroicon-o-star')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('client_rating')
                            ->label('Avaliação do Cliente')
                            ->options(Service::getRatingOptions())
                            ->placeholder('Sem avaliação'),

                        Forms\Components\Toggle::make('requires_followup')
                            ->label('Requer Acompanhamento?')
                            ->inline(false),

                        Forms\Components\Textarea::make('client_feedback')
                            ->label('Feedback do Cliente')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('followup_notes')
                            ->label('Notas de Acompanhamento')
                            ->rows(2)
                            ->visible(fn (Get $get) => $get('requires_followup'))
                            ->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->limit(25)
                    ->url(fn ($record) => route('filament.funil.resources.clients.view', $record->client_id)),

                Tables\Columns\TextColumn::make('serviceType.name')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('process_number')
                    ->label('Processo')
                    ->searchable()
                    ->toggleable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('deadline_date')
                    ->label('Prazo')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null)
                    ->icon(fn ($record) => $record->isOverdue() ? 'heroicon-o-exclamation-triangle' : null),

                Tables\Columns\TextColumn::make('scheduled_datetime')
                    ->label('Agendamento')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Service::getStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => Service::getStatusColors()[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioridade')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Service::getPriorityOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => Service::getPriorityColors()[$state] ?? 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Pagamento')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Service::getPaymentStatusOptions()[$state] ?? $state)
                    ->color(fn (string $state): string => Service::getPaymentStatusColors()[$state] ?? 'gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('deadline_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(Service::getStatusOptions())
                    ->multiple(),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Pagamento')
                    ->options(Service::getPaymentStatusOptions()),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Prioridade')
                    ->options(Service::getPriorityOptions()),

                Tables\Filters\SelectFilter::make('service_type_id')
                    ->label('Tipo de Serviço')
                    ->relationship('serviceType', 'name'),

                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('overdue')
                    ->label('Atrasados')
                    ->query(fn (Builder $query): Builder => $query->overdue())
                    ->toggle(),

                Tables\Filters\Filter::make('upcoming')
                    ->label('Próximos 7 dias')
                    ->query(fn (Builder $query): Builder => $query->upcoming(7))
                    ->toggle(),

                Tables\Filters\TrashedFilter::make()
                    ->label('Excluídos'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('complete')
                        ->label('Marcar Concluído')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
                        ->action(fn ($record) => $record->update([
                            'status' => 'completed',
                            'completion_date' => now(),
                        ])),
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
            ->emptyStateHeading('Nenhum serviço cadastrado')
            ->emptyStateDescription('Cadastre seu primeiro serviço/diligência.')
            ->emptyStateIcon('heroicon-o-briefcase');
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
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
        return static::getModel()::whereIn('status', ['pending', 'confirmed', 'in_progress'])->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $overdueCount = static::getModel()::overdue()->count();
        return $overdueCount > 0 ? 'danger' : 'primary';
    }
}
