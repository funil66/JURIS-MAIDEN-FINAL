@php
    /** @var \App\Models\Diligence $record */
@endphp

<div class="p-4 bg-white rounded-lg shadow-md dark:bg-gray-800 space-y-2 flex flex-col justify-between h-full">
    <div>
        <div class="flex justify-between items-start">
            <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ $record->uid }}</span>
            <span @class([
                'px-2 py-1 text-xs font-semibold leading-tight rounded-full',
                match ($record->status) {
                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                    'scheduled' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                    'in_progress' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
                    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                    'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                },
            ])>
                {{ $record->getStatusLabelAttribute() }}
            </span>
        </div>
        <h3 class="text-md font-bold text-gray-900 dark:text-white truncate pt-2">
            {{ $record->title }}
        </h3>
        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            <p><strong>Cliente:</strong> {{ $record->client?->name ?? 'N/A' }}</p>
            <p><strong>Responsável:</strong> {{ $record->assignedUser?->name ?? 'N/A' }}</p>
        </div>
    </div>
    <div class="pt-2 border-t dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400 flex justify-between items-center">
        <span>Data: {{ $record->scheduled_date?->format('d/m/Y') ?? 'N/A' }}</span>
        <a href="{{ static::getResource()::getUrl('edit', ['record' => $record]) }}" class="text-primary-600 hover:text-primary-800">
            Ver
        </a>
    </div>
</div>
