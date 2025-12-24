<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\GeneratedReport;
use App\Models\Process;
use App\Models\ReportTemplate;
use App\Models\User;
use App\Services\ReportGeneratorService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdvancedReportsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Relatórios Avançados';
    protected static ?string $title = 'Relatórios Avançados';
    protected static ?string $navigationGroup = 'Relatórios';
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'advanced-reports';

    protected static string $view = 'filament.pages.advanced-reports';

    public array $data = [];
    public ?ReportTemplate $selectedTemplate = null;
    public ?array $previewData = null;
    public bool $showPreview = false;

    public function mount(): void
    {
        // Verifica se veio com template selecionado
        $templateId = request()->query('template');
        if ($templateId) {
            $this->selectedTemplate = ReportTemplate::find($templateId);
        }

        $this->form->fill([
            'type' => $this->selectedTemplate?->type ?? 'processes',
            'format' => $this->selectedTemplate?->default_format ?? 'pdf',
            'date_from' => now()->startOfMonth()->format('Y-m-d'),
            'date_to' => now()->endOfMonth()->format('Y-m-d'),
            'template_id' => $this->selectedTemplate?->id,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configuração do Relatório')
                    ->description('Selecione o tipo de relatório e configure os filtros')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Select::make('template_id')
                                    ->label('Template Salvo')
                                    ->options(
                                        ReportTemplate::where('user_id', Auth::id())
                                            ->orWhere('is_public', true)
                                            ->orderBy('is_favorite', 'desc')
                                            ->orderBy('usage_count', 'desc')
                                            ->pluck('name', 'id')
                                    )
                                    ->placeholder('Selecionar template...')
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        if ($state) {
                                            $template = ReportTemplate::find($state);
                                            if ($template) {
                                                $set('type', $template->type);
                                                $set('format', $template->default_format);
                                                $this->selectedTemplate = $template;
                                            }
                                        } else {
                                            $this->selectedTemplate = null;
                                        }
                                    }),

                                Forms\Components\Select::make('type')
                                    ->label('Tipo de Relatório')
                                    ->options(ReportTemplate::getTypeOptions())
                                    ->required()
                                    ->live()
                                    ->prefixIcon(fn ($state) => ReportTemplate::getTypeIcons()[$state] ?? 'heroicon-o-document'),

                                Forms\Components\Select::make('format')
                                    ->label('Formato')
                                    ->options([
                                        'pdf' => '📄 PDF',
                                        'excel' => '📊 Excel',
                                        'csv' => '📋 CSV',
                                    ])
                                    ->required()
                                    ->default('pdf'),

                                Forms\Components\DatePicker::make('date_from')
                                    ->label('Data Inicial')
                                    ->required()
                                    ->default(now()->startOfMonth()),

                                Forms\Components\DatePicker::make('date_to')
                                    ->label('Data Final')
                                    ->required()
                                    ->default(now()->endOfMonth())
                                    ->afterOrEqual('date_from'),
                            ]),
                    ]),

                Forms\Components\Section::make('Filtros Específicos')
                    ->description('Refine os dados do relatório')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\Select::make('client_id')
                                    ->label('Cliente')
                                    ->options(Client::pluck('name', 'id'))
                                    ->searchable()
                                    ->placeholder('Todos os clientes'),

                                Forms\Components\Select::make('process_id')
                                    ->label('Processo')
                                    ->options(Process::pluck('title', 'id'))
                                    ->searchable()
                                    ->placeholder('Todos os processos')
                                    ->visible(fn ($get) => in_array($get('type'), ['deadlines', 'diligences', 'time_entries', 'proceedings'])),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options(fn ($get) => $this->getStatusOptions($get('type')))
                                    ->placeholder('Todos os status')
                                    ->visible(fn ($get) => !in_array($get('type'), ['productivity', 'clients'])),

                                Forms\Components\Select::make('user_id')
                                    ->label('Responsável/Usuário')
                                    ->options(User::pluck('name', 'id'))
                                    ->searchable()
                                    ->placeholder('Todos os usuários')
                                    ->visible(fn ($get) => in_array($get('type'), ['time_entries', 'deadlines', 'diligences', 'processes', 'productivity'])),

                                // Filtros específicos por tipo
                                Forms\Components\Select::make('phase')
                                    ->label('Fase')
                                    ->options([
                                        'knowledge' => 'Conhecimento',
                                        'execution' => 'Execução',
                                        'appeal' => 'Recursal',
                                        'precautionary' => 'Cautelar',
                                    ])
                                    ->placeholder('Todas as fases')
                                    ->visible(fn ($get) => $get('type') === 'processes'),

                                Forms\Components\Select::make('priority')
                                    ->label('Prioridade')
                                    ->options([
                                        'low' => 'Baixa',
                                        'normal' => 'Normal',
                                        'high' => 'Alta',
                                        'critical' => 'Crítica',
                                    ])
                                    ->placeholder('Todas as prioridades')
                                    ->visible(fn ($get) => $get('type') === 'deadlines'),

                                Forms\Components\Toggle::make('billable')
                                    ->label('Apenas Faturáveis')
                                    ->visible(fn ($get) => $get('type') === 'time_entries'),
                            ]),
                    ])
                    ->collapsed()
                    ->collapsible(),

                Forms\Components\Section::make('Opções de Saída')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('include_summary')
                                    ->label('Incluir Resumo')
                                    ->default(true)
                                    ->helperText('Estatísticas resumidas'),

                                Forms\Components\Toggle::make('include_charts')
                                    ->label('Incluir Gráficos')
                                    ->default(true)
                                    ->helperText('Apenas para PDF')
                                    ->visible(fn ($get) => $get('format') === 'pdf'),

                                Forms\Components\Toggle::make('save_as_template')
                                    ->label('Salvar como Template')
                                    ->helperText('Salvar configuração para uso futuro'),
                            ]),

                        Forms\Components\TextInput::make('template_name')
                            ->label('Nome do Template')
                            ->placeholder('Ex: Relatório Mensal de Processos')
                            ->visible(fn ($get) => $get('save_as_template'))
                            ->required(fn ($get) => $get('save_as_template')),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ])
            ->statePath('data');
    }

    protected function getStatusOptions(?string $type): array
    {
        return match($type) {
            'processes' => [
                'active' => 'Ativo',
                'suspended' => 'Suspenso',
                'archived' => 'Arquivado',
                'closed_won' => 'Encerrado - Ganho',
                'closed_lost' => 'Encerrado - Perdido',
                'closed_settled' => 'Encerrado - Acordo',
            ],
            'deadlines' => [
                'pending' => 'Pendente',
                'in_progress' => 'Em Andamento',
                'completed' => 'Cumprido',
                'extended' => 'Prorrogado',
                'missed' => 'Perdido',
                'cancelled' => 'Cancelado',
            ],
            'diligences', 'services' => [
                'pending' => 'Pendente',
                'in_progress' => 'Em Andamento',
                'completed' => 'Concluído',
                'cancelled' => 'Cancelado',
                'rescheduled' => 'Reagendado',
            ],
            'contracts' => [
                'draft' => 'Rascunho',
                'active' => 'Ativo',
                'completed' => 'Concluído',
                'suspended' => 'Suspenso',
                'cancelled' => 'Cancelado',
            ],
            'invoices' => [
                'draft' => 'Rascunho',
                'pending' => 'Pendente',
                'paid' => 'Paga',
                'partial' => 'Parcial',
                'overdue' => 'Vencida',
                'cancelled' => 'Cancelada',
            ],
            'financial' => [
                'pending' => 'Pendente',
                'paid' => 'Pago',
                'overdue' => 'Vencido',
                'cancelled' => 'Cancelado',
            ],
            default => [],
        };
    }

    public function preview(): void
    {
        $data = $this->form->getState();

        try {
            $service = new ReportGeneratorService($this->selectedTemplate);
            $service->setFilters($this->buildFilters($data));

            $this->previewData = $service->preview($data['type']);
            $this->showPreview = true;

            Notification::make()
                ->title('Preview gerado')
                ->body("Mostrando {$this->previewData['data']->count()} de {$this->previewData['total_records']} registros")
                ->info()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao gerar preview')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function generate(): void
    {
        $data = $this->form->getState();

        try {
            // Salvar como template se solicitado
            if (!empty($data['save_as_template']) && !empty($data['template_name'])) {
                $template = $this->saveAsTemplate($data);
                $this->selectedTemplate = $template;
            }

            $service = new ReportGeneratorService($this->selectedTemplate);
            $service->setFilters($this->buildFilters($data));

            $report = $service->generate($data['type'], $data['format']);

            if ($report->status === GeneratedReport::STATUS_COMPLETED) {
                Notification::make()
                    ->title('Relatório gerado com sucesso!')
                    ->body("📊 {$report->records_count} registros | ⏱️ {$report->execution_time_formatted}")
                    ->success()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('download')
                            ->label('Download')
                            ->url(Storage::url($report->file_path))
                            ->openUrlInNewTab(),
                    ])
                    ->send();

                // Abre o arquivo automaticamente
                $this->dispatch('open-url', url: Storage::url($report->file_path));
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erro ao gerar relatório')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function buildFilters(array $data): array
    {
        $filters = [
            'date_from' => $data['date_from'] ?? null,
            'date_to' => $data['date_to'] ?? null,
        ];

        // Adiciona filtros específicos se preenchidos
        foreach (['client_id', 'process_id', 'status', 'user_id', 'phase', 'priority', 'billable'] as $key) {
            if (!empty($data[$key])) {
                $filters[$key] = $data[$key];
            }
        }

        return $filters;
    }

    protected function saveAsTemplate(array $data): ReportTemplate
    {
        return ReportTemplate::create([
            'user_id' => Auth::id(),
            'name' => $data['template_name'],
            'type' => $data['type'],
            'default_format' => $data['format'],
            'filters' => $this->buildFilters($data),
            'include_summary' => $data['include_summary'] ?? true,
            'include_charts' => $data['include_charts'] ?? true,
        ]);
    }

    public function closePreview(): void
    {
        $this->showPreview = false;
        $this->previewData = null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Visualizar')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->action('preview'),

            Action::make('generate')
                ->label('Gerar Relatório')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action('generate'),

            Action::make('templates')
                ->label('Templates')
                ->icon('heroicon-o-document-chart-bar')
                ->color('info')
                ->url(route('filament.funil.resources.report-templates.index')),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'recentReports' => GeneratedReport::forUser(Auth::id())
                ->completed()
                ->notExpired()
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),

            'favoriteTemplates' => ReportTemplate::where('user_id', Auth::id())
                ->where('is_favorite', true)
                ->orderBy('usage_count', 'desc')
                ->limit(5)
                ->get(),

            'previewData' => $this->previewData,
            'showPreview' => $this->showPreview,
        ];
    }
}
