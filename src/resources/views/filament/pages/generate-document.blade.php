<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Formulário --}}
        <form wire:submit.prevent="generatePreview">
            {{ $this->form }}

            <div class="mt-6 flex gap-4">
                <x-filament::button type="submit" icon="heroicon-o-eye">
                    Visualizar Prévia
                </x-filament::button>

                <x-filament::button 
                    type="button" 
                    color="success" 
                    icon="heroicon-o-document-arrow-down"
                    wire:click="generatePdf"
                    wire:loading.attr="disabled"
                    :disabled="!$preview"
                >
                    <span wire:loading.remove wire:target="generatePdf">Gerar PDF</span>
                    <span wire:loading wire:target="generatePdf">Gerando...</span>
                </x-filament::button>

                <x-filament::button 
                    type="button" 
                    color="gray" 
                    icon="heroicon-o-arrow-path"
                    wire:click="resetForm"
                >
                    Limpar
                </x-filament::button>
            </div>
        </form>

        {{-- Preview --}}
        @if($preview)
            <x-filament::section>
                <x-slot name="heading">
                    📄 Prévia do Documento
                </x-slot>
                <x-slot name="description">
                    Esta é uma prévia do documento com as variáveis substituídas
                </x-slot>

                <div class="bg-white dark:bg-gray-900 p-8 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
                    <div class="prose dark:prose-invert max-w-none">
                        {!! $preview !!}
                    </div>
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                <x-slot name="heading">
                    📋 Instruções
                </x-slot>

                <div class="prose dark:prose-invert max-w-none">
                    <ol>
                        <li><strong>Selecione um template</strong> - Escolha o modelo de documento desejado</li>
                        <li><strong>Vincule registros</strong> (opcional) - Selecione um cliente ou serviço para preencher automaticamente as variáveis</li>
                        <li><strong>Defina o título</strong> - Dê um nome ao documento para fácil identificação</li>
                        <li><strong>Visualize a prévia</strong> - Clique em "Visualizar Prévia" para ver o documento</li>
                        <li><strong>Gere o PDF</strong> - Se estiver satisfeito, clique em "Gerar PDF"</li>
                    </ol>

                    <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                        <h4 class="text-amber-700 dark:text-amber-300 font-semibold">💡 Dica</h4>
                        <p class="text-sm text-amber-600 dark:text-amber-400">
                            As variáveis marcadas com <code class="bg-amber-100 dark:bg-amber-800 px-1 rounded">@{{ variavel }}</code> serão substituídas automaticamente 
                            com os dados do advogado logado, cliente selecionado e serviço vinculado.
                        </p>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
