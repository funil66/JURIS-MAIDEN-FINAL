<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
            <p class="text-sm text-gray-500">Tipo de Documento</p>
            <p class="font-medium">{{ $document->template?->name ?? 'Documento' }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Serviço</p>
            <p class="font-medium">{{ $document->service?->code ?? '-' }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Data de Geração</p>
            <p class="font-medium">{{ $document->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Gerado por</p>
            <p class="font-medium">{{ $document->generatedByUser?->name ?? 'Sistema' }}</p>
        </div>
    </div>

    @if($document->content)
        <div class="border-t pt-4">
            <h4 class="font-medium mb-2">Conteúdo do Documento</h4>
            <div class="prose dark:prose-invert max-w-none bg-gray-50 dark:bg-gray-800 p-4 rounded-lg overflow-auto max-h-96">
                {!! nl2br(e($document->content)) !!}
            </div>
        </div>
    @endif

    @if($document->file_path)
        <div class="border-t pt-4">
            <p class="text-sm text-gray-500 mb-2">Arquivo PDF disponível para download</p>
            <a href="{{ asset('storage/' . $document->file_path) }}" 
               target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                Baixar PDF
            </a>
        </div>
    @endif
</div>
