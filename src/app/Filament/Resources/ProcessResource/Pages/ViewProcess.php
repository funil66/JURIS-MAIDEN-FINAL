<?php

namespace App\Filament\Resources\ProcessResource\Pages;

use App\Filament\Resources\ProcessResource;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;

class ViewProcess extends ViewRecord
{
    protected static string $resource = ProcessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('addSubprocess')
                ->label('Adicionar Subprocesso')
                ->icon('heroicon-o-plus-circle')
                ->color('info')
                ->url(fn () => route('filament.funil.resources.processes.create', [
                    'parent_id' => $this->record->id,
                    'client_id' => $this->record->client_id,
                ])),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Identificação')
                    ->columns(4)
                    ->schema([
                        Components\TextEntry::make('uid')
                            ->label('ID')
                            ->badge()
                            ->color('primary'),

                        Components\TextEntry::make('title')
                            ->label('Título')
                            ->columnSpan(3),

                        Components\TextEntry::make('formatted_cnj')
                            ->label('Número CNJ')
                            ->placeholder('Não informado'),

                        Components\TextEntry::make('old_number')
                            ->label('Número Antigo')
                            ->placeholder('Não informado'),

                        Components\TextEntry::make('internal_code')
                            ->label('Código Interno')
                            ->placeholder('Não informado'),

                        Components\TextEntry::make('client.name')
                            ->label('Cliente')
                            ->url(fn ($record) => route('filament.funil.resources.clients.view', $record->client_id)),
                    ]),

                Components\Section::make('Status e Classificação')
                    ->columns(4)
                    ->schema([
                        Components\TextEntry::make('status_label')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($record) => match($record->status) {
                                'active' => 'success',
                                'suspended' => 'warning',
                                'prospecting' => 'info',
                                'closed_won' => 'success',
                                'closed_lost' => 'danger',
                                default => 'gray',
                            }),

                        Components\TextEntry::make('phase_label')
                            ->label('Fase')
                            ->badge()
                            ->color('gray'),

                        Components\TextEntry::make('instance_label')
                            ->label('Instância'),

                        Components\TextEntry::make('matter_type')
                            ->label('Área do Direito')
                            ->formatStateUsing(fn (?string $state) => 
                                $state ? (\App\Models\Process::getMatterTypeOptions()[$state] ?? $state) : '-'
                            ),

                        Components\IconEntry::make('is_urgent')
                            ->label('Urgente')
                            ->boolean(),

                        Components\IconEntry::make('is_confidential')
                            ->label('Sigiloso')
                            ->boolean(),

                        Components\IconEntry::make('has_injunction')
                            ->label('Possui Liminar')
                            ->boolean(),

                        Components\IconEntry::make('is_pro_bono')
                            ->label('Pro Bono')
                            ->boolean(),
                    ]),

                Components\Section::make('Localização')
                    ->columns(4)
                    ->collapsible()
                    ->schema([
                        Components\TextEntry::make('court')
                            ->label('Tribunal')
                            ->formatStateUsing(fn (?string $state) => 
                                $state ? (\App\Models\Process::getCourtOptions()[$state] ?? $state) : '-'
                            ),

                        Components\TextEntry::make('jurisdiction')
                            ->label('Comarca')
                            ->placeholder('-'),

                        Components\TextEntry::make('court_division')
                            ->label('Vara')
                            ->placeholder('-'),

                        Components\TextEntry::make('state')
                            ->label('UF')
                            ->placeholder('-'),
                    ]),

                Components\Section::make('Partes')
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        Components\TextEntry::make('plaintiff')
                            ->label('Autor/Requerente')
                            ->placeholder('Não informado'),

                        Components\TextEntry::make('defendant')
                            ->label('Réu/Requerido')
                            ->placeholder('Não informado'),

                        Components\TextEntry::make('client_role')
                            ->label('Papel do Cliente')
                            ->formatStateUsing(fn (?string $state): string => $state ? (\App\Models\Process::getClientRoleOptions()[$state] ?? $state) : '-'),
                    ]),

                Components\Section::make('Valores')
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        Components\TextEntry::make('case_value')
                            ->label('Valor da Causa')
                            ->money('BRL')
                            ->placeholder('R$ 0,00'),

                        Components\TextEntry::make('contingency_value')
                            ->label('Valor Contingencial')
                            ->money('BRL')
                            ->placeholder('R$ 0,00'),

                        Components\TextEntry::make('sentence_value')
                            ->label('Valor da Sentença')
                            ->money('BRL')
                            ->placeholder('R$ 0,00'),
                    ]),

                Components\Section::make('Datas')
                    ->columns(4)
                    ->collapsible()
                    ->schema([
                        Components\TextEntry::make('distribution_date')
                            ->label('Distribuição')
                            ->date('d/m/Y')
                            ->placeholder('-'),

                        Components\TextEntry::make('filing_date')
                            ->label('Ajuizamento')
                            ->date('d/m/Y')
                            ->placeholder('-'),

                        Components\TextEntry::make('transit_date')
                            ->label('Trânsito em Julgado')
                            ->date('d/m/Y')
                            ->placeholder('-'),

                        Components\TextEntry::make('closing_date')
                            ->label('Encerramento')
                            ->date('d/m/Y')
                            ->placeholder('-'),
                    ]),

                Components\Section::make('Observações')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Components\TextEntry::make('strategy')
                            ->label('Estratégia')
                            ->html()
                            ->columnSpanFull()
                            ->placeholder('Nenhuma estratégia definida'),

                        Components\TextEntry::make('risk_assessment')
                            ->label('Avaliação de Risco')
                            ->columnSpanFull()
                            ->placeholder('Nenhuma avaliação'),

                        Components\TextEntry::make('notes')
                            ->label('Observações')
                            ->columnSpanFull()
                            ->placeholder('Nenhuma observação'),
                    ]),

                Components\Section::make('Processo Principal')
                    ->visible(fn ($record) => $record->parent_id !== null)
                    ->schema([
                        Components\TextEntry::make('parent.title')
                            ->label('Título')
                            ->url(fn ($record) => $record->parent_id 
                                ? route('filament.funil.resources.processes.view', $record->parent_id) 
                                : null
                            ),

                        Components\TextEntry::make('parent.uid')
                            ->label('ID')
                            ->badge()
                            ->color('info'),
                    ])
                    ->columns(2),
            ]);
    }
}
