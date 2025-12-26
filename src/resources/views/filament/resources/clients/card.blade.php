@php
    /** @var \App\Models\Client $record */
@endphp

<div class="p-4 bg-white rounded-lg shadow-md dark:bg-gray-800 space-y-2 flex flex-col justify-between h-full">
    <div>
        <div class="flex justify-between items-start">
            <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ $record->uid }}</span>
            <span @class([
                'px-2 py-1 text-xs font-semibold leading-tight rounded-full',
                $record->is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            ])>
                {{ $record->is_active ? 'Ativo' : 'Inativo' }}
            </span>
        </div>
        <p class="text-lg font-bold text-gray-900 dark:text-white truncate pt-2">
            {{ $record->name }}
        </p>
        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1 space-y-1">
            @if($record->email)
                <p class="flex items-center space-x-2">
                    <x-heroicon-o-envelope class="w-4 h-4" />
                    <span>{{ $record->email }}</span>
                </p>
            @endif
            @if($record->phone)
                <p class="flex items-center space-x-2">
                    <x-heroicon-o-phone class="w-4 h-4" />
                    <span>{{ $record->phone }}</span>
                </p>
            @endif
        </div>
    </div>
    <div class="pt-2 border-t dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400 flex justify-end items-center">
        <a href="{{ App\Filament\Resources\ClientResource::getUrl('edit', ['record' => $record]) }}" class="text-primary-600 hover:text-primary-800">
            Ver Detalhes
        </a>
    </div>
</div>
