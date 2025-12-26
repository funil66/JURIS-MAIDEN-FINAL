<x-filament-panels::page>
    <div class="py-8 px-4">
        <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Documentos Gerados</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($this->documents as $document)
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg p-6 flex flex-col gap-3 hover:bg-primary-50 transition">
                    <div class="flex items-center gap-3 mb-2">
                        <x-heroicon-o-document-text class="w-8 h-8 text-primary-600" />
                        <span class="font-semibold text-lg text-gray-900 dark:text-white">{{ $document->title }}</span>
                    </div>
                    <div class="text-sm text-gray-500 mb-1">Processo: <span class="font-bold text-gray-700 dark:text-white">{{ $document->process_number }}</span></div>
                    <div class="text-xs text-gray-400 mb-1">Criado em: {{ $document->created_at->format('d/m/Y') }}</div>
                    <a href="{{ route('filament.client-panel.pages.document-preview', $document->id) }}" class="mt-4 inline-block text-primary-600 hover:underline font-medium">Visualizar</a>
                    <a href="{{ route('filament.client-panel.pages.document-download', $document->id) }}" class="inline-block text-green-600 hover:underline font-medium">Download</a>
                </div>
            @empty
                <div class="col-span-full text-center py-10 text-gray-500">Nenhum documento encontrado</div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
