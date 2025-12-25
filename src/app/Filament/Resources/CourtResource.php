<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourtResource\Pages;
use App\Models\Court;
use App\Services\CourtApiService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CourtResource extends Resource
{
    protected static ?string $model = Court::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Tribunais';

    protected static ?string $modelLabel = 'Tribunal';

    protected static ?string $pluralModelLabel = 'Tribunais';

    protected static ?string $navigationGroup = 'Cadastros';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tribunal')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Informações Básicas')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Forms\Components\Section::make()
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('acronym')
                                            ->label('Sigla')
                                            ->required()
                                            ->maxLength(20)
                                            ->placeholder('Ex: TJSP, TRF3')
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('name')
                                            ->label('Nome')
                                            ->required()
                                            ->maxLength(200)
                                            ->placeholder('Ex: Tribunal de Justiça de São Paulo')
                                            ->columnSpan(2),

                                        Forms\Components\TextInput::make('full_name')
                                            ->label('Nome Completo')
                                            ->maxLength(500)
                                            ->columnSpan('full'),

                                        Forms\Components\Select::make('type')
                                            ->label('Tipo')
                                            ->options(Court::TYPES)
                                            ->required()
                                            ->searchable(),

                                        Forms\Components\Select::make('jurisdiction')
                                            ->label('Jurisdição')
                                            ->options(Court::JURISDICTIONS)
                                            ->required()
                                            ->searchable(),

                                        Forms\Components\Select::make('state')
                                            ->label('Estado')
                                            ->options(Court::STATES)
                                            ->searchable()
                                            ->placeholder('Selecione o estado'),

                                        Forms\Components\TextInput::make('region')
                                            ->label('Região')
                                            ->maxLength(50)
                                            ->placeholder('Ex: 1ª, 2ª, 3ª'),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Ativo')
                                            ->default(true)
                                            ->helperText('Tribunais inativos não serão sincronizados'),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Configuração da API')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Forms\Components\Section::make('Tipo e Endpoint')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Select::make('api_type')
                                            ->label('Tipo de API')
                                            ->options(Court::API_TYPES)
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(fn (Forms\Set $set, $state) => 
                                                $set('supported_operations', Court::make(['api_type' => $state])->getSupportedOperationsDefault())
                                            ),

                                        Forms\Components\TextInput::make('api_base_url')
                                            ->label('URL Base')
                                            ->url()
                                            ->placeholder('https://api.tribunal.jus.br')
                                            ->helperText('URL base da API do tribunal'),
                                    ]),

                                Forms\Components\Section::make('Credenciais')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('api_key')
                                            ->label('Chave de API')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('Chave de acesso')
                                            ->helperText('Usada principalmente para DataJud'),

                                        Forms\Components\Placeholder::make('')
                                            ->content('')
                                            ->hiddenLabel(),

                                        Forms\Components\TextInput::make('api_username')
                                            ->label('Usuário')
                                            ->maxLength(100)
                                            ->placeholder('Usuário de acesso'),

                                        Forms\Components\TextInput::make('api_password')
                                            ->label('Senha')
                                            ->password()
                                            ->revealable()
                                            ->placeholder('Senha de acesso'),

                                        Forms\Components\TextInput::make('api_certificate_path')
                                            ->label('Caminho do Certificado')
                                            ->maxLength(500)
                                            ->placeholder('/path/to/certificate.pfx')
                                            ->helperText('Para APIs que exigem certificado digital'),

                                        Forms\Components\TextInput::make('api_certificate_password')
                                            ->label('Senha do Certificado')
                                            ->password()
                                            ->revealable(),
                                    ]),

                                Forms\Components\Section::make('Configurações Avançadas')
                                    ->columns(2)
                                    ->collapsed()
                                    ->schema([
                                        Forms\Components\CheckboxList::make('supported_operations')
                                            ->label('Operações Suportadas')
                                            ->options([
                                                'movements' => 'Movimentações',
                                                'parties' => 'Partes',
                                                'documents' => 'Documentos',
                                                'hearings' => 'Audiências',
                                                'details' => 'Detalhes do Processo',
                                            ])
                                            ->columns(3)
                                            ->columnSpan('full'),

                                        Forms\Components\TextInput::make('requests_per_minute')
                                            ->label('Requisições por Minuto')
                                            ->numeric()
                                            ->default(60)
                                            ->minValue(1)
                                            ->maxValue(1000)
                                            ->helperText('Limite de rate limiting'),

                                        Forms\Components\TextInput::make('requests_per_day')
                                            ->label('Requisições por Dia')
                                            ->numeric()
                                            ->default(10000)
                                            ->minValue(1)
                                            ->helperText('Limite diário de requisições'),

                                        Forms\Components\KeyValue::make('request_headers')
                                            ->label('Headers Personalizados')
                                            ->keyLabel('Header')
                                            ->valueLabel('Valor')
                                            ->columnSpan('full'),

                                        Forms\Components\KeyValue::make('authentication_config')
                                            ->label('Configurações de Autenticação')
                                            ->keyLabel('Chave')
                                            ->valueLabel('Valor')
                                            ->columnSpan('full'),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Status')
                            ->icon('heroicon-o-signal')
                            ->schema([
                                Forms\Components\Section::make('Última Sincronização')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Placeholder::make('last_sync_display')
                                            ->label('Última Sincronização')
                                            ->content(fn (Court $record): string => 
                                                $record->last_sync_at?->format('d/m/Y H:i:s') ?? 'Nunca sincronizado'
                                            ),

                                        Forms\Components\Placeholder::make('config_status')
                                            ->label('Status da Configuração')
                                            ->content(fn (Court $record): string => 
                                                $record->isApiConfigured() ? '✓ Configurado' : '✗ Pendente'
                                            ),
                                    ]),

                                Forms\Components\Section::make('Último Erro')
                                    ->schema([
                                        Forms\Components\Placeholder::make('last_error_display')
                                            ->label('Data do Erro')
                                            ->content(fn (Court $record): string => 
                                                $record->last_error_at?->format('d/m/Y H:i:s') ?? '-'
                                            ),

                                        Forms\Components\Placeholder::make('last_error_msg')
                                            ->label('Mensagem')
                                            ->content(fn (Court $record): string => 
                                                $record->last_error_message ?? '-'
                                            ),
                                    ])
                                    ->visible(fn (Court $record): bool => 
                                        $record->last_error_at !== null
                                    ),

                                Forms\Components\Section::make('Estatísticas')
                                    ->columns(4)
                                    ->schema([
                                        Forms\Components\Placeholder::make('total_queries')
                                            ->label('Total de Consultas')
                                            ->content(fn (Court $record): string => 
                                                number_format($record->queries()->count())
                                            ),

                                        Forms\Components\Placeholder::make('total_movements')
                                            ->label('Movimentações')
                                            ->content(fn (Court $record): string => 
                                                number_format($record->movements()->count())
                                            ),

                                        Forms\Components\Placeholder::make('total_syncs')
                                            ->label('Sincronizações')
                                            ->content(fn (Court $record): string => 
                                                number_format($record->syncLogs()->count())
                                            ),

                                        Forms\Components\Placeholder::make('total_schedules')
                                            ->label('Agendamentos')
                                            ->content(fn (Court $record): string => 
                                                number_format($record->syncSchedules()->active()->count())
                                            ),
                                    ]),
                            ])
                            ->visible(fn (?Court $record): bool => $record !== null),
                    ])
                    ->columnSpanFull(),
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
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('acronym')
                    ->label('Sigla')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(40),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (?string $state): string => $state ? (Court::TYPES[$state] ?? $state) : '-')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('jurisdiction')
                    ->label('Jurisdição')
                    ->formatStateUsing(fn (?string $state): string => $state ? (Court::JURISDICTIONS[$state] ?? $state) : '-')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'federal' => 'info',
                        'estadual' => 'success',
                        'trabalhista' => 'warning',
                        'eleitoral' => 'purple',
                        'militar' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('state')
                    ->label('UF')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('api_type')
                    ->label('API')
                    ->formatStateUsing(fn (?string $state): string => $state ? (Court::API_TYPES[$state] ?? $state) : '-')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_configured')
                    ->label('Config.')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('last_sync_at')
                    ->label('Última Sync')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Nunca')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('movements_count')
                    ->label('Movim.')
                    ->counts('movements')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(Court::TYPES)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('jurisdiction')
                    ->label('Jurisdição')
                    ->options(Court::JURISDICTIONS)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('state')
                    ->label('Estado')
                    ->options(Court::STATES)
                    ->searchable()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('api_type')
                    ->label('Tipo de API')
                    ->options(Court::API_TYPES),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Ativo')
                    ->trueLabel('Ativos')
                    ->falseLabel('Inativos'),

                Tables\Filters\TernaryFilter::make('is_configured')
                    ->label('Configurado')
                    ->trueLabel('Configurados')
                    ->falseLabel('Não configurados'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('test_connection')
                        ->label('Testar Conexão')
                        ->icon('heroicon-o-signal')
                        ->color('info')
                        ->visible(fn (Court $record): bool => $record->isApiConfigured())
                        ->action(function (Court $record): void {
                            $service = app(CourtApiService::class);
                            $result = $service->testConnection($record);

                            if ($result['success']) {
                                Notification::make()
                                    ->title('Conexão OK')
                                    ->body($result['message'])
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Erro na Conexão')
                                    ->body($result['message'])
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Tables\Actions\Action::make('sync_now')
                        ->label('Sincronizar Agora')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (Court $record): bool => $record->isApiConfigured() && $record->is_active)
                        ->requiresConfirmation()
                        ->action(function (Court $record): void {
                            $service = app(CourtApiService::class);
                            $syncLog = $service->syncAllActiveProcesses($record);

                            $message = match ($syncLog->status) {
                                'success' => "Sincronização concluída: {$syncLog->movements_new} novas movimentações",
                                'partial' => "Sincronização parcial: {$syncLog->movements_new} novas, {$syncLog->errors_count} erros",
                                'error' => "Erro: {$syncLog->error_message}",
                                default => "Status: {$syncLog->status}",
                            };

                            Notification::make()
                                ->title($syncLog->status === 'error' ? 'Erro' : 'Sincronização')
                                ->body($message)
                                ->color($syncLog->status === 'error' ? 'danger' : 'success')
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Ativar')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desativar')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('acronym');
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
            'index' => Pages\ListCourts::route('/'),
            'create' => Pages\CreateCourt::route('/create'),
            'view' => Pages\ViewCourt::route('/{record}'),
            'edit' => Pages\EditCourt::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
}
