<div 
    x-data="{ 
        isOpen: @entangle('isOpen'),
        selectedIndex: @entangle('selectedIndex')
    }"
    @keydown.escape.window="isOpen = false"
    @click.outside="isOpen = false"
    class="relative"
>
    {{-- Input de busca --}}
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <x-heroicon-o-magnifying-glass class="h-4 w-4 text-gray-400" />
        </div>
        <input
            type="text"
            wire:model.live.debounce.200ms="query"
            wire:keydown.arrow-down.prevent="selectNext"
            wire:keydown.arrow-up.prevent="selectPrevious"
            wire:keydown.enter.prevent="goToSelected"
            placeholder="Buscar... (Ctrl+K)"
            class="w-64 pl-9 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg 
                focus:ring-2 focus:ring-primary-500 focus:border-primary-500 
                dark:bg-gray-800 dark:text-white dark:placeholder-gray-400
                transition-all"
        />
        @if($query)
            <button 
                wire:click="close"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
            >
                <x-heroicon-o-x-mark class="h-4 w-4" />
            </button>
        @endif
    </div>

    {{-- Dropdown de resultados --}}
    @if($isOpen && count($results) > 0)
        <div class="absolute z-50 mt-2 w-96 bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="max-h-96 overflow-y-auto">
                @foreach($results as $index => $result)
                    <a 
                        href="{{ $result['url'] }}"
                        wire:click.prevent="goToResult({{ $index }})"
                        class="flex items-center px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors
                            {{ $selectedIndex === $index ? 'bg-primary-50 dark:bg-primary-900/20' : '' }}"
                    >
                        <span class="text-lg mr-3">{{ $result['icon'] }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {!! $result['display_highlighted'] !!}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center space-x-2">
                                <span>{{ $result['entity_label'] }}</span>
                                @if($result['uid'])
                                    <span class="font-mono">{{ $result['uid'] }}</span>
                                @endif
                            </div>
                        </div>
                        <x-heroicon-o-chevron-right class="h-4 w-4 text-gray-400 flex-shrink-0" />
                    </a>
                @endforeach
            </div>

            {{-- Rodapé --}}
            <div class="px-4 py-2 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700">
                <a 
                    href="{{ route('filament.admin.pages.global-search', ['query' => $query]) }}"
                    class="text-sm text-primary-600 hover:text-primary-700 flex items-center justify-center"
                >
                    Ver todos os resultados
                    <x-heroicon-o-arrow-right class="h-4 w-4 ml-1" />
                </a>
            </div>
        </div>
    @endif

    {{-- Sem resultados --}}
    @if($isOpen && strlen($query) >= 2 && count($results) === 0)
        <div class="absolute z-50 mt-2 w-96 bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 text-center">
            <x-heroicon-o-document-magnifying-glass class="h-8 w-8 mx-auto text-gray-400" />
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Nenhum resultado para "{{ $query }}"
            </p>
            <a 
                href="{{ route('filament.admin.pages.global-search', ['query' => $query]) }}"
                class="mt-2 inline-block text-sm text-primary-600 hover:underline"
            >
                Busca avançada →
            </a>
        </div>
    @endif
</div>
