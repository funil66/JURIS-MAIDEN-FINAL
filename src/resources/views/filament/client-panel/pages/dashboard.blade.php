
<x-filament-panels::page>
    <div class="space-y-8">
        <!-- Cards de estatísticas -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de Serviços</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->stats['total_services'] }}</p>
            </div>
            <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Em Andamento</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->stats['services_in_progress'] }}</p>
            </div>
            <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Concluídos</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->stats['services_completed'] }}</p>
            </div>
            <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pagamentos Pendentes</p>
                <p class="text-3xl font-bold text-red-600 dark:text-red-500">R$ {{ number_format($this->stats['pending_payments'], 2, ',', '.') }}</p>
            </div>
        </div>

        <!-- Grid de listas -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Coluna de Serviços Recentes -->
            <div class="space-y-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Serviços Recentes</h2>
                <div class="space-y-4">
                    @forelse($this->recentServices as $service)
                        <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
                            <div class="flex justify-between">
                                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $service->serviceType?->name ?? 'Serviço' }}</p>
                                <span class="text-xs text-gray-500">{{ $service->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 truncate">{{ $service->description }}</p>
                        </div>
                    @empty
                        <div class="p-4 text-center text-gray-500 bg-white rounded-lg shadow dark:bg-gray-800">
                            Nenhum serviço recente.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Coluna de Próximos Eventos -->
            <div class="space-y-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Próximos Eventos</h2>
                <div class="space-y-4">
                    @forelse($this->upcomingEvents as $event)
                        <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
                            <div class="flex justify-between">
                                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $event->title }}</p>
                                <span class="text-xs text-gray-500">{{ $event->starts_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $event->type }}</p>
                        </div>
                    @empty
                        <div class="p-4 text-center text-gray-500 bg-white rounded-lg shadow dark:bg-gray-800">
                            Nenhum evento futuro.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Coluna de Pagamentos Pendentes -->
            <div class="space-y-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Pagamentos Pendentes</h2>
                <div class="space-y-4">
                    @forelse($this->pendingPayments as $payment)
                        <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
                            <div class="flex justify-between">
                                <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $payment->description }}</p>
                                <p class="font-bold text-red-600 dark:text-red-500">R$ {{ number_format($payment->amount, 2, ',', '.') }}</p>
                            </div>
                             <p class="text-sm text-gray-600 dark:text-gray-400">Vencimento: {{ $payment->due_date?->format('d/m/Y') }}</p>
                        </div>
                    @empty
                        <div class="p-4 text-center text-gray-500 bg-white rounded-lg shadow dark:bg-gray-800">
                            Nenhuma pendência financeira.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-filament-panels::page>
