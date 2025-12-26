@php
    /** @var \App\Models\Invoice $record */
@endphp

<div class="p-4 bg-white rounded-lg shadow-md dark:bg-gray-800 space-y-2 flex flex-col justify-between h-full">
    <div>
        <div class="flex justify-between items-start">
            <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">{{ $record->invoice_number }}</span>
            <span class="text-xs px-2 py-1 font-semibold leading-tight rounded-full {{ 
                match($record->status) {
                    'paid' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                    'partial' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                    'overdue' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                    'cancelled' => 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                }
            }}">
                {{ $record->getFormattedStatusAttribute() }}
            </span>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            {{ $record->client?->name ?? 'Cliente não informado' }}
        </p>
        <p class="text-2xl font-bold text-gray-900 dark:text-white truncate pt-2">
            R$ {{ number_format($record->total, 2, ',', '.') }}
        </p>
    </div>
    <div class="pt-2 border-t dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400 flex justify-between items-center">
        <span>Vencimento: {{ $record->due_date?->format('d/m/Y') ?? 'N/A' }}</span>
        <a href="{{ App\Filament\Resources\InvoiceResource::getUrl('edit', ['record' => $record]) }}" class="text-primary-600 hover:text-primary-800">
            Ver
        </a>
    </div>
</div>
