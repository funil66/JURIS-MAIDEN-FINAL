<div>
    @if($this->hasAlerts())
        <div class="fi-wi-stats-overview">
            @php
                $criticalCount = $this->getCriticalCount();
                $warningCount = $this->getWarningCount();
            @endphp

            {{-- Header com contagem --}}
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-bell-alert class="w-6 h-6 text-danger-500" />
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Central de Alertas
                    </h3>
                </div>
                <div class="flex items-center gap-2">
                    @if($criticalCount > 0)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-danger-100 text-danger-800 dark:bg-danger-900 dark:text-danger-200">
                            {{ $criticalCount }} crítico(s)
                        </span>
                    @endif
                    @if($warningCount > 0)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-200">
                            {{ $warningCount }} alerta(s)
                        </span>
                    @endif
                </div>
            </div>

            {{-- Lista de Alertas --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($this->getAlerts() as $alert)
                    <a href="{{ $alert['link'] }}" 
                       class="block p-4 rounded-xl border transition-all duration-200 hover:shadow-lg hover:scale-105
                              @if($alert['type'] === 'danger') bg-danger-50 border-danger-200 hover:bg-danger-100 dark:bg-danger-900/20 dark:border-danger-700 @endif
                              @if($alert['type'] === 'warning') bg-warning-50 border-warning-200 hover:bg-warning-100 dark:bg-warning-900/20 dark:border-warning-700 @endif
                              @if($alert['type'] === 'info') bg-info-50 border-info-200 hover:bg-info-100 dark:bg-info-900/20 dark:border-info-700 @endif
                              @if($alert['type'] === 'success') bg-success-50 border-success-200 hover:bg-success-100 dark:bg-success-900/20 dark:border-success-700 @endif
                       ">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <x-dynamic-component 
                                    :component="$alert['icon']" 
                                    class="w-6 h-6
                                           @if($alert['type'] === 'danger') text-danger-600 dark:text-danger-400 @endif
                                           @if($alert['type'] === 'warning') text-warning-600 dark:text-warning-400 @endif
                                           @if($alert['type'] === 'info') text-info-600 dark:text-info-400 @endif
                                           @if($alert['type'] === 'success') text-success-600 dark:text-success-400 @endif
                                    " 
                                />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold
                                          @if($alert['type'] === 'danger') text-danger-800 dark:text-danger-200 @endif
                                          @if($alert['type'] === 'warning') text-warning-800 dark:text-warning-200 @endif
                                          @if($alert['type'] === 'info') text-info-800 dark:text-info-200 @endif
                                          @if($alert['type'] === 'success') text-success-800 dark:text-success-200 @endif
                                ">
                                    {{ $alert['title'] }}
                                </p>
                                <p class="text-xs mt-1
                                          @if($alert['type'] === 'danger') text-danger-600 dark:text-danger-300 @endif
                                          @if($alert['type'] === 'warning') text-warning-600 dark:text-warning-300 @endif
                                          @if($alert['type'] === 'info') text-info-600 dark:text-info-300 @endif
                                          @if($alert['type'] === 'success') text-success-600 dark:text-success-300 @endif
                                ">
                                    {{ $alert['message'] }}
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-400" />
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @else
        {{-- Estado sem alertas --}}
        <div class="p-6 text-center bg-success-50 dark:bg-success-900/20 rounded-xl border border-success-200 dark:border-success-700">
            <x-heroicon-o-check-circle class="w-12 h-12 mx-auto text-success-500" />
            <h3 class="mt-3 text-lg font-medium text-success-800 dark:text-success-200">
                Tudo em dia! 🎉
            </h3>
            <p class="mt-1 text-sm text-success-600 dark:text-success-300">
                Não há alertas críticos no momento. Continue o ótimo trabalho!
            </p>
        </div>
    @endif
</div>
