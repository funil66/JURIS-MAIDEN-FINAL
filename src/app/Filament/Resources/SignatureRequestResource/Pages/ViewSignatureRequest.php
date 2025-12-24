<?php

namespace App\Filament\Resources\SignatureRequestResource\Pages;

use App\Filament\Resources\SignatureRequestResource;
use App\Models\SignatureRequest;
use App\Models\SignatureSigner;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Forms;
use Filament\Notifications\Notification;

class ViewSignatureRequest extends ViewRecord
{
    protected static string $resource = SignatureRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send')
                ->label('Enviar para Assinatura')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn () => $this->record->status === SignatureRequest::STATUS_DRAFT)
                ->requiresConfirmation()
                ->modalHeading('Enviar para Assinatura')
                ->modalDescription('Deseja enviar este documento para assinatura? Os signatários serão notificados por email.')
                ->action(function () {
                    $this->record->send();
                    Notification::make()
                        ->success()
                        ->title('Documento enviado!')
                        ->body('Os signatários foram notificados.')
                        ->send();
                }),

            Actions\Action::make('cancel')
                ->label('Cancelar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->canBeSigned())
                ->requiresConfirmation()
                ->modalHeading('Cancelar Solicitação')
                ->modalDescription('Deseja cancelar esta solicitação de assinatura? Esta ação não pode ser desfeita.')
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Motivo do Cancelamento')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->cancel($data['reason'] ?? null);
                    Notification::make()
                        ->warning()
                        ->title('Solicitação cancelada')
                        ->send();
                }),

            Actions\Action::make('download')
                ->label('Baixar Documento')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    return response()->download(storage_path('app/' . $this->record->document_path));
                }),

            Actions\EditAction::make()
                ->visible(fn () => $this->record->status === SignatureRequest::STATUS_DRAFT),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Resumo do Status
                Components\Section::make()
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('uid')
                                    ->label('Código')
                                    ->badge()
                                    ->color('gray')
                                    ->copyable(),

                                Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => SignatureRequest::STATUSES[$state] ?? $state)
                                    ->color(fn ($state) => SignatureRequest::STATUS_COLORS[$state] ?? 'gray')
                                    ->icon(fn ($state) => SignatureRequest::STATUS_ICONS[$state] ?? null),

                                Components\TextEntry::make('progress')
                                    ->label('Progresso')
                                    ->getStateUsing(fn ($record) => "{$record->signed_count} de {$record->total_signers} assinaturas")
                                    ->badge()
                                    ->color(fn ($record) => $record->progress === 100 ? 'success' : 'warning'),

                                Components\TextEntry::make('expires_at')
                                    ->label('Expira em')
                                    ->dateTime('d/m/Y H:i')
                                    ->color(fn ($record) => $record->isExpired() ? 'danger' : null),
                            ]),
                    ]),

                // Abas de Detalhes
                Components\Tabs::make('Tabs')
                    ->tabs([
                        // Aba: Documento
                        Components\Tabs\Tab::make('Documento')
                            ->icon('heroicon-o-document')
                            ->schema([
                                Components\Section::make('Informações do Documento')
                                    ->columns(2)
                                    ->schema([
                                        Components\TextEntry::make('document_name')
                                            ->label('Nome do Documento'),

                                        Components\TextEntry::make('signature_type')
                                            ->label('Tipo de Assinatura')
                                            ->badge()
                                            ->formatStateUsing(fn ($state) => SignatureRequest::SIGNATURE_TYPES[$state] ?? $state)
                                            ->color('info'),

                                        Components\TextEntry::make('verification_method')
                                            ->label('Método de Verificação')
                                            ->badge()
                                            ->formatStateUsing(fn ($state) => SignatureRequest::VERIFICATION_METHODS[$state] ?? $state),

                                        Components\TextEntry::make('requester.name')
                                            ->label('Solicitado por'),

                                        Components\TextEntry::make('message')
                                            ->label('Mensagem')
                                            ->columnSpanFull()
                                            ->placeholder('Nenhuma mensagem'),

                                        Components\TextEntry::make('document_hash')
                                            ->label('Hash do Documento (SHA-256)')
                                            ->columnSpanFull()
                                            ->fontFamily('mono')
                                            ->size('sm')
                                            ->copyable()
                                            ->visible(fn ($record) => $record->document_hash),
                                    ]),
                            ]),

                        // Aba: Signatários
                        Components\Tabs\Tab::make('Signatários')
                            ->icon('heroicon-o-users')
                            ->badge(fn ($record) => $record->signers->count())
                            ->schema([
                                Components\RepeatableEntry::make('signers')
                                    ->label('')
                                    ->schema([
                                        Components\Grid::make(5)
                                            ->schema([
                                                Components\TextEntry::make('name')
                                                    ->label('Nome'),

                                                Components\TextEntry::make('email')
                                                    ->label('E-mail'),

                                                Components\TextEntry::make('role')
                                                    ->label('Papel')
                                                    ->badge()
                                                    ->formatStateUsing(fn ($state) => SignatureSigner::ROLES[$state] ?? $state)
                                                    ->color('gray'),

                                                Components\TextEntry::make('status')
                                                    ->label('Status')
                                                    ->badge()
                                                    ->formatStateUsing(fn ($state) => SignatureSigner::STATUSES[$state] ?? $state)
                                                    ->color(fn ($state) => SignatureSigner::STATUS_COLORS[$state] ?? 'gray')
                                                    ->icon(fn ($state) => SignatureSigner::STATUS_ICONS[$state] ?? null),

                                                Components\TextEntry::make('signed_at')
                                                    ->label('Assinado em')
                                                    ->dateTime('d/m/Y H:i')
                                                    ->placeholder('—'),
                                            ]),
                                    ])
                                    ->columns(1),
                            ]),

                        // Aba: Auditoria
                        Components\Tabs\Tab::make('Auditoria')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->badge(fn ($record) => $record->auditLogs->count())
                            ->schema([
                                Components\RepeatableEntry::make('auditLogs')
                                    ->label('')
                                    ->schema([
                                        Components\Grid::make(4)
                                            ->schema([
                                                Components\TextEntry::make('created_at')
                                                    ->label('Data/Hora')
                                                    ->dateTime('d/m/Y H:i:s'),

                                                Components\TextEntry::make('action')
                                                    ->label('Ação')
                                                    ->badge()
                                                    ->formatStateUsing(fn ($record) => $record->action_label)
                                                    ->color(fn ($record) => $record->action_color),

                                                Components\TextEntry::make('actor_name')
                                                    ->label('Executado por'),

                                                Components\TextEntry::make('ip_address')
                                                    ->label('IP'),
                                            ]),

                                        Components\TextEntry::make('description')
                                            ->label('Descrição')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1),
                            ]),

                        // Aba: Datas
                        Components\Tabs\Tab::make('Datas')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Components\Section::make('Histórico')
                                    ->columns(3)
                                    ->schema([
                                        Components\TextEntry::make('requested_at')
                                            ->label('Solicitado em')
                                            ->dateTime('d/m/Y H:i'),

                                        Components\TextEntry::make('expires_at')
                                            ->label('Expira em')
                                            ->dateTime('d/m/Y H:i'),

                                        Components\TextEntry::make('completed_at')
                                            ->label('Concluído em')
                                            ->dateTime('d/m/Y H:i')
                                            ->placeholder('—'),

                                        Components\TextEntry::make('created_at')
                                            ->label('Criado em')
                                            ->dateTime('d/m/Y H:i'),

                                        Components\TextEntry::make('updated_at')
                                            ->label('Atualizado em')
                                            ->dateTime('d/m/Y H:i'),
                                    ]),

                                Components\Section::make('Configurações')
                                    ->columns(2)
                                    ->schema([
                                        Components\IconEntry::make('sequential_signing')
                                            ->label('Assinatura Sequencial')
                                            ->boolean(),

                                        Components\IconEntry::make('send_notifications')
                                            ->label('Notificações Ativadas')
                                            ->boolean(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
