<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Cards de Tipos de Relatório --}}
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @php
                $reportTypes = [
                    'processes' => ['icon' => 'heroicon-o-scale', 'color' => 'blue', 'label' => 'Processos'],
                    'deadlines' => ['icon' => 'heroicon-o-clock', 'color' => 'orange', 'label' => 'Prazos'],
                    'diligences' => ['icon' => 'heroicon-o-clipboard-document-check', 'color' => 'purple', 'label' => 'Diligências'],
                    'time_entries' => ['icon' => 'heroicon-o-play', 'color' => 'cyan', 'label' => 'Tempo'],
                    'invoices' => ['icon' => 'heroicon-o-document-currency-dollar', 'color' => 'green', 'label' => 'Faturas'],
                    'clients' => ['icon' => 'heroicon-o-users', 'color' => 'indigo', 'label' => 'Clientes'],
                ];
            @endphp
            
            @foreach($reportTypes as $type => $config)
                <button 
                    wire:click="$set('data.type', '{{ $type }}')"
                    class="p-4 rounded-xl border-2 transition-all hover:shadow-lg {{ ($this->data['type'] ?? '') === $type ? 'border-primary-500 bg-primary-50 dark:bg-primary-950' : 'border-gray-200 dark:border-gray-700 hover:border-primary-300' }}"
                >
                    <div class="flex flex-col items-center gap-2">
                        <x-dynamic-component :component="$config['icon']" class="w-8 h-8 text-{{ $config['color'] }}-500" />
                        <span class="text-sm font-medium">{{ $config['label'] }}</span>
                    </div>
                </button>
            @endforeach
        </div>

        {{-- Formulário Principal --}}
        <form wire:submit="generate">
            {{ $this->form }}
        </form>

        {{-- Templates Favoritos --}}
        @if($favoriteTemplates->count() > 0)
            <x-filament::section collapsible collapsed>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-star class="w-5 h-5 text-warning-500" />
                        Templates Favoritos
                    </div>
                </x-slot>
                
                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                    @foreach($favoriteTemplates as $template)
                        <a 
                            href="{{ route('filament.funil.pages.advanced-reports', ['template' => $template->id]) }}"
                            class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all"
                        >
                            <div class="flex items-center gap-2 mb-1">
                                <x-heroicon-s-star class="w-4 h-4 text-warning-500" />
                                <span class="text-sm font-medium truncate">{{ $template->name }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                <span class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700">
                                    {{ strtoupper($template->default_format) }}
                                </span>
                                <span>{{ $template->usage_count }} usos</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        {{-- Preview Modal --}}
        @if($showPreview && $previewData)
            <x-filament::modal id="preview-modal" :close-button="true" slide-over width="5xl">
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-eye class="w-5 h-5" />
                        Preview do Relatório
                    </div>
                </x-slot>
                
                <x-slot name="description">
                    Mostrando {{ $previewData['data']->count() }} de {{ $previewData['total_records'] }} registros
                </x-slot>

                {{-- Resumo --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    @foreach($previewData['summary'] as $key => $value)
                        @if(!is_array($value))
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                <div class="text-xs text-gray-500 uppercase">{{ str_replace('_', ' ', $key) }}</div>
                                <div class="text-lg font-semibold">
                                    @if(is_numeric($value) && $value >= 100)
                                        R$ {{ number_format($value, 2, ',', '.') }}
                                    @else
                                        {{ $value }}
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Tabela de Dados --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 dark:bg-gray-700">
                            <tr>
                                @foreach($previewData['columns'] as $column)
                                    <th class="px-3 py-2 text-left font-medium">{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($previewData['data'] as $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                    @foreach(array_keys($previewData['columns']) as $column)
                                        <td class="px-3 py-2">
                                            @php
                                                $parts = explode('.', $column);
                                                $value = $item;
                                                foreach($parts as $part) {
                                                    $value = is_object($value) ? ($value->{$part} ?? null) : ($value[$part] ?? null);
                                                }
                                            @endphp
                                            @if($value instanceof \Carbon\Carbon)
                                                {{ $value->format('d/m/Y') }}
                                            @elseif(is_bool($value))
                                                {{ $value ? 'Sim' : 'Não' }}
                                            @elseif(is_numeric($value) && $value >= 100)
                                                R$ {{ number_format($value, 2, ',', '.') }}
                                            @else
                                                {{ $value ?? '-' }}
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-slot name="footerActions">
                    <x-filament::button color="gray" wire:click="closePreview">
                        Fechar
                    </x-filament::button>
                    <x-filament::button color="success" wire:click="generate">
                        Gerar Relatório Completo
                    </x-filament::button>
                </x-slot>
            </x-filament::modal>
        @endif

        {{-- Relatórios Recentes --}}
        @if($recentReports->count() > 0)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clock class="w-5 h-5 text-gray-500" />
                        Relatórios Recentes
                    </div>
                </x-slot>
                
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($recentReports as $report)
                        <div class="flex items-center justify-between py-3">
                            <div class="flex items-center gap-3">
                                @php
                                    $formatColors = [
                                        'pdf' => 'danger',
                                        'excel' => 'success',
                                        'csv' => 'gray',
                                    ];
                                @endphp
                                <x-filament::badge :color="$formatColors[$report->format] ?? 'gray'">
                                    {{ strtoupper($report->format) }}
                                </x-filament::badge>
                                
                                <div>
                                    <div class="font-medium">{{ $report->name }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ $report->records_count }} registros • 
                                        {{ $report->file_size_formatted }} •
                                        {{ $report->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                @if($report->expires_at)
                                    <span class="text-xs text-gray-400">
                                        Expira {{ $report->expires_at->diffForHumans() }}
                                    </span>
                                @endif
                                
                                @if($report->file_path && \Illuminate\Support\Facades\Storage::exists($report->file_path))
                                    <x-filament::button 
                                        size="sm" 
                                        color="gray"
                                        tag="a"
                                        href="{{ \Illuminate\Support\Facades\Storage::url($report->file_path) }}"
                                        target="_blank"
                                    >
                                        <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                    </x-filament::button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        {{-- Informações dos Tipos de Relatório --}}
        <x-filament::section collapsible collapsed>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-info-500" />
                    Tipos de Relatório Disponíveis
                </div>
            </x-slot>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @php
                    $typeDescriptions = [
                        'processes' => 'Relatório completo de processos judiciais com status, fases, valores da causa e responsáveis.',
                        'deadlines' => 'Análise de prazos processuais com alertas de vencimento, prioridades e cumprimentos.',
                        'diligences' => 'Controle de diligências realizadas com custos estimados vs reais e resultados.',
                        'time_entries' => 'Lançamentos de tempo com horas faturáveis, valores por hora e produtividade.',
                        'contracts' => 'Contratos e honorários com parcelas, valores pagos e pendentes.',
                        'invoices' => 'Faturas emitidas com status de pagamento, vencimentos e totais.',
                        'clients' => 'Cadastro de clientes com quantidade de processos e valores faturados.',
                        'financial' => 'Movimentação financeira com receitas, despesas e saldo.',
                        'services' => 'Serviços de diligência com agendamentos, status e valores.',
                        'productivity' => 'Análise de produtividade por usuário com horas trabalhadas e faturadas.',
                    ];
                @endphp
                
                @foreach($typeDescriptions as $type => $description)
                    <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="font-medium">{{ \App\Models\ReportTemplate::getTypeOptions()[$type] ?? $type }}</span>
                        </div>
                        <p class="text-sm text-gray-500">{{ $description }}</p>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>

    @script
    <script>
        $wire.on('open-url', ({ url }) => {
            window.open(url, '_blank');
        });
    </script>
    @endscript
</x-filament-panels::page>
