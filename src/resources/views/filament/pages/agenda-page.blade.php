<x-filament-panels::page>
    @php
        $calendarDays = $this->getCalendarDays();
        $currentMonth = \Carbon\Carbon::parse($this->currentDate);
        $selectedDateEvents = $this->getEventsForDate();
        $upcomingEvents = $this->getUpcomingEvents();
        
        $colorClasses = [
            'indigo' => 'bg-indigo-500',
            'emerald' => 'bg-emerald-500',
            'amber' => 'bg-amber-500',
            'rose' => 'bg-rose-500',
            'sky' => 'bg-sky-500',
            'purple' => 'bg-purple-500',
            'teal' => 'bg-teal-500',
            'orange' => 'bg-orange-500',
            'violet' => 'bg-violet-500',
            'slate' => 'bg-slate-500',
        ];
        
        $colorBgClasses = [
            'indigo' => 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300',
            'emerald' => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300',
            'amber' => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300',
            'rose' => 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300',
            'sky' => 'bg-sky-100 dark:bg-sky-900/30 text-sky-700 dark:text-sky-300',
            'purple' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
            'teal' => 'bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300',
            'orange' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300',
            'violet' => 'bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300',
            'slate' => 'bg-slate-100 dark:bg-slate-900/30 text-slate-700 dark:text-slate-300',
        ];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Calendário Principal --}}
        <div class="lg:col-span-3">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                {{-- Header do Calendário --}}
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <button wire:click="previousMonth" 
                                class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <x-heroicon-o-chevron-left class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                        </button>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $currentMonth->locale('pt_BR')->isoFormat('MMMM [de] YYYY') }}
                        </h2>
                        <button wire:click="nextMonth" 
                                class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <x-heroicon-o-chevron-right class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                        </button>
                    </div>
                    <button wire:click="goToToday" 
                            class="px-4 py-2 text-sm font-semibold text-indigo-600 dark:text-indigo-400 
                                   bg-indigo-50 dark:bg-indigo-900/30 rounded-xl 
                                   hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors">
                        Hoje
                    </button>
                </div>

                {{-- Dias da Semana --}}
                <div class="grid grid-cols-7 border-b border-gray-100 dark:border-gray-700">
                    @foreach(['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'] as $dayName)
                        <div class="px-2 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ $dayName }}
                        </div>
                    @endforeach
                </div>

                {{-- Grid do Calendário --}}
                <div class="grid grid-cols-7">
                    @foreach($calendarDays as $day)
                        <div wire:click="selectDate('{{ $day['date'] }}')"
                             class="min-h-[100px] p-2 border-b border-r border-gray-100 dark:border-gray-700 cursor-pointer transition-all duration-200
                                    {{ !$day['isCurrentMonth'] ? 'bg-gray-50 dark:bg-gray-900/50' : '' }}
                                    {{ $day['isSelected'] ? 'bg-indigo-50 dark:bg-indigo-900/20 ring-2 ring-indigo-500 ring-inset' : '' }}
                                    {{ $day['isToday'] && !$day['isSelected'] ? 'bg-amber-50 dark:bg-amber-900/10' : '' }}
                                    hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            
                            {{-- Número do Dia --}}
                            <div class="flex items-center justify-between mb-1">
                                <span class="inline-flex items-center justify-center w-7 h-7 text-sm font-semibold rounded-full
                                             {{ $day['isToday'] ? 'bg-indigo-600 text-white' : '' }}
                                             {{ !$day['isCurrentMonth'] ? 'text-gray-400 dark:text-gray-600' : 'text-gray-700 dark:text-gray-300' }}">
                                    {{ $day['day'] }}
                                </span>
                                @if($day['moreCount'] > 0)
                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                        +{{ $day['moreCount'] }}
                                    </span>
                                @endif
                            </div>

                            {{-- Eventos do Dia --}}
                            <div class="space-y-1">
                                @foreach($day['events'] as $event)
                                    <div class="text-xs px-1.5 py-0.5 rounded truncate {{ $colorBgClasses[$event['color']] ?? 'bg-gray-100 dark:bg-gray-700' }}">
                                        {{ \Illuminate\Support\Str::limit($event['title'], 15) }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Legenda de Tipos --}}
            <div class="mt-4 flex flex-wrap gap-4 px-2">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Prazos Vencidos</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Prazos Pendentes</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-sky-500"></span>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Audiências</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Reuniões</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-violet-500"></span>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Serviços</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-indigo-500"></span>
                    <span class="text-xs text-gray-600 dark:text-gray-400">Eventos</span>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Eventos do Dia Selecionado --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-indigo-500 to-indigo-600">
                    <h3 class="text-lg font-bold text-white">
                        {{ \Carbon\Carbon::parse($this->selectedDate)->locale('pt_BR')->isoFormat('D [de] MMMM') }}
                    </h3>
                    <p class="text-indigo-100 text-sm">
                        {{ \Carbon\Carbon::parse($this->selectedDate)->locale('pt_BR')->isoFormat('dddd') }}
                    </p>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-[400px] overflow-y-auto">
                    @forelse($selectedDateEvents as $event)
                        <a href="{{ $event['url'] ?? '#' }}" 
                           class="block px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-1 h-12 rounded-full {{ $colorClasses[$event['color']] ?? 'bg-gray-400' }}"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                        {{ $event['title'] }}
                                    </p>
                                    @if($event['description'])
                                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                            {{ $event['description'] }}
                                        </p>
                                    @endif
                                    @if(!$event['all_day'])
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                            <x-heroicon-s-clock class="w-3 h-3 inline -mt-0.5" />
                                            {{ \Carbon\Carbon::parse($event['start'])->format('H:i') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="px-5 py-8 text-center">
                            <x-heroicon-o-calendar class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
                            <p class="text-gray-500 dark:text-gray-400">Nenhum evento neste dia</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Próximos Eventos --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-clock class="w-5 h-5 text-indigo-500" />
                        Próximos Eventos
                    </h3>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-[350px] overflow-y-auto">
                    @forelse($upcomingEvents as $event)
                        <a href="{{ $event['url'] ?? '#' }}" 
                           class="block px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="flex-shrink-0 w-2 h-2 rounded-full {{ $colorClasses[$event['color']] ?? 'bg-gray-400' }}"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $event['title'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($event['start'])->locale('pt_BR')->isoFormat('D MMM') }}
                                        @if(!$event['all_day'])
                                            às {{ \Carbon\Carbon::parse($event['start'])->format('H:i') }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="px-5 py-6 text-center text-gray-500 dark:text-gray-400 text-sm">
                            Nenhum evento próximo
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Ações Rápidas --}}
            <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl shadow-lg p-5">
                <h3 class="text-lg font-bold text-white mb-4">Ações Rápidas</h3>
                <div class="space-y-2">
                    <a href="{{ route('filament.funil.resources.events.create') }}" 
                       class="flex items-center gap-3 px-3 sm:px-4 py-2 sm:py-3 bg-white/10 rounded-xl text-white hover:bg-white/20 transition-colors w-full sm:w-auto justify-center sm:justify-start">
                        <x-heroicon-o-plus-circle class="w-5 h-5" />
                        <span class="font-medium">Novo Evento</span>
                    </a>
                    <a href="{{ route('filament.funil.resources.diligences.create') }}" 
                       class="flex items-center gap-3 px-3 sm:px-4 py-2 sm:py-3 bg-white/10 rounded-xl text-white hover:bg-white/20 transition-colors w-full sm:w-auto justify-center sm:justify-start">
                        <x-heroicon-o-clipboard-document-check class="w-5 h-5" />
                        <span class="font-medium">Nova Diligência</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
