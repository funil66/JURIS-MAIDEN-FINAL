<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Boas-vindas --}}
        <x-filament::section>
            <div class="flex items-center gap-4">
                <div class="flex items-center justify-center w-16 h-16 bg-primary-100 dark:bg-primary-900 rounded-full">
                    <x-heroicon-o-user-circle class="w-10 h-10 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Olá, {{ auth()->guard('client')->user()->name }}!
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400">
                        Bem-vindo ao seu Portal. Acompanhe seus serviços e documentos jurídicos.
                    </p>
                </div>
            </div>
        </x-filament::section>

        {{-- Cards de Estatísticas --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <x-heroicon-o-briefcase class="w-6 h-6 text-blue-600" />
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->stats['total_services'] }}</p>
                        <p class="text-sm text-gray-500">Total de Serviços</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <x-heroicon-o-clock class="w-6 h-6 text-yellow-600" />
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->stats['services_in_progress'] }}</p>
                        <p class="text-sm text-gray-500">Em Andamento</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg">
                        <x-heroicon-o-check-circle class="w-6 h-6 text-green-600" />
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->stats['services_completed'] }}</p>
                        <p class="text-sm text-gray-500">Concluídos</p>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-12 h-12 bg-red-100 dark:bg-red-900 rounded-lg">
                        <x-heroicon-o-currency-dollar class="w-6 h-6 text-red-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">R$ {{ number_format($this->stats['pending_payments'], 2, ',', '.') }}</p>
                        <p class="text-sm text-gray-500">A Pagar</p>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Próximos Eventos --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-calendar class="w-5 h-5 text-primary-500" />
                        Próximos Eventos
                    </div>
                </x-slot>

                @if($this->upcomingEvents->count() > 0)
                    <div class="space-y-3">
                        @foreach($this->upcomingEvents as $event)
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="flex flex-col items-center justify-center w-12 h-12 bg-primary-100 dark:bg-primary-900 rounded-lg">
                                    <span class="text-xs text-primary-600 font-medium">{{ $event->starts_at->format('M') }}</span>
                                    <span class="text-lg font-bold text-primary-700">{{ $event->starts_at->format('d') }}</span>
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $event->title }}</p>
                                    <p class="text-sm text-gray-500">
                                        {{ $event->starts_at->format('H:i') }}
                                        @if($event->location)
                                            • {{ $event->location }}
                                        @endif
                                    </p>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($event->type === 'hearing') bg-red-100 text-red-700
                                    @elseif($event->type === 'deadline') bg-yellow-100 text-yellow-700
                                    @elseif($event->type === 'meeting') bg-blue-100 text-blue-700
                                    @else bg-gray-100 text-gray-700
                                    @endif">
                                    {{ \App\Models\Event::getTypeOptions()[$event->type] ?? $event->type }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6">
                        <x-heroicon-o-calendar class="w-12 h-12 mx-auto text-gray-300" />
                        <p class="mt-2 text-gray-500">Nenhum evento agendado</p>
                    </div>
                @endif
            </x-filament::section>

            {{-- Serviços Recentes --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document-text class="w-5 h-5 text-primary-500" />
                        Serviços Recentes
                    </div>
                </x-slot>

                @if($this->recentServices->count() > 0)
                    <div class="space-y-3">
                        @foreach($this->recentServices as $service)
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ $service->code }}
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        {{ $service->serviceType?->name ?? 'Serviço' }}
                                        @if($service->process_number)
                                            • {{ $service->process_number }}
                                        @endif
                                    </p>
                                </div>
                                <span class="px-2 py-1 text-xs font-medium rounded-full
                                    @if($service->status === 'completed') bg-green-100 text-green-700
                                    @elseif($service->status === 'in_progress') bg-blue-100 text-blue-700
                                    @elseif($service->status === 'confirmed') bg-purple-100 text-purple-700
                                    @elseif($service->status === 'cancelled') bg-red-100 text-red-700
                                    @else bg-yellow-100 text-yellow-700
                                    @endif">
                                    {{ \App\Models\Service::getStatusOptions()[$service->status] ?? $service->status }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6">
                        <x-heroicon-o-document-text class="w-12 h-12 mx-auto text-gray-300" />
                        <p class="mt-2 text-gray-500">Nenhum serviço encontrado</p>
                    </div>
                @endif
            </x-filament::section>
        </div>

        {{-- Pagamentos Pendentes --}}
        @if($this->pendingPayments->count() > 0)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2 text-red-600">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                        Pagamentos Pendentes
                    </div>
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-gray-500 text-sm">
                                <th class="pb-3">Descrição</th>
                                <th class="pb-3">Vencimento</th>
                                <th class="pb-3 text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->pendingPayments as $payment)
                                <tr>
                                    <td class="py-3">
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $payment->description }}</p>
                                    </td>
                                    <td class="py-3">
                                        <span class="@if($payment->due_date && $payment->due_date->isPast()) text-red-600 font-medium @else text-gray-500 @endif">
                                            {{ $payment->due_date?->format('d/m/Y') ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-right font-medium text-gray-900 dark:text-white">
                                        R$ {{ number_format($payment->amount, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
