<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Contract;
use App\Models\TimeEntry;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class FinancialJuridicalChart extends ChartWidget
{
    protected static ?string $heading = 'Faturamento vs Horas (Últimos 6 meses)';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '350px';

    protected static ?string $pollingInterval = '120s';

    protected function getData(): array
    {
        $months = collect();
        $invoiced = collect();
        $received = collect();
        $hours = collect();

        // Últimos 6 meses
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months->push($date->translatedFormat('M/Y'));

            // Valor faturado no mês
            $monthInvoiced = Invoice::whereMonth('issue_date', $date->month)
                ->whereYear('issue_date', $date->year)
                ->whereNotIn('status', ['cancelled', 'draft'])
                ->sum('total');

            // Valor recebido no mês
            $monthReceived = Invoice::whereMonth('paid_date', $date->month)
                ->whereYear('paid_date', $date->year)
                ->where('status', 'paid')
                ->sum('total');

            // Horas trabalhadas no mês (convertido para valor: horas * R$200 exemplo)
            $monthHours = TimeEntry::whereMonth('work_date', $date->month)
                ->whereYear('work_date', $date->year)
                ->where('is_billable', true)
                ->sum('duration_minutes') / 60;

            $invoiced->push($monthInvoiced);
            $received->push($monthReceived);
            $hours->push(round($monthHours, 1));
        }

        return [
            'datasets' => [
                [
                    'label' => 'Faturado (R$)',
                    'data' => $invoiced->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Recebido (R$)',
                    'data' => $received->toArray(),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Horas Faturáveis',
                    'data' => $hours->toArray(),
                    'type' => 'line',
                    'backgroundColor' => 'rgba(249, 115, 22, 0.5)',
                    'borderColor' => 'rgb(249, 115, 22)',
                    'borderWidth' => 3,
                    'yAxisID' => 'y1',
                    'tension' => 0.3,
                    'fill' => false,
                    'pointRadius' => 5,
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
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            // Improve responsiveness and animations
            'responsive' => true,
            'maintainAspectRatio' => false,
            'animation' => [
                'duration' => 800,
                'easing' => 'easeOutQuart',
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return 'R$ ' + value.toLocaleString('pt-BR'); }",
                    ],
                    'grid' => [
                        'drawOnChartArea' => true,
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => "function(value) { return value + 'h'; }",
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
        ];
    }
}
