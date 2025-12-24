<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Process;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class FinancialTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tendência Financeira';

    protected static ?string $description = 'Receitas vs Despesas dos últimos 6 meses';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected static ?string $maxHeight = '280px';

    protected static ?string $pollingInterval = null;

    protected function getData(): array
    {
        $months = collect();
        $received = collect();
        $pending = collect();

        // Últimos 6 meses
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months->push($date->locale('pt_BR')->isoFormat('MMM'));

            // Receitas recebidas
            $receivedAmount = Invoice::paid()
                ->whereMonth('paid_date', $date->month)
                ->whereYear('paid_date', $date->year)
                ->sum('total');
            $received->push($receivedAmount);

            // Faturas emitidas (pendentes + pagas)
            $pendingAmount = Invoice::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->sum('total');
            $pending->push($pendingAmount);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Recebido',
                    'data' => $received->toArray(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Faturado',
                    'data' => $pending->toArray(),
                    'backgroundColor' => 'rgba(99, 102, 241, 0.2)',
                    'borderColor' => 'rgb(99, 102, 241)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
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
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
        ];
    }
}
