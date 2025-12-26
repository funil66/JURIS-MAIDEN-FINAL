<x-filament-panels::page>
    <div class="py-8 px-4">
        <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Serviços Contratados</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($this->services as $service)
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg p-6 flex flex-col gap-3 hover:bg-primary-50 transition">
                    <div class="flex items-center gap-3 mb-2">
                        <x-heroicon-o-briefcase class="w-8 h-8 text-primary-600" />
                        <span class="font-semibold text-lg text-gray-900 dark:text-white">{{ $service->serviceType?->name ?? 'Serviço' }}</span>
                    </div>
                    <div class="text-sm text-gray-500 mb-1">Código: <span class="font-bold text-gray-700 dark:text-white">{{ $service->code }}</span></div>
                    @if($service->process_number)
                        <div class="text-xs text-gray-400 mb-1">Processo: {{ $service->process_number }}</div>
                    @endif
                    <div class="flex gap-2 mt-2">
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
                    <a href="{{ route('filament.client-panel.pages.service-details', $service->id) }}" class="mt-4 inline-block text-primary-600 hover:underline font-medium">Ver detalhes</a>
                </div>
            @empty
                <div class="col-span-full text-center py-10 text-gray-500">Nenhum serviço encontrado</div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
