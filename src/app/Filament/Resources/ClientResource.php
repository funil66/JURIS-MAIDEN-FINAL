<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use App\Rules\CpfCnpj;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'Cadastros';
    
    protected static ?string $modelLabel = 'Cliente';
    
    protected static ?string $pluralModelLabel = 'Clientes';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados Básicos')
                    ->description('Informações principais do cliente')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([
                        Forms\Components\ToggleButtons::make('type')
                            ->label('Tipo de Pessoa')
                            ->options([
                                'pf' => 'Pessoa Física',
                                'pj' => 'Pessoa Jurídica',
                            ])
                            ->icons([
                                'pf' => 'heroicon-o-user',
                                'pj' => 'heroicon-o-building-office',
                            ])
                            ->default('pf')
                            ->inline()
                            ->required()
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('name')
                            ->label('Nome Completo')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Nome do cliente'),

                        Forms\Components\TextInput::make('document')
                            ->label(fn (Get $get) => $get('type') === 'pj' ? 'CNPJ' : 'CPF')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->mask(fn (Get $get) => $get('type') === 'pj' ? '99.999.999/9999-99' : '999.999.999-99')
                            ->placeholder(fn (Get $get) => $get('type') === 'pj' ? '00.000.000/0000-00' : '000.000.000-00')
                            ->rules([new CpfCnpj()]),

                        Forms\Components\TextInput::make('rg')
                            ->label('RG')
                            ->maxLength(20)
                            ->visible(fn (Get $get) => $get('type') === 'pf'),

                        Forms\Components\TextInput::make('oab')
                            ->label('OAB')
                            ->maxLength(20)
                            ->placeholder('UF000000')
                            ->helperText('Preencha se o cliente for advogado'),

                        // Campos para PJ
                        Forms\Components\TextInput::make('company_name')
                            ->label('Razão Social')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('type') === 'pj'),

                        Forms\Components\TextInput::make('trading_name')
                            ->label('Nome Fantasia')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('type') === 'pj'),

                        Forms\Components\TextInput::make('contact_person')
                            ->label('Pessoa de Contato')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => $get('type') === 'pj'),
                    ]),

                Forms\Components\Section::make('Contato')
                    ->description('Telefones e e-mail')
                    ->icon('heroicon-o-phone')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('email@exemplo.com'),

                        Forms\Components\TextInput::make('phone')
                            ->label('Telefone')
                            ->tel()
                            ->mask('(99) 9999-9999')
                            ->placeholder('(00) 0000-0000'),

                        Forms\Components\TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->tel()
                            ->mask('(99) 99999-9999')
                            ->placeholder('(00) 00000-0000')
                            ->suffixIcon('heroicon-o-chat-bubble-left-ellipsis'),
                    ]),

                Forms\Components\Section::make('Endereço')
                    ->description('Localização do cliente')
                    ->icon('heroicon-o-map-pin')
                    ->columns(3)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('cep')
                            ->label('CEP')
                            ->mask('99999-999')
                            ->placeholder('00000-000')
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('street')
                            ->label('Rua/Avenida')
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('number')
                            ->label('Número')
                            ->maxLength(20),

                        Forms\Components\TextInput::make('complement')
                            ->label('Complemento')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('neighborhood')
                            ->label('Bairro')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('city')
                            ->label('Cidade')
                            ->maxLength(255),

                        Forms\Components\Select::make('state')
                            ->label('Estado')
                            ->options([
                                'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
                                'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
                                'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
                                'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
                                'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
                                'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
                                'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins',
                            ])
                            ->searchable()
                            ->native(false),
                    ]),

                Forms\Components\Section::make('Observações')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Observações')
                            ->rows(4)
                            ->maxLength(65535)
                            ->placeholder('Anotações sobre o cliente...')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Cliente Ativo')
                            ->default(true)
                            ->helperText('Desative para ocultar o cliente das listas'),
                    ]),

                Forms\Components\Section::make('Acesso ao Portal')
                    ->description('Configure o acesso do cliente ao portal de acompanhamento')
                    ->icon('heroicon-o-computer-desktop')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Toggle::make('portal_access')
                            ->label('Habilitar Acesso ao Portal')
                            ->helperText('Permite que o cliente acesse o portal para acompanhar serviços')
                            ->live()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('password')
                            ->label('Senha do Portal')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (Get $get, string $operation): bool => $operation === 'create' && $get('portal_access'))
                            ->visible(fn (Get $get): bool => $get('portal_access'))
                            ->helperText('Deixe em branco para manter a senha atual'),

                        Forms\Components\Placeholder::make('portal_info')
                            ->label('URL do Portal')
                            ->content(fn (): string => config('app.url') . '/portal')
                            ->visible(fn (Get $get): bool => $get('portal_access')),

                        Forms\Components\Placeholder::make('portal_last_login')
                            ->label('Último Login')
                            ->content(fn ($record): string => $record?->portal_last_login_at?->format('d/m/Y H:i') ?? 'Nunca')
                            ->visible(fn ($record): bool => $record?->portal_access ?? false),
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

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'pf' ? 'PF' : 'PJ')
                    ->color(fn (string $state): string => $state === 'pf' ? 'info' : 'warning'),

                Tables\Columns\TextColumn::make('document')
                    ->label('CPF/CNPJ')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Documento copiado!'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefone')
                    ->searchable()
                    ->icon('heroicon-o-phone'),

                Tables\Columns\TextColumn::make('whatsapp')
                    ->label('WhatsApp')
                    ->searchable()
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->url(fn ($record) => $record->whatsapp ? 'https://wa.me/55' . preg_replace('/[^0-9]/', '', $record->whatsapp) : null)
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('city')
                    ->label('Cidade')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('state')
                    ->label('UF')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\IconColumn::make('portal_access')
                    ->label('Portal')
                    ->boolean()
                    ->trueIcon('heroicon-o-computer-desktop')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'pf' => 'Pessoa Física',
                        'pj' => 'Pessoa Jurídica',
                    ]),

                Tables\Filters\SelectFilter::make('state')
                    ->label('Estado')
                    ->options([
                        'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
                        'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
                        'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
                        'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
                        'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
                        'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
                        'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins',
                    ])
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Todos')
                    ->trueLabel('Apenas Ativos')
                    ->falseLabel('Apenas Inativos'),

                Tables\Filters\TrashedFilter::make()
                    ->label('Excluídos'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver Resumo'),
                Tables\Actions\Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->visible(fn (Client $record): bool => !empty($record->whatsapp) || !empty($record->phone))
                    ->url(fn (Client $record): string => 'https://wa.me/55' . preg_replace('/[^0-9]/', '', $record->whatsapp ?? $record->phone))
                    ->openUrlInNewTab(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
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
            ])
            ->emptyStateHeading('Nenhum cliente cadastrado')
            ->emptyStateDescription('Cadastre seu primeiro cliente clicando no botão abaixo.')
            ->emptyStateIcon('heroicon-o-users');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            ClientResource\Widgets\ClientServicesWidget::class,
            ClientResource\Widgets\ClientTransactionsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'view' => Pages\ViewClient::route('/{record}'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
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
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
