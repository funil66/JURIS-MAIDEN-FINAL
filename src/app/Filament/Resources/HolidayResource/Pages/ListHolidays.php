<?php

namespace App\Filament\Resources\HolidayResource\Pages;

use App\Filament\Resources\HolidayResource;
use App\Models\Holiday;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListHolidays extends ListRecords
{
    protected static string $resource = HolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo Feriado'),

            Actions\Action::make('seed_holidays')
                ->label('Importar Feriados BR')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Importar Feriados Brasileiros')
                ->modalDescription('Isso irá importar/atualizar os feriados nacionais brasileiros para o ano atual e o próximo ano. Feriados existentes serão atualizados.')
                ->form([
                    \Filament\Forms\Components\TextInput::make('year')
                        ->label('Ano')
                        ->numeric()
                        ->default(now()->year)
                        ->required()
                        ->minValue(2020)
                        ->maxValue(2050),

                    \Filament\Forms\Components\Toggle::make('next_year')
                        ->label('Incluir próximo ano')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    Holiday::seedBrazilianHolidays((int) $data['year']);
                    
                    if ($data['next_year']) {
                        Holiday::seedBrazilianHolidays((int) $data['year'] + 1);
                    }

                    Notification::make()
                        ->title('Feriados Importados!')
                        ->body('Os feriados brasileiros foram importados com sucesso.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
