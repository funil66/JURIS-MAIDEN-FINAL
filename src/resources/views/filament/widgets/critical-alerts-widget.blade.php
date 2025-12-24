<x-filament-widgets::widget>
    @if($this->hasAlerts())
        <div class="space-y-4">
            @php
                $criticalCount = $this->getCriticalCount();
                $warningCount = $this->getWarningCount();
            @endphp

            {{-- Header com contagem --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-rose-100 dark:bg-rose-900/30 rounded-xl">
                        <x-heroicon-o-bell-alert class="w-6 h-6 text-rose-600 dark:text-rose-400 animate-pulse" />
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Central de Alertas
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Itens que precisam da sua atenção
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if($criticalCount > 0)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300 animate-pulse">
                            <span class="w-2 h-2 bg-rose-500 rounded-full"></span>
                            {{ $criticalCount }} crítico(s)
                        </span>
                    @endif
                    @if($warningCount > 0)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                            <span class="w-2 h-2 bg-amber-500 rounded-full"></span>
                            {{ $warningCount }} alerta(s)
                        </span>
                    @endif
                </div>
            </div>

            {{-- Lista de Alertas - Grid Responsivo --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                @foreach($this->getAlerts() as $index => $alert)
                    <a href="{{ $alert['link'] }}" 
                       class="group relative block p-5 rounded-2xl border-2 transition-all duration-300 hover:shadow-xl hover:-translate-y-1 overflow-hidden
                              @if($alert['type'] === 'danger') 
                                  bg-gradient-to-br from-rose-50 to-rose-100 border-rose-200 
                                  hover:border-rose-400 hover:shadow-rose-100
                                  dark:from-rose-900/20 dark:to-rose-900/10 dark:border-rose-700 dark:hover:border-rose-500
                              @elseif($alert['type'] === 'warning') 
                                  bg-gradient-to-br from-amber-50 to-amber-100 border-amber-200 
                                  hover:border-amber-400 hover:shadow-amber-100
                                  dark:from-amber-900/20 dark:to-amber-900/10 dark:border-amber-700 dark:hover:border-amber-500
                              @elseif($alert['type'] === 'info') 
                                  bg-gradient-to-br from-sky-50 to-sky-100 border-sky-200 
                                  hover:border-sky-400 hover:shadow-sky-100
                                  dark:from-sky-900/20 dark:to-sky-900/10 dark:border-sky-700 dark:hover:border-sky-500
                              @else 
                                  bg-gradient-to-br from-emerald-50 to-emerald-100 border-emerald-200 
                                  hover:border-emerald-400 hover:shadow-emerald-100
                                  dark:from-emerald-900/20 dark:to-emerald-900/10 dark:border-emerald-700 dark:hover:border-emerald-500
                              @endif
                       "
                       style="animation: slideUp 0.3s ease-out {{ $index * 0.1 }}s both;">
                        
                        {{-- Background Decoration --}}
                        <div class="absolute top-0 right-0 w-24 h-24 transform translate-x-8 -translate-y-8 opacity-10 group-hover:opacity-20 transition-opacity">
                            <div class="w-full h-full rounded-full
                                @if($alert['type'] === 'danger') bg-rose-500
                                @elseif($alert['type'] === 'warning') bg-amber-500
                                @elseif($alert['type'] === 'info') bg-sky-500
                                @else bg-emerald-500
                                @endif
                            "></div>
                        </div>

                        <div class="relative flex items-start gap-4">
                            <div class="flex-shrink-0 p-3 rounded-xl transition-transform group-hover:scale-110
                                @if($alert['type'] === 'danger') bg-rose-200 dark:bg-rose-800/50
                                @elseif($alert['type'] === 'warning') bg-amber-200 dark:bg-amber-800/50
                                @elseif($alert['type'] === 'info') bg-sky-200 dark:bg-sky-800/50
                                @else bg-emerald-200 dark:bg-emerald-800/50
                                @endif
                            ">
                                <x-dynamic-component 
                                    :component="$alert['icon']" 
                                    class="w-6 h-6
                                        @if($alert['type'] === 'danger') text-rose-600 dark:text-rose-300
                                        @elseif($alert['type'] === 'warning') text-amber-600 dark:text-amber-300
                                        @elseif($alert['type'] === 'info') text-sky-600 dark:text-sky-300
                                        @else text-emerald-600 dark:text-emerald-300
                                        @endif
                                    " 
                                />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-base font-bold
                                    @if($alert['type'] === 'danger') text-rose-800 dark:text-rose-200
                                    @elseif($alert['type'] === 'warning') text-amber-800 dark:text-amber-200
                                    @elseif($alert['type'] === 'info') text-sky-800 dark:text-sky-200
                                    @else text-emerald-800 dark:text-emerald-200
                                    @endif
                                ">
                                    {{ $alert['title'] }}
                                </p>
                                <p class="text-sm mt-1 leading-relaxed
                                    @if($alert['type'] === 'danger') text-rose-600 dark:text-rose-300
                                    @elseif($alert['type'] === 'warning') text-amber-600 dark:text-amber-300
                                    @elseif($alert['type'] === 'info') text-sky-600 dark:text-sky-300
                                    @else text-emerald-600 dark:text-emerald-300
                                    @endif
                                ">
                                    {{ $alert['message'] }}
                                </p>
                            </div>
                        </div>

                        {{-- Arrow indicator --}}
                        <div class="absolute bottom-4 right-4 opacity-0 group-hover:opacity-100 transform translate-x-2 group-hover:translate-x-0 transition-all">
                            <x-heroicon-o-arrow-right class="w-5 h-5
                                @if($alert['type'] === 'danger') text-rose-500
                                @elseif($alert['type'] === 'warning') text-amber-500
                                @elseif($alert['type'] === 'info') text-sky-500
                                @else text-emerald-500
                                @endif
                            " />
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @else
        {{-- Estado sem alertas --}}
        <div class="p-8 text-center bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-2xl border-2 border-emerald-200 dark:border-emerald-700">
            <div class="w-16 h-16 mx-auto mb-4 bg-emerald-100 dark:bg-emerald-800/50 rounded-full flex items-center justify-center">
                <x-heroicon-o-check-circle class="w-10 h-10 text-emerald-500" />
            </div>
            <h3 class="text-xl font-bold text-emerald-800 dark:text-emerald-200">
                Tudo em dia! 🎉
            </h3>
            <p class="mt-2 text-emerald-600 dark:text-emerald-300 max-w-md mx-auto">
                Não há alertas críticos no momento. Continue o ótimo trabalho!
            </p>
        </div>
    @endif

    <style>
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</x-filament-widgets::widget>
