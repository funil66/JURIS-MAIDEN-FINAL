<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Alertas Críticos --}}
        @livewire(\App\Filament\Widgets\CriticalAlertsWidget::class)

        {{-- Stats Overview --}}
        @livewire(\App\Filament\Widgets\JuridicalStatsWidget::class)

        {{-- Prazos e Alertas --}}
        <div class="grid grid-cols-1 gap-6">
            @livewire(\App\Filament\Widgets\DeadlinesWidget::class)
        </div>

        {{-- Processos Recentes --}}
        <div class="grid grid-cols-1 gap-6">
            @livewire(\App\Filament\Widgets\ProcessesOverviewWidget::class)
        </div>

        {{-- Diligências e Time Tracking --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @livewire(\App\Filament\Widgets\DiligencesWidget::class)
            @livewire(\App\Filament\Widgets\TimeTrackingWidget::class)
        </div>

        {{-- Gráfico de Faturamento --}}
        <div class="grid grid-cols-1 gap-6">
            @livewire(\App\Filament\Widgets\FinancialJuridicalChart::class)
        </div>

        {{-- Gráficos de Pizza --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @livewire(\App\Filament\Widgets\ProcessesByStatusChart::class)
            @livewire(\App\Filament\Widgets\ProcessesByPhaseChart::class)
            @livewire(\App\Filament\Widgets\InvoicesByStatusChart::class)
        </div>

        {{-- Top Clientes --}}
        <div class="grid grid-cols-1 gap-6">
            @livewire(\App\Filament\Widgets\TopClientsWidget::class)
        </div>
    </div>
</x-filament-panels::page>
