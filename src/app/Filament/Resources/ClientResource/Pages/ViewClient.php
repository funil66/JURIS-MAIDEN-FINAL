<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\Client;
use App\Models\Service;
use App\Models\Transaction;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;
use Illuminate\Contracts\Support\Htmlable;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;

    public function getTitle(): string|Htmlable
    {
        return $this->record->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->record->type === 'pf' ? 'Pessoa Física' : 'Pessoa Jurídica';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil'),
            
            Actions\Action::make('novo_servico')
                ->label('Novo Serviço')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->url(fn () => route('filament.funil.resources.services.create', ['client_id' => $this->record->id])),
            
            Actions\Action::make('whatsapp')
                ->label('WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('success')
                ->url(fn () => 'https://wa.me/55' . preg_replace('/[^0-9]/', '', $this->record->whatsapp))
                ->openUrlInNewTab()
                ->visible(fn () => !empty($this->record->whatsapp)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // DADOS DO CLIENTE
                Section::make('📋 Dados Cadastrais')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nome')
                            ->weight(FontWeight::Bold)
                            ->size(TextEntry\TextEntrySize::Large),
                        
                        TextEntry::make('document')
                            ->label(fn ($record) => $record->type === 'pf' ? 'CPF' : 'CNPJ')
                            ->copyable(),
                        
                        TextEntry::make('rg')
                            ->label('RG')
                            ->visible(fn ($record) => $record->type === 'pf' && $record->rg)
                            ->placeholder('-'),
                        
                        TextEntry::make('oab')
                            ->label('OAB')
                            ->visible(fn ($record) => !empty($record->oab))
                            ->badge()
                            ->color('warning'),

                        TextEntry::make('company_name')
                            ->label('Razão Social')
                            ->visible(fn ($record) => $record->type === 'pj'),
                        
                        TextEntry::make('trading_name')
                            ->label('Nome Fantasia')
                            ->visible(fn ($record) => $record->type === 'pj'),
                    ]),

                Section::make('📞 Contato')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('email')
                            ->label('E-mail')
                            ->icon('heroicon-o-envelope')
                            ->copyable()
                            ->url(fn ($record) => "mailto:{$record->email}")
                            ->placeholder('-'),
                        
                        TextEntry::make('phone')
                            ->label('Telefone')
                            ->icon('heroicon-o-phone')
                            ->placeholder('-'),
                        
                        TextEntry::make('whatsapp')
                            ->label('WhatsApp')
                            ->icon('heroicon-o-chat-bubble-left-ellipsis')
                            ->url(fn ($record) => $record->whatsapp ? 'https://wa.me/55' . preg_replace('/[^0-9]/', '', $record->whatsapp) : null)
                            ->placeholder('-'),
                    ]),

                Section::make('📍 Endereço')
                    ->columns(1)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('full_address')
                            ->label('')
                            ->placeholder('Endereço não cadastrado'),
                    ]),

                // RESUMO FINANCEIRO
                Section::make('💰 Resumo Financeiro')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('total_receitas')
                            ->label('Total Receitas')
                            ->state(fn ($record) => 'R$ ' . number_format(
                                $record->transactions()->where('type', 'receita')->sum('amount'),
                                2, ',', '.'
                            ))
                            ->color('success')
                            ->weight(FontWeight::Bold),
                        
                        TextEntry::make('total_despesas')
                            ->label('Total Despesas')
                            ->state(fn ($record) => 'R$ ' . number_format(
                                $record->transactions()->where('type', 'despesa')->sum('amount'),
                                2, ',', '.'
                            ))
                            ->color('danger')
                            ->weight(FontWeight::Bold),
                        
                        TextEntry::make('saldo')
                            ->label('Saldo')
                            ->state(function ($record) {
                                $receitas = $record->transactions()->where('type', 'receita')->sum('amount');
                                $despesas = $record->transactions()->where('type', 'despesa')->sum('amount');
                                $saldo = $receitas - $despesas;
                                return 'R$ ' . number_format($saldo, 2, ',', '.');
                            })
                            ->color(function ($record) {
                                $receitas = $record->transactions()->where('type', 'receita')->sum('amount');
                                $despesas = $record->transactions()->where('type', 'despesa')->sum('amount');
                                return ($receitas - $despesas) >= 0 ? 'success' : 'danger';
                            })
                            ->weight(FontWeight::Bold),
                        
                        TextEntry::make('valor_servicos')
                            ->label('Valor em Serviços')
                            ->state(fn ($record) => 'R$ ' . number_format(
                                $record->services()->sum('total_price'),
                                2, ',', '.'
                            ))
                            ->color('info')
                            ->weight(FontWeight::Bold),
                    ]),

                // ESTATÍSTICAS
                Section::make('📊 Estatísticas')
                    ->columns(5)
                    ->schema([
                        TextEntry::make('total_servicos')
                            ->label('Total de Serviços')
                            ->state(fn ($record) => $record->services()->count())
                            ->badge()
                            ->color('primary'),
                        
                        TextEntry::make('servicos_pendentes')
                            ->label('Pendentes')
                            ->state(fn ($record) => $record->services()->where('status', 'pending')->count())
                            ->badge()
                            ->color('warning'),
                        
                        TextEntry::make('servicos_concluidos')
                            ->label('Concluídos')
                            ->state(fn ($record) => $record->services()->where('status', 'completed')->count())
                            ->badge()
                            ->color('success'),
                        
                        TextEntry::make('total_eventos')
                            ->label('Eventos')
                            ->state(fn ($record) => $record->events()->count())
                            ->badge()
                            ->color('info'),
                        
                        TextEntry::make('total_transacoes')
                            ->label('Transações')
                            ->state(fn ($record) => $record->transactions()->count())
                            ->badge()
                            ->color('gray'),
                    ]),

                // OBSERVAÇÕES
                Section::make('📝 Observações')
                    ->collapsed()
                    ->visible(fn ($record) => !empty($record->notes))
                    ->schema([
                        TextEntry::make('notes')
                            ->label('')
                            ->markdown(),
                    ]),
            ]);
    }

    protected function getFooterWidgets(): array
    {
        return [
            ClientResource\Widgets\ClientServicesWidget::class,
            ClientResource\Widgets\ClientTransactionsWidget::class,
        ];
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }
}
