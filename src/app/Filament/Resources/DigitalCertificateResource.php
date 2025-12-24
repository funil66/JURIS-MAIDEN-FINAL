<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DigitalCertificateResource\Pages;
use App\Models\DigitalCertificate;
use App\Services\DigitalSignatureService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;

class DigitalCertificateResource extends Resource
{
    protected static ?string $model = DigitalCertificate::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Assinaturas';

    protected static ?string $modelLabel = 'Certificado Digital';

    protected static ?string $pluralModelLabel = 'Certificados Digitais';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $expiringSoon = static::getModel()::expiringSoon(30)->count();
        return $expiringSoon > 0 ? (string) $expiringSoon : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        // Aba: Informações Básicas
                        Forms\Components\Tabs\Tab::make('Informações')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\Section::make('Dados do Certificado')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('uid')
                                            ->label('Código')
                                            ->disabled()
                                            ->visible(fn ($record) => $record !== null),

                                        Forms\Components\TextInput::make('name')
                                            ->label('Nome do Certificado')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Ex: Certificado e-CPF João Silva')
                                            ->columnSpan(fn ($record) => $record ? 1 : 2),

                                        Forms\Components\Select::make('type')
                                            ->label('Tipo do Certificado')
                                            ->options(DigitalCertificate::TYPES)
                                            ->default(DigitalCertificate::TYPE_A1)
                                            ->required()
                                            ->native(false),

                                        Forms\Components\Select::make('user_id')
                                            ->label('Usuário Proprietário')
                                            ->relationship('user', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->default(fn () => auth()->id()),

                                        Forms\Components\Textarea::make('description')
                                            ->label('Descrição')
                                            ->rows(2)
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Dados do Titular')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('holder_name')
                                            ->label('Nome do Titular')
                                            ->required()
                                            ->maxLength(255),

                                        Forms\Components\TextInput::make('holder_document')
                                            ->label('CPF/CNPJ')
                                            ->maxLength(18),

                                        Forms\Components\TextInput::make('holder_email')
                                            ->label('E-mail')
                                            ->email()
                                            ->maxLength(255),
                                    ]),
                            ]),

                        // Aba: Arquivo do Certificado (apenas para A1)
                        Forms\Components\Tabs\Tab::make('Arquivo')
                            ->icon('heroicon-o-document')
                            ->visible(fn ($get) => $get('type') === DigitalCertificate::TYPE_A1)
                            ->schema([
                                Forms\Components\Section::make('Certificado A1')
                                    ->description('Faça upload do arquivo .pfx ou .p12 do seu certificado digital')
                                    ->schema([
                                        Forms\Components\FileUpload::make('certificate_path')
                                            ->label('Arquivo do Certificado')
                                            ->directory('certificates')
                                            ->visibility('private')
                                            ->acceptedFileTypes([
                                                'application/x-pkcs12',
                                                'application/pkcs12',
                                                '.pfx',
                                                '.p12',
                                            ])
                                            ->maxSize(1024)
                                            ->helperText('Formatos aceitos: .pfx, .p12. Máximo 1MB.')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('certificate_password')
                                            ->label('Senha do Certificado')
                                            ->password()
                                            ->revealable()
                                            ->maxLength(255)
                                            ->helperText('A senha será armazenada de forma criptografada'),
                                    ]),
                            ]),

                        // Aba: Validade
                        Forms\Components\Tabs\Tab::make('Validade')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Forms\Components\Section::make('Informações de Validade')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('serial_number')
                                            ->label('Número de Série')
                                            ->maxLength(255)
                                            ->disabled()
                                            ->visible(fn ($record) => $record !== null),

                                        Forms\Components\TextInput::make('issuer')
                                            ->label('Autoridade Certificadora')
                                            ->maxLength(255)
                                            ->disabled()
                                            ->visible(fn ($record) => $record !== null),

                                        Forms\Components\DateTimePicker::make('valid_from')
                                            ->label('Válido Desde')
                                            ->disabled()
                                            ->visible(fn ($record) => $record !== null),

                                        Forms\Components\DateTimePicker::make('valid_until')
                                            ->label('Válido Até')
                                            ->disabled()
                                            ->visible(fn ($record) => $record !== null),

                                        Forms\Components\Select::make('status')
                                            ->label('Status')
                                            ->options(DigitalCertificate::STATUSES)
                                            ->default(DigitalCertificate::STATUS_PENDING)
                                            ->required()
                                            ->native(false),

                                        Forms\Components\Toggle::make('is_default')
                                            ->label('Certificado Padrão')
                                            ->helperText('Usar este certificado como padrão para assinaturas'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->label('Código')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->name),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => DigitalCertificate::TYPES[$state] ?? $state)
                    ->color('info'),

                Tables\Columns\TextColumn::make('holder_name')
                    ->label('Titular')
                    ->searchable()
                    ->limit(25),

                Tables\Columns\TextColumn::make('issuer')
                    ->label('AC Emissora')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Expira em')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => match (true) {
                        $record->valid_until?->isPast() => 'danger',
                        $record->isExpiringSoon(30) => 'warning',
                        default => null,
                    }),

                Tables\Columns\TextColumn::make('days_remaining')
                    ->label('Dias Restantes')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === 0 || $state === null => 'danger',
                        $state <= 30 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => DigitalCertificate::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => DigitalCertificate::STATUS_COLORS[$state] ?? 'gray'),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Padrão')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->trueColor('warning'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Proprietário')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(DigitalCertificate::STATUSES),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(DigitalCertificate::TYPES),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expirando em 30 dias')
                    ->query(fn (Builder $query) => $query->expiringSoon(30)),

                Tables\Filters\Filter::make('expired')
                    ->label('Expirados')
                    ->query(fn (Builder $query) => $query->where('status', DigitalCertificate::STATUS_EXPIRED)),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('set_default')
                        ->label('Definir como Padrão')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->visible(fn ($record) => !$record->is_default && $record->isValid())
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->setAsDefault();
                            Notification::make()
                                ->success()
                                ->title('Certificado definido como padrão')
                                ->send();
                        }),

                    Tables\Actions\Action::make('validate')
                        ->label('Validar Certificado')
                        ->icon('heroicon-o-shield-check')
                        ->color('info')
                        ->visible(fn ($record) => $record->type === DigitalCertificate::TYPE_A1 && $record->certificate_path)
                        ->form([
                            Forms\Components\TextInput::make('password')
                                ->label('Senha do Certificado')
                                ->password()
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $service = app(DigitalSignatureService::class);
                            $path = Storage::path($record->certificate_path);
                            
                            $result = $service->validateCertificate($path, $data['password']);

                            if ($result['valid']) {
                                $record->update([
                                    'serial_number' => $result['serial_number'],
                                    'issuer' => $result['issuer'],
                                    'holder_name' => $result['holder_name'],
                                    'holder_document' => $result['holder_document'],
                                    'holder_email' => $result['holder_email'],
                                    'valid_from' => $result['valid_from'],
                                    'valid_until' => $result['valid_until'],
                                    'status' => DigitalCertificate::STATUS_ACTIVE,
                                ]);

                                Notification::make()
                                    ->success()
                                    ->title('Certificado validado com sucesso!')
                                    ->body("Válido até: " . $result['valid_until']->format('d/m/Y'))
                                    ->send();
                            } else {
                                Notification::make()
                                    ->danger()
                                    ->title('Erro na validação')
                                    ->body($result['error'] ?? 'Não foi possível validar o certificado')
                                    ->send();
                            }
                        }),

                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListDigitalCertificates::route('/'),
            'create' => Pages\CreateDigitalCertificate::route('/create'),
            'view' => Pages\ViewDigitalCertificate::route('/{record}'),
            'edit' => Pages\EditDigitalCertificate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
