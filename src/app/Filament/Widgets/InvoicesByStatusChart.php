<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;

class InvoicesByStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Faturas por Status';

    protected static ?int $sort = 9;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '300px';

    protected static ?string $pollingInterval = '120s';

    protected function getData(): array
    {
        $statuses = [
            'draft' => ['label' => 'Rascunho', 'color' => 'rgb(156, 163, 175)'],
            'pending' => ['label' => 'Pendente', 'color' => 'rgb(234, 179, 8)'],
            'partial' => ['label' => 'Parcial', 'color' => 'rgb(59, 130, 246)'],
            'paid' => ['label' => 'Paga', 'color' => 'rgb(34, 197, 94)'],
            'overdue' => ['label' => 'Vencida', 'color' => 'rgb(239, 68, 68)'],
            'cancelled' => ['label' => 'Cancelada', 'color' => 'rgb(107, 114, 128)'],
        ];

        $data = [];
        $labels = [];
        $colors = [];

        foreach ($statuses as $status => $config) {
            $count = Invoice::where('status', $status)->count();
            if ($count > 0) {
                $data[] = $count;
                $labels[] = $config['label'] . ' (' . $count . ')';
                $colors[] = $config['color'];
            }
        }

        // Se não houver dados, mostrar vazio
        if (empty($data)) {
            return [
                'datasets' => [['data' => [1], 'backgroundColor' => ['rgb(229, 231, 235)']]],
                'labels' => ['Sem faturas'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Faturas',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                ],
            ],
            'cutout' => '50%',
        ];
    }
}
