<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Resumo Financeiro --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 bg-red-100 dark:bg-red-900 rounded-lg">
                        <x-heroicon-o-clock class="w-6 h-6 text-red-600" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Pendente</p>
                        <p class="text-2xl font-bold text-red-600">R$ {{ number_format($this->totalPending, 2, ',', '.') }}</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-green-600" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Pago</p>
                        <p class="text-2xl font-bold text-green-600">R$ {{ number_format($this->totalPaid, 2, ',', '.') }}</p>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Tabela de Transações --}}
        <x-filament::section>
            <x-slot name="heading">
                Histórico de Cobranças
            </x-slot>
            <x-slot name="description">
                Acompanhe todas as cobranças e pagamentos referentes aos seus serviços
            </x-slot>

            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
