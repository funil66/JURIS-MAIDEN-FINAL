<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Formulário de Filtros --}}
        <form wire:submit="generateReport">
            {{ $this->form }}

            <div class="mt-6 flex flex-wrap gap-3 justify-end">
                <x-filament::button type="submit" icon="heroicon-o-document-arrow-down" color="success">
                    Gerar PDF
                </x-filament::button>
                
                <x-filament::button type="button" wire:click="exportExcel" icon="heroicon-o-table-cells" color="primary">
                    Exportar Excel
                </x-filament::button>
                
                <x-filament::button type="button" wire:click="exportCsv" icon="heroicon-o-document-text" color="gray">
                    Exportar CSV
                </x-filament::button>
            </div>
        </form>

        {{-- Cards de Resumo Rápido --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-8">
            <x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary-600">
                        {{ \App\Models\Service::whereMonth('scheduled_datetime', now()->month)->count() }}
                    </div>
                    <div class="text-sm text-gray-500">Serviços este mês</div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold text-success-600">
                        R$ {{ number_format(\App\Models\Transaction::where('type', 'income')->whereMonth('due_date', now()->month)->where('status', 'paid')->sum('amount'), 2, ',', '.') }}
                    </div>
                    <div class="text-sm text-gray-500">Receitas (Pagas)</div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold text-danger-600">
                        R$ {{ number_format(\App\Models\Transaction::where('type', 'expense')->whereMonth('due_date', now()->month)->where('status', 'paid')->sum('amount'), 2, ',', '.') }}
                    </div>
                    <div class="text-sm text-gray-500">Despesas (Pagas)</div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-center">
                    <div class="text-3xl font-bold text-warning-600">
                        R$ {{ number_format(\App\Models\Transaction::where('status', 'pending')->sum('amount'), 2, ',', '.') }}
                    </div>
                    <div class="text-sm text-gray-500">Pendente</div>
                </div>
            </x-filament::section>
        </div>

        {{-- Informações sobre os relatórios --}}
        <x-filament::section>
            <x-slot name="heading">
                📊 Tipos de Relatórios Disponíveis
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <h4 class="font-semibold text-blue-700 dark:text-blue-300">📋 Relatório de Serviços</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Lista todos os serviços no período selecionado com detalhes de cliente, tipo, valor e status.
                    </p>
                </div>

                <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <h4 class="font-semibold text-green-700 dark:text-green-300">👥 Relatório de Clientes</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Ranking de clientes por quantidade de serviços e valor total no período.
                    </p>
                </div>

                <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <h4 class="font-semibold text-yellow-700 dark:text-yellow-300">💰 Relatório Financeiro</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Demonstrativo de receitas, despesas, saldo e transações pendentes/pagas.
                    </p>
                </div>

                <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <h4 class="font-semibold text-purple-700 dark:text-purple-300">📊 Relatório Geral</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Visão consolidada com resumo de serviços, financeiro e clientes ativos.
                    </p>
                </div>
            </div>
        </x-filament::section>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('open-url', (event) => {
                window.open(event.url, '_blank');
            });
        });
    </script>
    @endpush
</x-filament-panels::page>
