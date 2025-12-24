<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Barra de Busca Principal --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <x-heroicon-o-magnifying-glass class="h-6 w-6 text-gray-400" />
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="query"
                    wire:keydown.enter="search"
                    placeholder="Buscar por UID, nome, número de processo, CPF/CNPJ..."
                    class="w-full pl-12 pr-24 py-4 text-lg border-2 border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:text-white transition-all"
                    autofocus
                />
                <div class="absolute inset-y-0 right-0 flex items-center space-x-2 pr-3">
                    @if($query)
                        <button 
                            wire:click="clearSearch"
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            title="Limpar"
                        >
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </button>
                    @endif
                    <button 
                        wire:click="search"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
                    >
                        Buscar
                    </button>
                </div>
            </div>

            {{-- Dica de busca --}}
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                💡 Dica: Use o UID (ex: CLI-10001, PRC-10005) para busca direta
            </p>
        </div>

        {{-- Filtros de Entidades --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    🔍 Buscar em:
                </h3>
                <div class="space-x-2">
                    <button 
                        wire:click="selectAllEntities"
                        class="text-xs text-primary-600 hover:underline"
                    >
                        Todos
                    </button>
                    <span class="text-gray-300">|</span>
                    <button 
                        wire:click="deselectAllEntities"
                        class="text-xs text-gray-500 hover:underline"
                    >
                        Nenhum
                    </button>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($this->searchableEntities as $key => $entity)
                    <button
                        wire:click="toggleEntity('{{ $key }}')"
                        class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium transition-all
                            {{ in_array($key, $selectedEntities) 
                                ? 'bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-400 ring-2 ring-primary-500' 
                                : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700' 
                            }}"
                    >
                        <span class="mr-1.5">{{ $entity['icon'] }}</span>
                        {{ $entity['label'] }}
                        @if(isset($entityStats[$key]))
                            <span class="ml-1.5 px-1.5 py-0.5 text-xs rounded-full bg-white/50 dark:bg-black/20">
                                {{ $entityStats[$key]['count'] ?? 0 }}
                            </span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Resultados --}}
        @if($hasSearched)
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                {{-- Header de resultados --}}
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            @if($totalResults > 0)
                                🎯 {{ $totalResults }} resultado(s) encontrado(s)
                            @else
                                😕 Nenhum resultado encontrado
                            @endif
                        </h2>
                        @if($totalResults > 0)
                            <div class="flex items-center space-x-2 text-sm">
                                @foreach($entityCounts as $entity => $count)
                                    @if($count > 0)
                                        <span class="px-2 py-1 rounded-full bg-{{ $this->searchableEntities[$entity]['color'] }}-100 
                                            dark:bg-{{ $this->searchableEntities[$entity]['color'] }}-900/30 
                                            text-{{ $this->searchableEntities[$entity]['color'] }}-800 
                                            dark:text-{{ $this->searchableEntities[$entity]['color'] }}-400">
                                            {{ $this->searchableEntities[$entity]['icon'] }} {{ $count }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Lista de resultados --}}
                @if($totalResults > 0)
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($results as $result)
                            <a 
                                href="{{ $result['url'] }}"
                                class="block px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
                            >
                                <div class="flex items-start space-x-4">
                                    {{-- Ícone da entidade --}}
                                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-{{ $result['color'] }}-100 
                                        dark:bg-{{ $result['color'] }}-900/30 flex items-center justify-center text-xl">
                                        {{ $result['icon'] }}
                                    </div>

                                    {{-- Conteúdo --}}
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center space-x-2">
                                            <h3 class="text-base font-medium text-gray-900 dark:text-white truncate">
                                                {!! $result['display_highlighted'] !!}
                                            </h3>
                                            @if($result['uid'])
                                                <span class="px-2 py-0.5 text-xs font-mono rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                                                    {{ $result['uid'] }}
                                                </span>
                                            @endif
                                        </div>

                                        @if($result['subtitle'])
                                            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                                {!! $result['subtitle_highlighted'] !!}
                                            </p>
                                        @endif

                                        @if($result['context'])
                                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 italic">
                                                {!! $result['context'] !!}
                                            </p>
                                        @endif

                                        <div class="mt-1 flex items-center space-x-3 text-xs text-gray-400">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded 
                                                bg-{{ $result['color'] }}-100 dark:bg-{{ $result['color'] }}-900/30 
                                                text-{{ $result['color'] }}-700 dark:text-{{ $result['color'] }}-400">
                                                {{ $result['entity_label'] }}
                                            </span>
                                            @if($result['created_at'])
                                                <span>{{ $result['created_at'] }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Seta --}}
                                    <div class="flex-shrink-0 self-center">
                                        <x-heroicon-o-chevron-right class="h-5 w-5 text-gray-400" />
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="px-6 py-12 text-center">
                        <x-heroicon-o-document-magnifying-glass class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">
                            Nenhum resultado encontrado
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Tente buscar com outros termos ou verifique os filtros selecionados.
                        </p>
                    </div>
                @endif
            </div>
        @else
            {{-- Estado inicial --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Buscas recentes --}}
                @if(count($recentSearches) > 0)
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                            🕒 Buscas Recentes
                        </h3>
                        <div class="space-y-2">
                            @foreach($recentSearches as $term)
                                <button 
                                    wire:click="searchFromRecent('{{ $term }}')"
                                    class="flex items-center w-full px-3 py-2 text-left rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                >
                                    <x-heroicon-o-clock class="h-4 w-4 text-gray-400 mr-2" />
                                    <span class="text-gray-700 dark:text-gray-300">{{ $term }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Estatísticas de entidades --}}
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                        📊 Registros no Sistema
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        @foreach($entityStats as $key => $stat)
                            <div class="flex items-center space-x-3">
                                <span class="text-xl">{{ $stat['icon'] }}</span>
                                <div>
                                    <div class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ number_format($stat['count']) }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $stat['label'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Dicas de busca --}}
            <div class="bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20 rounded-xl p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    💡 Dicas de Busca
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="bg-white/50 dark:bg-gray-900/50 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">🔢 Busca por UID</h4>
                        <p class="text-gray-600 dark:text-gray-400">
                            Digite o código único (ex: CLI-10001, PRC-10005) para acesso direto ao registro.
                        </p>
                    </div>
                    <div class="bg-white/50 dark:bg-gray-900/50 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">📝 Busca por Texto</h4>
                        <p class="text-gray-600 dark:text-gray-400">
                            Busque por nome, descrição, número de processo ou qualquer texto relevante.
                        </p>
                    </div>
                    <div class="bg-white/50 dark:bg-gray-900/50 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">🏷️ Filtros</h4>
                        <p class="text-gray-600 dark:text-gray-400">
                            Use os filtros acima para limitar a busca a tipos específicos de registros.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
