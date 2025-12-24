<?php

namespace App\Filament\Widgets;

use App\Models\Process;
use Filament\Widgets\ChartWidget;

class ProcessStatusDonutChart extends ChartWidget
{
    protected static ?string $heading = 'Processos por Status';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];

    protected static ?string $maxHeight = '280px';

    protected static ?string $pollingInterval = '120s';

    protected function getData(): array
    {
        $statuses = Process::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $labels = [];
        $data = [];
        $colors = [];

        $statusConfig = [
            'active' => ['label' => 'Ativos', 'color' => 'rgb(99, 102, 241)'],
            'suspended' => ['label' => 'Suspensos', 'color' => 'rgb(251, 191, 36)'],
            'archived' => ['label' => 'Arquivados', 'color' => 'rgb(148, 163, 184)'],
            'closed' => ['label' => 'Encerrados', 'color' => 'rgb(16, 185, 129)'],
            'won' => ['label' => 'Ganhos', 'color' => 'rgb(34, 197, 94)'],
            'lost' => ['label' => 'Perdidos', 'color' => 'rgb(244, 63, 94)'],
            'settled' => ['label' => 'Acordo', 'color' => 'rgb(14, 165, 233)'],
        ];

        foreach ($statuses as $status => $count) {
            $config = $statusConfig[$status] ?? ['label' => ucfirst($status), 'color' => 'rgb(148, 163, 184)'];
            $labels[] = $config['label'];
            $data[] = $count;
            $colors[] = $config['color'];
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderWidth' => 0,
                    'hoverOffset' => 8,
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
            'cutout' => '65%',
            'maintainAspectRatio' => true,
        ];
    }
}
