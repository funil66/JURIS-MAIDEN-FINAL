@php
    /** @var \App\Models\Service $record */
@endphp

<div class="p-4 bg-white rounded-lg shadow-md dark:bg-gray-800 space-y-2 flex flex-col justify-between h-full">
    <div>
        <div class="flex justify-between items-start">
            <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ $record->code }}</span>
            <span @class([
                'px-2 py-1 text-xs font-semibold leading-tight rounded-full',
                \App\Models\Service::getStatusColors()[$record->status] ?? 'gray',
            ])>
                {{ \App\Models\Service::getStatusOptions()[$record->status] ?? $record->status }}
            </span>
        </div>
        <p class="text-md font-bold text-gray-900 dark:text-white truncate pt-2">
            {{ $record->description ?: 'Serviço sem descrição' }}
        </p>
        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            <p><strong>Cliente:</strong> {{ $record->client?->name ?? 'N/A' }}</p>
            <p><strong>Processo:</strong> {{ $record->process?->process_number ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="pt-2 border-t dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400 flex justify-between items-center">
        <span class="font-bold text-lg text-gray-800 dark:text-gray-200">{{ $record->getFormattedTotalAttribute() }}</span>
        <a href="{{ App\Filament\Resources\ServiceResource::getUrl('edit', ['record' => $record]) }}" class="text-primary-600 hover:text-primary-800">
            Ver
        </a>
    </div>
</div>
