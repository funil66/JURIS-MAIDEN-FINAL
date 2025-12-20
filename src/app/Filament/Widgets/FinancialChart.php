<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class FinancialChart extends ChartWidget
{
    protected static ?string $heading = 'Fluxo de Caixa (Últimos 6 meses)';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $months = collect();
        $incomes = collect();
        $expenses = collect();

        // Últimos 6 meses
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months->push($date->translatedFormat('M/Y'));

            $monthIncome = Transaction::income()
                ->paid()
                ->whereMonth('competence_date', $date->month)
                ->whereYear('competence_date', $date->year)
                ->sum('net_amount');

            $monthExpense = Transaction::expense()
                ->paid()
                ->whereMonth('competence_date', $date->month)
                ->whereYear('competence_date', $date->year)
                ->sum('net_amount');

            $incomes->push($monthIncome);
            $expenses->push($monthExpense);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Receitas',
                    'data' => $incomes->toArray(),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'fill' => true,
                ],
                [
                    'label' => 'Despesas',
                    'data' => $expenses->toArray(),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'fill' => true,
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return 'R$ ' + value.toLocaleString('pt-BR'); }",
                    ],
                ],
            ],
        ];
    }
}
