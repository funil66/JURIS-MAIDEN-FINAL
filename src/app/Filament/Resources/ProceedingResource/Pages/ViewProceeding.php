<?php

namespace App\Filament\Resources\ProceedingResource\Pages;

use App\Filament\Resources\ProceedingResource;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;

class ViewProceeding extends ViewRecord
{
    protected static string $resource = ProceedingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('completeDeadline')
                ->label('Cumprir Prazo')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->has_deadline && !$this->record->deadline_completed)
                ->requiresConfirmation()
                ->action(fn () => $this->record->completeDeadline()),
            Actions\Action::make('completeAction')
                ->label('Concluir Ação')
                ->icon('heroicon-o-check')
                ->color('info')
                ->visible(fn () => $this->record->requires_action && !$this->record->action_completed)
                ->requiresConfirmation()
                ->action(fn () => $this->record->completeAction()),
            Actions\Action::make('markAnalyzed')
                ->label('Marcar Analisado')
                ->icon('heroicon-o-eye')
                ->visible(fn () => $this->record->status === 'pending')
                ->action(fn () => $this->record->markAsAnalyzed()),
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

                        Components\TextEntry::make('proceeding_date')
                            ->label('Data')
                            ->date('d/m/Y'),

                        Components\TextEntry::make('type_label')
                            ->label('Tipo')
                            ->badge()
                            ->color('gray'),

                        Components\TextEntry::make('source_label')
                            ->label('Fonte'),

                        Components\TextEntry::make('title')
                            ->label('Título')
                            ->columnSpanFull()
                            ->weight('bold'),

                        Components\TextEntry::make('process.title')
                            ->label('Processo')
                            ->columnSpan(2)
                            ->url(fn ($record) => route('filament.funil.resources.processes.view', $record->process_id)),

                        Components\TextEntry::make('process.uid')
                            ->label('ID Processo')
                            ->badge()
                            ->color('info'),

                        Components\TextEntry::make('process.client.name')
                            ->label('Cliente'),
                    ]),

                Components\Section::make('Conteúdo')
                    ->collapsible()
                    ->schema([
                        Components\TextEntry::make('content')
                            ->label('')
                            ->html()
                            ->columnSpanFull()
                            ->placeholder('Nenhum conteúdo'),
                    ]),

                Components\Section::make('Prazo')
                    ->visible(fn ($record) => $record->has_deadline)
                    ->columns(4)
                    ->schema([
                        Components\TextEntry::make('deadline_date')
                            ->label('Data do Prazo')
                            ->date('d/m/Y')
                            ->color(fn ($record) => $record->deadline_color),

                        Components\TextEntry::make('deadline_days')
                            ->label('Dias Úteis')
                            ->placeholder('-'),

                        Components\TextEntry::make('days_until_deadline')
                            ->label('Dias Restantes')
                            ->formatStateUsing(fn (?int $state) => $state !== null 
                                ? ($state < 0 ? abs($state) . ' dias de atraso' : $state . ' dias')
                                : '-'
                            )
                            ->color(fn ($record) => $record->deadline_color),

                        Components\IconEntry::make('deadline_completed')
                            ->label('Cumprido')
                            ->boolean(),

                        Components\TextEntry::make('deadline_completed_at')
                            ->label('Cumprido em')
                            ->dateTime('d/m/Y H:i')
                            ->visible(fn ($record) => $record->deadline_completed)
                            ->placeholder('-'),
                    ]),

                Components\Section::make('Ação Necessária')
                    ->visible(fn ($record) => $record->requires_action)
                    ->columns(3)
                    ->schema([
                        Components\TextEntry::make('action_description')
                            ->label('Descrição da Ação')
                            ->columnSpanFull(),

                        Components\TextEntry::make('actionResponsible.name')
                            ->label('Responsável')
                            ->placeholder('Não definido'),

                        Components\IconEntry::make('action_completed')
                            ->label('Concluída')
                            ->boolean(),

                        Components\TextEntry::make('action_completed_at')
                            ->label('Concluída em')
                            ->dateTime('d/m/Y H:i')
                            ->visible(fn ($record) => $record->action_completed)
                            ->placeholder('-'),
                    ]),

                Components\Section::make('Status')
                    ->columns(4)
                    ->schema([
                        Components\TextEntry::make('status_label')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($record) => match($record->status) {
                                'pending' => 'warning',
                                'analyzed' => 'info',
                                'actioned' => 'success',
                                'archived' => 'gray',
                                default => 'gray',
                            }),

                        Components\IconEntry::make('is_important')
                            ->label('Importante')
                            ->boolean(),

                        Components\IconEntry::make('is_favorable')
                            ->label('Favorável')
                            ->boolean()
                            ->visible(fn ($record) => $record->is_favorable !== null),

                        Components\TextEntry::make('user.name')
                            ->label('Registrado por'),
                    ]),

                Components\Section::make('Observações')
                    ->collapsible()
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        Components\TextEntry::make('notes')
                            ->label('Observações')
                            ->placeholder('Nenhuma observação'),

                        Components\TextEntry::make('internal_notes')
                            ->label('Notas Internas')
                            ->placeholder('Nenhuma nota'),
                    ]),

                Components\Section::make('Metadados')
                    ->collapsible()
                    ->collapsed()
                    ->columns(3)
                    ->schema([
                        Components\TextEntry::make('external_id')
                            ->label('ID Externo')
                            ->placeholder('-'),

                        Components\TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i'),

                        Components\TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime('d/m/Y H:i'),
                    ]),
            ]);
    }
}
