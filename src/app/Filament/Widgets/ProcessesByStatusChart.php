<?php

namespace App\Filament\Widgets;

use App\Models\Process;
use Filament\Widgets\ChartWidget;

class ProcessesByStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Processos por Status';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '300px';

    protected static ?string $pollingInterval = '120s';

    protected function getData(): array
    {
        $statuses = [
            'active' => ['label' => 'Ativos', 'color' => 'rgb(34, 197, 94)'],
            'suspended' => ['label' => 'Suspensos', 'color' => 'rgb(234, 179, 8)'],
            'archived' => ['label' => 'Arquivados', 'color' => 'rgb(156, 163, 175)'],
            'closed_won' => ['label' => 'Ganhos', 'color' => 'rgb(59, 130, 246)'],
            'closed_lost' => ['label' => 'Perdidos', 'color' => 'rgb(239, 68, 68)'],
            'closed_settled' => ['label' => 'Acordos', 'color' => 'rgb(168, 85, 247)'],
        ];

        $data = [];
        $labels = [];
        $colors = [];

        foreach ($statuses as $status => $config) {
            $count = Process::where('status', $status)->count();
            if ($count > 0) {
                $data[] = $count;
                $labels[] = $config['label'] . ' (' . $count . ')';
                $colors[] = $config['color'];
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Processos',
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
            'cutout' => '60%',
        ];
    }
}
