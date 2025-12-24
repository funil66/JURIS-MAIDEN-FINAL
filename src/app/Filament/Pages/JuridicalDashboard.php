<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use App\Filament\Widgets;

class JuridicalDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-scale';
    
    protected static ?string $navigationLabel = 'Dashboard Jurídico';
    
    protected static ?string $title = 'Dashboard Jurídico';
    
    protected static ?string $slug = 'juridical-dashboard';
    
    protected static ?string $navigationGroup = 'Dashboard';
    
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.juridical-dashboard';

    public function getWidgets(): array
    {
        return [
            Widgets\JuridicalStatsWidget::class,
            Widgets\DeadlinesWidget::class,
            Widgets\ProcessesOverviewWidget::class,
            Widgets\DiligencesWidget::class,
            Widgets\TimeTrackingWidget::class,
            Widgets\FinancialJuridicalChart::class,
            Widgets\ProcessesByStatusChart::class,
            Widgets\ProcessesByPhaseChart::class,
            Widgets\InvoicesByStatusChart::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('new_process')
                ->label('Novo Processo')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(route('filament.funil.resources.processes.create')),

            Action::make('new_time_entry')
                ->label('Lançar Tempo')
                ->icon('heroicon-o-clock')
                ->color('info')
                ->url(route('filament.funil.resources.time-entries.create')),

            Action::make('new_diligence')
                ->label('Nova Diligência')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('warning')
                ->url(route('filament.funil.resources.diligences.create')),

            Action::make('reports')
                ->label('Relatórios')
                ->icon('heroicon-o-document-chart-bar')
                ->color('gray')
                ->url(route('filament.funil.pages.reports-page')),
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 4;
    }
}
