<?php

namespace App\Filament\Resources\DigitalCertificateResource\Pages;

use App\Filament\Resources\DigitalCertificateResource;
use App\Models\DigitalCertificate;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;

class ViewDigitalCertificate extends ViewRecord
{
    protected static string $resource = DigitalCertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('set_default')
                ->label('Definir como Padrão')
                ->icon('heroicon-o-star')
                ->color('warning')
                ->visible(fn () => !$this->record->is_default && $this->record->isValid())
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->setAsDefault();
                    Notification::make()
                        ->success()
                        ->title('Certificado definido como padrão')
                        ->send();
                }),

            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Status Card
                Components\Section::make()
                    ->schema([
                        Components\Grid::make(5)
                            ->schema([
                                Components\TextEntry::make('uid')
                                    ->label('Código')
                                    ->badge()
                                    ->color('gray')
                                    ->copyable(),

                                Components\TextEntry::make('type')
                                    ->label('Tipo')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => DigitalCertificate::TYPES[$state] ?? $state)
                                    ->color('info'),

                                Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => DigitalCertificate::STATUSES[$state] ?? $state)
                                    ->color(fn ($state) => DigitalCertificate::STATUS_COLORS[$state] ?? 'gray'),

                                Components\TextEntry::make('days_remaining')
                                    ->label('Dias Restantes')
                                    ->badge()
                                    ->color(fn ($state) => match (true) {
                                        $state === 0 || $state === null => 'danger',
                                        $state <= 30 => 'warning',
                                        default => 'success',
                                    }),

                                Components\IconEntry::make('is_default')
                                    ->label('Padrão')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-star')
                                    ->trueColor('warning'),
                            ]),
                    ]),

                Components\Tabs::make('Tabs')
                    ->tabs([
                        // Aba: Informações
                        Components\Tabs\Tab::make('Informações')
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Components\Section::make('Dados do Certificado')
                                    ->columns(2)
                                    ->schema([
                                        Components\TextEntry::make('name')
                                            ->label('Nome'),

                                        Components\TextEntry::make('user.name')
                                            ->label('Proprietário'),

                                        Components\TextEntry::make('description')
                                            ->label('Descrição')
                                            ->columnSpanFull()
                                            ->placeholder('Sem descrição'),
                                    ]),

                                Components\Section::make('Dados do Titular')
                                    ->columns(3)
                                    ->schema([
                                        Components\TextEntry::make('holder_name')
                                            ->label('Nome do Titular'),

                                        Components\TextEntry::make('holder_document')
                                            ->label('CPF/CNPJ')
                                            ->placeholder('—'),

                                        Components\TextEntry::make('holder_email')
                                            ->label('E-mail')
                                            ->placeholder('—'),
                                    ]),
                            ]),

                        // Aba: Detalhes Técnicos
                        Components\Tabs\Tab::make('Detalhes Técnicos')
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                Components\Section::make('Informações do Certificado')
                                    ->columns(2)
                                    ->schema([
                                        Components\TextEntry::make('serial_number')
                                            ->label('Número de Série')
                                            ->fontFamily('mono')
                                            ->copyable()
                                            ->placeholder('Não validado'),

                                        Components\TextEntry::make('issuer')
                                            ->label('Autoridade Certificadora')
                                            ->placeholder('Não validado'),

                                        Components\TextEntry::make('valid_from')
                                            ->label('Válido Desde')
                                            ->dateTime('d/m/Y H:i')
                                            ->placeholder('—'),

                                        Components\TextEntry::make('valid_until')
                                            ->label('Válido Até')
                                            ->dateTime('d/m/Y H:i')
                                            ->color(fn ($record) => match (true) {
                                                $record->valid_until?->isPast() => 'danger',
                                                $record->isExpiringSoon(30) => 'warning',
                                                default => null,
                                            })
                                            ->placeholder('—'),
                                    ]),
                            ]),

                        // Aba: Assinaturas
                        Components\Tabs\Tab::make('Assinaturas')
                            ->icon('heroicon-o-pencil-square')
                            ->badge(fn ($record) => $record->signers->count())
                            ->schema([
                                Components\RepeatableEntry::make('signers')
                                    ->label('Assinaturas realizadas com este certificado')
                                    ->schema([
                                        Components\Grid::make(4)
                                            ->schema([
                                                Components\TextEntry::make('signatureRequest.document_name')
                                                    ->label('Documento'),

                                                Components\TextEntry::make('signed_at')
                                                    ->label('Data da Assinatura')
                                                    ->dateTime('d/m/Y H:i'),

                                                Components\TextEntry::make('name')
                                                    ->label('Signatário'),

                                                Components\TextEntry::make('signatureRequest.status')
                                                    ->label('Status')
                                                    ->badge(),
                                            ]),
                                    ])
                                    ->columns(1)
                                    ->placeholder('Nenhuma assinatura realizada com este certificado'),
                            ]),

                        // Aba: Datas
                        Components\Tabs\Tab::make('Datas')
                            ->icon('heroicon-o-calendar')
                            ->schema([
                                Components\Section::make('Histórico')
                                    ->columns(2)
                                    ->schema([
                                        Components\TextEntry::make('created_at')
                                            ->label('Cadastrado em')
                                            ->dateTime('d/m/Y H:i'),

                                        Components\TextEntry::make('updated_at')
                                            ->label('Atualizado em')
                                            ->dateTime('d/m/Y H:i'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
