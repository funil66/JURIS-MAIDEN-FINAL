<?php

namespace App\Filament\Widgets;

use App\Models\Process;
use Filament\Widgets\ChartWidget;

class ProcessesByPhaseChart extends ChartWidget
{
    protected static ?string $heading = 'Processos por Fase';

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '300px';

    protected static ?string $pollingInterval = '120s';

    protected function getData(): array
    {
        $phases = [
            'knowledge' => ['label' => 'Conhecimento', 'color' => 'rgb(59, 130, 246)'],
            'execution' => ['label' => 'Execução', 'color' => 'rgb(234, 179, 8)'],
            'appeal' => ['label' => 'Recursal', 'color' => 'rgb(239, 68, 68)'],
            'precautionary' => ['label' => 'Cautelar', 'color' => 'rgb(156, 163, 175)'],
        ];

        $data = [];
        $labels = [];
        $colors = [];

        foreach ($phases as $phase => $config) {
            $count = Process::where('phase', $phase)->where('status', 'active')->count();
            if ($count > 0) {
                $data[] = $count;
                $labels[] = $config['label'] . ' (' . $count . ')';
                $colors[] = $config['color'];
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Processos Ativos',
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
        return 'pie';
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
        ];
    }
}
