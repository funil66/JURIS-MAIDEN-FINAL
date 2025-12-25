<x-filament-widgets::widget>
    @php
        $stats = $this->getStats();
        $activities = $this->getRecentActivity();
        $quickActions = $this->getQuickActions();
    @endphp

    <div class="space-y-6">
        {{-- Header com Saudação --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    @php
                        $hour = now()->hour;
                        $greeting = $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');
                    @endphp
                    {{ $greeting }}, {{ auth()->user()->name }}! 👋
                </h2>
                <p class="text-gray-500 dark:text-gray-400 mt-1">
                    {{ now()->locale('pt_BR')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                </p>
            </div>

            {{-- Ações Rápidas --}}
            <div class="flex flex-wrap gap-2 w-full">
                @foreach($quickActions as $action)
                    <a href="{{ $action['url'] }}" 
                       class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 sm:py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-200 
                              bg-{{ $action['color'] }}-50 text-{{ $action['color'] }}-700 
                              hover:bg-{{ $action['color'] }}-100 hover:scale-105 hover:shadow-lg
                              dark:bg-{{ $action['color'] }}-900/30 dark:text-{{ $action['color'] }}-300 
                              dark:hover:bg-{{ $action['color'] }}-900/50 w-full sm:w-auto justify-center sm:justify-start">
                        <x-dynamic-component :component="$action['icon']" class="w-5 h-5" />
                        <span class="hidden sm:inline">{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Cards de Estatísticas --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Processos --}}
            <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 group">
                <div class="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8">
                    <div class="absolute inset-0 bg-indigo-500 rounded-full opacity-10 group-hover:opacity-20 transition-opacity"></div>
                </div>
                <div class="relative">
                    <div class="flex items-center justify-between">
                        <div class="p-3 bg-indigo-100 dark:bg-indigo-900/50 rounded-xl">
                            <x-heroicon-o-scale class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        @if($stats['processes']['urgent'] > 0)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300 animate-pulse">
                                {{ $stats['processes']['urgent'] }} urgentes
                            </span>
                        @endif
                    </div>
                    <div class="mt-4">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['processes']['total'] }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Processos Ativos</p>
                    </div>
                    <div class="mt-3 flex items-center gap-2 text-sm">
                        @if($stats['processes']['trend']['direction'] === 'up')
                            <span class="inline-flex items-center text-emerald-600 dark:text-emerald-400">
                                <x-heroicon-s-arrow-trending-up class="w-4 h-4 mr-1" />
                                +{{ $stats['processes']['trend']['percentage'] }}%
                            </span>
                        @elseif($stats['processes']['trend']['direction'] === 'down')
                            <span class="inline-flex items-center text-rose-600 dark:text-rose-400">
                                <x-heroicon-s-arrow-trending-down class="w-4 h-4 mr-1" />
                                -{{ $stats['processes']['trend']['percentage'] }}%
                            </span>
                        @endif
                        <span class="text-gray-400">{{ $stats['processes']['new'] }} novos este mês</span>
                    </div>
                </div>
            </div>

            {{-- Clientes --}}
            <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 group">
                <div class="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8">
                    <div class="absolute inset-0 bg-emerald-500 rounded-full opacity-10 group-hover:opacity-20 transition-opacity"></div>
                </div>
                <div class="relative">
                    <div class="flex items-center justify-between">
                        <div class="p-3 bg-emerald-100 dark:bg-emerald-900/50 rounded-xl">
                            <x-heroicon-o-user-group class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['clients']['total'] }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Clientes Ativos</p>
                    </div>
                    <div class="mt-3 flex items-center gap-2 text-sm">
                        @if($stats['clients']['trend']['direction'] === 'up')
                            <span class="inline-flex items-center text-emerald-600 dark:text-emerald-400">
                                <x-heroicon-s-arrow-trending-up class="w-4 h-4 mr-1" />
                                +{{ $stats['clients']['trend']['percentage'] }}%
                            </span>
                        @endif
                        <span class="text-gray-400">{{ $stats['clients']['new'] }} novos este mês</span>
                    </div>
                </div>
            </div>

            {{-- Financeiro --}}
            <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 group">
                <div class="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8">
                    <div class="absolute inset-0 bg-amber-500 rounded-full opacity-10 group-hover:opacity-20 transition-opacity"></div>
                </div>
                <div class="relative">
                    <div class="flex items-center justify-between">
                        <div class="p-3 bg-amber-100 dark:bg-amber-900/50 rounded-xl">
                            <x-heroicon-o-banknotes class="w-6 h-6 text-amber-600 dark:text-amber-400" />
                        </div>
                        @if($stats['financial']['overdue'] > 0)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300">
                                R$ {{ number_format($stats['financial']['overdue'], 0, ',', '.') }} vencido
                            </span>
                        @endif
                    </div>
                    <div class="mt-4">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">
                            R$ {{ number_format($stats['financial']['received'], 0, ',', '.') }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Recebido este Mês</p>
                    </div>
                    <div class="mt-3">
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                            <span>Pendente</span>
                            <span>R$ {{ number_format($stats['financial']['pending'], 0, ',', '.') }}</span>
                        </div>
                        <div class="w-full h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                            @php
                                $total = $stats['financial']['received'] + $stats['financial']['pending'];
                                $percentage = $total > 0 ? ($stats['financial']['received'] / $total) * 100 : 0;
                            @endphp
                            <div class="h-full bg-gradient-to-r from-amber-400 to-amber-600 rounded-full transition-all duration-500" 
                                 style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Prazos --}}
            <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 group">
                <div class="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8">
                    <div class="absolute inset-0 bg-rose-500 rounded-full opacity-10 group-hover:opacity-20 transition-opacity"></div>
                </div>
                <div class="relative">
                    <div class="flex items-center justify-between">
                        <div class="p-3 bg-rose-100 dark:bg-rose-900/50 rounded-xl">
                            <x-heroicon-o-clock class="w-6 h-6 text-rose-600 dark:text-rose-400" />
                        </div>
                        @if($stats['deadlines']['overdue'] > 0)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300 animate-pulse">
                                {{ $stats['deadlines']['overdue'] }} vencidos!
                            </span>
                        @endif
                    </div>
                    <div class="mt-4">
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['deadlines']['upcoming'] }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Prazos Próximos (7 dias)</p>
                    </div>
                    <div class="mt-3 flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                        <span class="flex items-center gap-1">
                            <x-heroicon-s-clipboard-document-check class="w-4 h-4" />
                            {{ $stats['deadlines']['diligences'] }} diligências
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Atividades Recentes --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <x-heroicon-o-clock class="w-5 h-5 text-indigo-500" />
                    Atividades Recentes
                </h3>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($activities as $activity)
                    <div class="px-6 py-4 flex items-center gap-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex-shrink-0 p-2.5 rounded-xl bg-{{ $activity['color'] }}-100 dark:bg-{{ $activity['color'] }}-900/30">
                            <x-dynamic-component :component="$activity['icon']" class="w-5 h-5 text-{{ $activity['color'] }}-600 dark:text-{{ $activity['color'] }}-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ $activity['title'] }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ $activity['subtitle'] }}
                            </p>
                        </div>
                        <div class="flex-shrink-0 text-xs text-gray-400 dark:text-gray-500">
                            {{ $activity['time'] }}
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-inbox class="w-12 h-12 mx-auto mb-3 opacity-50" />
                        <p>Nenhuma atividade recente</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
