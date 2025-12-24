<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SignatureRequestResource\Pages;
use App\Models\SignatureRequest;
use App\Models\SignatureSigner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SignatureRequestResource extends Resource
{
    protected static ?string $model = SignatureRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = 'Assinaturas';

    protected static ?string $modelLabel = 'Solicitação de Assinatura';

    protected static ?string $pluralModelLabel = 'Solicitações de Assinatura';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::pending()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Abas principais
                Forms\Components\Tabs::make('Tabs')
                    ->tabs([
                        // Aba: Documento
                        Forms\Components\Tabs\Tab::make('Documento')
                            ->icon('heroicon-o-document')
                            ->schema([
                                Forms\Components\Section::make('Informações do Documento')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('uid')
                                            ->label('Código')
                                            ->disabled()
                                            ->visible(fn ($record) => $record !== null),

                                        Forms\Components\TextInput::make('document_name')
                                            ->label('Nome do Documento')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(fn ($record) => $record ? 1 : 2),

                                        Forms\Components\FileUpload::make('document_path')
                                            ->label('Arquivo do Documento')
                                            ->required()
                                            ->directory('signatures/documents')
                                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                            ->maxSize(10240)
                                            ->columnSpanFull()
                                            ->helperText('Formatos aceitos: PDF, DOC, DOCX. Máximo 10MB.'),

                                        Forms\Components\Textarea::make('message')
                                            ->label('Mensagem para os Signatários')
                                            ->rows(3)
                                            ->columnSpanFull()
                                            ->placeholder('Mensagem opcional a ser enviada junto com a solicitação de assinatura'),
                                    ]),
                            ]),

                        // Aba: Configurações
                        Forms\Components\Tabs\Tab::make('Configurações')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Forms\Components\Section::make('Tipo de Assinatura')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Select::make('signature_type')
                                            ->label('Tipo de Assinatura')
                                            ->options(SignatureRequest::SIGNATURE_TYPES)
                                            ->default(SignatureRequest::TYPE_ELECTRONIC)
                                            ->required()
                                            ->native(false)
                                            ->helperText(fn ($state) => SignatureRequest::SIGNATURE_TYPE_DESCRIPTIONS[$state] ?? ''),

                                        Forms\Components\Select::make('verification_method')
                                            ->label('Método de Verificação')
                                            ->options(SignatureRequest::VERIFICATION_METHODS)
                                            ->default(SignatureRequest::VERIFICATION_EMAIL)
                                            ->required()
                                            ->native(false),
                                    ]),

                                Forms\Components\Section::make('Prazo e Notificações')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\DateTimePicker::make('expires_at')
                                            ->label('Data de Expiração')
                                            ->default(now()->addDays(30))
                                            ->minDate(now())
                                            ->native(false),

                                        Forms\Components\Toggle::make('send_notifications')
                                            ->label('Enviar Notificações')
                                            ->default(true)
                                            ->helperText('Enviar emails para os signatários'),

                                        Forms\Components\Toggle::make('sequential_signing')
                                            ->label('Assinatura Sequencial')
                                            ->default(false)
                                            ->helperText('Cada signatário deve assinar na ordem definida'),
                                    ]),
                            ]),

                        // Aba: Signatários
                        Forms\Components\Tabs\Tab::make('Signatários')
                            ->icon('heroicon-o-users')
                            ->schema([
                                Forms\Components\Repeater::make('signers')
                                    ->label('')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Nome')
                                                    ->required()
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('email')
                                                    ->label('E-mail')
                                                    ->email()
                                                    ->required()
                                                    ->maxLength(255),

                                                Forms\Components\TextInput::make('phone')
                                                    ->label('Telefone')
                                                    ->tel()
                                                    ->maxLength(20),

                                                Forms\Components\TextInput::make('document_number')
                                                    ->label('CPF/CNPJ')
                                                    ->maxLength(18),
                                            ]),

                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Select::make('role')
                                                    ->label('Papel')
                                                    ->options(SignatureSigner::ROLES)
                                                    ->default(SignatureSigner::ROLE_SIGNER)
                                                    ->required()
                                                    ->native(false),

                                                Forms\Components\TextInput::make('signing_order')
                                                    ->label('Ordem')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(1),

                                                Forms\Components\Select::make('user_id')
                                                    ->label('Usuário do Sistema')
                                                    ->relationship('user', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Signatário externo'),
                                            ]),
                                    ])
                                    ->columns(1)
                                    ->defaultItems(1)
                                    ->minItems(1)
                                    ->addActionLabel('Adicionar Signatário')
                                    ->reorderable()
                                    ->reorderableWithButtons()
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Novo Signatário'),
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

                Tables\Columns\TextColumn::make('document_name')
                    ->label('Documento')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->document_name),

                Tables\Columns\TextColumn::make('signers_count')
                    ->label('Signatários')
                    ->counts('signers')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('progress')
                    ->label('Progresso')
                    ->getStateUsing(fn ($record) => $record->signed_count . '/' . $record->total_signers)
                    ->badge()
                    ->color(fn ($record) => $record->progress === 100 ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('signature_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => SignatureRequest::SIGNATURE_TYPES[$state] ?? $state)
                    ->color('info'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => SignatureRequest::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => SignatureRequest::STATUS_COLORS[$state] ?? 'gray')
                    ->icon(fn ($state) => SignatureRequest::STATUS_ICONS[$state] ?? null),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expira em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : null),

                Tables\Columns\TextColumn::make('requester.name')
                    ->label('Solicitado por')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(SignatureRequest::STATUSES)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('signature_type')
                    ->label('Tipo de Assinatura')
                    ->options(SignatureRequest::SIGNATURE_TYPES),

                Tables\Filters\Filter::make('expired')
                    ->label('Expiradas')
                    ->query(fn (Builder $query) => $query->where('status', SignatureRequest::STATUS_EXPIRED)),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expirando em 7 dias')
                    ->query(fn (Builder $query) => $query->expiringSoon(7)),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('send')
                        ->label('Enviar para Assinatura')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === SignatureRequest::STATUS_DRAFT)
                        ->requiresConfirmation()
                        ->modalHeading('Enviar para Assinatura')
                        ->modalDescription('Deseja enviar este documento para assinatura? Os signatários serão notificados.')
                        ->action(function ($record) {
                            $record->send();
                        }),

                    Tables\Actions\Action::make('cancel')
                        ->label('Cancelar')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => $record->canBeSigned())
                        ->requiresConfirmation()
                        ->modalHeading('Cancelar Solicitação')
                        ->modalDescription('Deseja cancelar esta solicitação de assinatura? Esta ação não pode ser desfeita.')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Motivo do Cancelamento')
                                ->rows(3),
                        ])
                        ->action(function ($record, array $data) {
                            $record->cancel($data['reason'] ?? null);
                        }),

                    Tables\Actions\Action::make('download')
                        ->label('Baixar Documento')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->url(fn ($record) => route('signatures.download', $record))
                        ->openUrlInNewTab(),

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
            'index' => Pages\ListSignatureRequests::route('/'),
            'create' => Pages\CreateSignatureRequest::route('/create'),
            'view' => Pages\ViewSignatureRequest::route('/{record}'),
            'edit' => Pages\EditSignatureRequest::route('/{record}/edit'),
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
