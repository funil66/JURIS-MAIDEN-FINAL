<x-filament-panels::page>
    <form wire:submit.prevent="calculate">
        {{ $this->form }}

        <div class="mt-6 flex gap-4">
            <x-filament::button type="submit" color="primary" icon="heroicon-o-calculator">
                Calcular Prazo
            </x-filament::button>

            @if($calculatedDate)
                <x-filament::button 
                    type="button" 
                    color="success" 
                    icon="heroicon-o-plus-circle"
                    wire:click="createDeadline"
                >
                    Criar Prazo
                </x-filament::button>
            @endif
        </div>
    </form>

    @if($calculatedDate && $calculationDetails)
        <div class="mt-8">
            <x-filament::section>
                <x-slot name="heading">
                    📅 Resultado do Cálculo
                </x-slot>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {{-- Data de Início --}}
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Data de Início</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $calculationDetails['start_date'] }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            {{ $calculationDetails['start_day_name'] }}
                        </div>
                    </div>

                    {{-- Prazo --}}
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-700">
                        <div class="text-sm text-blue-500 dark:text-blue-400 mb-1">Prazo</div>
                        <div class="text-xl font-bold text-blue-900 dark:text-blue-100">
                            {{ $calculationDetails['days_count'] }} dias
                        </div>
                        <div class="text-sm text-blue-600 dark:text-blue-300">
                            {{ $calculationDetails['counting_type'] }}
                        </div>
                    </div>

                    {{-- Data de Vencimento --}}
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 border border-green-200 dark:border-green-700">
                        <div class="text-sm text-green-500 dark:text-green-400 mb-1">Data de Vencimento</div>
                        <div class="text-2xl font-bold text-green-900 dark:text-green-100">
                            {{ $calculationDetails['due_date'] }}
                        </div>
                        <div class="text-sm text-green-600 dark:text-green-300">
                            {{ $calculationDetails['due_day_name'] }}
                        </div>
                    </div>
                </div>

                {{-- Detalhes do Cálculo --}}
                <div class="mt-6 bg-gray-50 dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        Detalhes do Cálculo
                    </h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Dias corridos:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-1">{{ $calculationDetails['calendar_days'] }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Exclui dia inicial:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-1">{{ $calculationDetails['excludes_start'] }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Prorroga p/ dia útil:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-1">{{ $calculationDetails['extends'] }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Tipo de contagem:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-1">{{ $calculationDetails['counting_type'] }}</span>
                        </div>
                    </div>
                </div>

                {{-- Linha do tempo visual --}}
                <div class="mt-6">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        Linha do Tempo
                    </h4>
                    <div class="relative">
                        <div class="absolute top-4 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                        <div class="relative flex justify-between">
                            {{-- Início --}}
                            <div class="flex flex-col items-center">
                                <div class="w-8 h-8 rounded-full bg-gray-500 text-white flex items-center justify-center z-10">
                                    <x-heroicon-o-calendar class="w-4 h-4" />
                                </div>
                                <div class="mt-2 text-xs text-center">
                                    <div class="font-medium text-gray-900 dark:text-white">Início</div>
                                    <div class="text-gray-500">{{ $calculationDetails['start_date'] }}</div>
                                </div>
                            </div>

                            {{-- Prazo --}}
                            <div class="flex flex-col items-center">
                                <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center z-10">
                                    <span class="text-xs font-bold">{{ $calculationDetails['days_count'] }}</span>
                                </div>
                                <div class="mt-2 text-xs text-center">
                                    <div class="font-medium text-blue-600 dark:text-blue-400">{{ $calculationDetails['days_count'] }} dias</div>
                                    <div class="text-gray-500">{{ $calculationDetails['counting_type'] }}</div>
                                </div>
                            </div>

                            {{-- Vencimento --}}
                            <div class="flex flex-col items-center">
                                <div class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center z-10">
                                    <x-heroicon-o-flag class="w-4 h-4" />
                                </div>
                                <div class="mt-2 text-xs text-center">
                                    <div class="font-medium text-green-600 dark:text-green-400">Vencimento</div>
                                    <div class="text-gray-900 dark:text-white font-bold">{{ $calculationDetails['due_date'] }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>
    @endif

    {{-- Tabela de referência de prazos --}}
    <div class="mt-8">
        <x-filament::section collapsed>
            <x-slot name="heading">
                📋 Tabela de Prazos Processuais (CPC)
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left py-2 px-4 font-semibold text-gray-700 dark:text-gray-300">Prazo</th>
                            <th class="text-center py-2 px-4 font-semibold text-gray-700 dark:text-gray-300">Dias</th>
                            <th class="text-center py-2 px-4 font-semibold text-gray-700 dark:text-gray-300">Contagem</th>
                            <th class="text-left py-2 px-4 font-semibold text-gray-700 dark:text-gray-300">Base Legal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr>
                            <td class="py-2 px-4">Contestação</td>
                            <td class="py-2 px-4 text-center font-medium">15</td>
                            <td class="py-2 px-4 text-center"><span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded text-xs">Úteis</span></td>
                            <td class="py-2 px-4 text-gray-500">Art. 335, CPC</td>
                        </tr>
                        <tr>
                            <td class="py-2 px-4">Apelação</td>
                            <td class="py-2 px-4 text-center font-medium">15</td>
                            <td class="py-2 px-4 text-center"><span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded text-xs">Úteis</span></td>
                            <td class="py-2 px-4 text-gray-500">Art. 1.003, §5º, CPC</td>
                        </tr>
                        <tr>
                            <td class="py-2 px-4">Agravo de Instrumento</td>
                            <td class="py-2 px-4 text-center font-medium">15</td>
                            <td class="py-2 px-4 text-center"><span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded text-xs">Úteis</span></td>
                            <td class="py-2 px-4 text-gray-500">Art. 1.003, §5º, CPC</td>
                        </tr>
                        <tr>
                            <td class="py-2 px-4">Embargos de Declaração</td>
                            <td class="py-2 px-4 text-center font-medium">5</td>
                            <td class="py-2 px-4 text-center"><span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded text-xs">Úteis</span></td>
                            <td class="py-2 px-4 text-gray-500">Art. 1.023, CPC</td>
                        </tr>
                        <tr>
                            <td class="py-2 px-4">Recurso Especial / Extraordinário</td>
                            <td class="py-2 px-4 text-center font-medium">15</td>
                            <td class="py-2 px-4 text-center"><span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded text-xs">Úteis</span></td>
                            <td class="py-2 px-4 text-gray-500">Art. 1.003, §5º, CPC</td>
                        </tr>
                        <tr>
                            <td class="py-2 px-4">Embargos à Execução</td>
                            <td class="py-2 px-4 text-center font-medium">15</td>
                            <td class="py-2 px-4 text-center"><span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded text-xs">Úteis</span></td>
                            <td class="py-2 px-4 text-gray-500">Art. 915, CPC</td>
                        </tr>
                        <tr>
                            <td class="py-2 px-4">Impugnação ao Cumprimento</td>
                            <td class="py-2 px-4 text-center font-medium">15</td>
                            <td class="py-2 px-4 text-center"><span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded text-xs">Úteis</span></td>
                            <td class="py-2 px-4 text-gray-500">Art. 525, CPC</td>
                        </tr>
                        <tr>
                            <td class="py-2 px-4">Réplica</td>
                            <td class="py-2 px-4 text-center font-medium">15</td>
                            <td class="py-2 px-4 text-center"><span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded text-xs">Úteis</span></td>
                            <td class="py-2 px-4 text-gray-500">Art. 351, CPC</td>
                        </tr>
                        <tr>
                            <td class="py-2 px-4">Manifestação Genérica</td>
                            <td class="py-2 px-4 text-center font-medium">5</td>
                            <td class="py-2 px-4 text-center"><span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded text-xs">Úteis</span></td>
                            <td class="py-2 px-4 text-gray-500">Art. 218, §3º, CPC</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-700">
                <div class="flex items-start gap-2">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-500 flex-shrink-0 mt-0.5" />
                    <div class="text-sm text-yellow-700 dark:text-yellow-300">
                        <strong>Importante:</strong> Os prazos podem variar conforme o tipo de procedimento, justiça especializada (trabalhista, eleitoral, etc.) e legislação específica. Sempre consulte a legislação aplicável ao caso concreto.
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
