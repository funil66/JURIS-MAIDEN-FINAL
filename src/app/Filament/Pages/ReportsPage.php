<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\Service;
use App\Models\Transaction;
use App\Exports\ServicesExport;
use App\Exports\ClientsExport;
use App\Exports\TransactionsExport;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Relatórios';
    protected static ?string $title = 'Relatórios';
    protected static ?string $navigationGroup = 'Relatórios';
    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.pages.reports-page';

    public ?string $report_type = null;
    public ?string $date_start = null;
    public ?string $date_end = null;
    public ?string $client_id = null;
    public ?string $service_status = null;
    public ?string $transaction_type = null;

    public function mount(): void
    {
        $this->form->fill([
            'date_start' => now()->startOfMonth()->format('Y-m-d'),
            'date_end' => now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuração do Relatório')
                    ->description('Selecione o tipo de relatório e os filtros desejados')
                    ->schema([
                        Select::make('report_type')
                            ->label('Tipo de Relatório')
                            ->options([
                                'services' => '📋 Relatório de Serviços',
                                'clients' => '👥 Relatório de Clientes',
                                'financial' => '💰 Relatório Financeiro',
                                'general' => '📊 Relatório Geral',
                            ])
                            ->required()
                            ->live()
                            ->columnSpanFull(),

                        DatePicker::make('date_start')
                            ->label('Data Inicial')
                            ->required()
                            ->default(now()->startOfMonth()),

                        DatePicker::make('date_end')
                            ->label('Data Final')
                            ->required()
                            ->default(now()->endOfMonth()),

                        Select::make('client_id')
                            ->label('Cliente')
                            ->options(Client::pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Todos os clientes')
                            ->visible(fn ($get) => in_array($get('report_type'), ['services', 'financial'])),

                        Select::make('service_status')
                            ->label('Status do Serviço')
                            ->options([
                                'pending' => 'Pendente',
                                'in_progress' => 'Em Andamento',
                                'completed' => 'Concluído',
                                'cancelled' => 'Cancelado',
                            ])
                            ->placeholder('Todos os status')
                            ->visible(fn ($get) => $get('report_type') === 'services'),

                        Select::make('transaction_type')
                            ->label('Tipo de Transação')
                            ->options([
                                'income' => 'Receitas',
                                'expense' => 'Despesas',
                            ])
                            ->placeholder('Todas as transações')
                            ->visible(fn ($get) => $get('report_type') === 'financial'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormStatePath(): ?string
    {
        return 'data';
    }

    public array $data = [];

    public function generateReport(): void
    {
        $data = $this->form->getState();

        if (empty($data['report_type'])) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Selecione um tipo de relatório',
            ]);
            return;
        }

        $pdf = match ($data['report_type']) {
            'services' => $this->generateServicesReport($data),
            'clients' => $this->generateClientsReport($data),
            'financial' => $this->generateFinancialReport($data),
            'general' => $this->generateGeneralReport($data),
            default => null,
        };

        if ($pdf) {
            $filename = "relatorio_{$data['report_type']}_" . now()->format('Y-m-d_His') . '.pdf';
            
            // Store temporarily and redirect
            $path = storage_path("app/public/reports/{$filename}");
            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }
            $pdf->save($path);

            $this->dispatch('open-url', url: asset("storage/reports/{$filename}"));
        }
    }

    protected function generateServicesReport(array $data): \Barryvdh\DomPDF\PDF
    {
        $query = Service::with(['client', 'serviceType'])
            ->whereBetween('scheduled_datetime', [$data['date_start'], $data['date_end']]);

        if (!empty($data['client_id'])) {
            $query->where('client_id', $data['client_id']);
        }

        if (!empty($data['service_status'])) {
            $query->where('status', $data['service_status']);
        }

        $services = $query->orderBy('scheduled_datetime', 'desc')->get();

        $summary = [
            'total' => $services->count(),
            'total_value' => $services->sum('value'),
            'by_status' => $services->groupBy('status')->map->count(),
            'by_type' => $services->groupBy('serviceType.name')->map->count(),
        ];

        return Pdf::loadView('reports.services', compact('services', 'summary', 'data'))
            ->setPaper('a4', 'portrait');
    }

    protected function generateClientsReport(array $data): \Barryvdh\DomPDF\PDF
    {
        $clients = Client::withCount(['services' => function ($query) use ($data) {
                $query->whereBetween('scheduled_datetime', [$data['date_start'], $data['date_end']]);
            }])
            ->withSum(['services' => function ($query) use ($data) {
                $query->whereBetween('scheduled_datetime', [$data['date_start'], $data['date_end']]);
            }], 'value')
            ->having('services_count', '>', 0)
            ->orderBy('services_sum_value', 'desc')
            ->get();

        $summary = [
            'total_clients' => $clients->count(),
            'total_services' => $clients->sum('services_count'),
            'total_value' => $clients->sum('services_sum_value'),
        ];

        return Pdf::loadView('reports.clients', compact('clients', 'summary', 'data'))
            ->setPaper('a4', 'portrait');
    }

    protected function generateFinancialReport(array $data): \Barryvdh\DomPDF\PDF
    {
        $query = Transaction::with(['client', 'service', 'paymentMethod'])
            ->whereBetween('due_date', [$data['date_start'], $data['date_end']]);

        if (!empty($data['client_id'])) {
            $query->where('client_id', $data['client_id']);
        }

        if (!empty($data['transaction_type'])) {
            $query->where('type', $data['transaction_type']);
        }

        $transactions = $query->orderBy('due_date', 'desc')->get();

        $summary = [
            'total_income' => $transactions->where('type', 'income')->sum('amount'),
            'total_expense' => $transactions->where('type', 'expense')->sum('amount'),
            'balance' => $transactions->where('type', 'income')->sum('amount') - $transactions->where('type', 'expense')->sum('amount'),
            'pending' => $transactions->where('status', 'pending')->sum('amount'),
            'paid' => $transactions->where('status', 'paid')->sum('amount'),
            'overdue' => $transactions->where('status', 'overdue')->sum('amount'),
        ];

        return Pdf::loadView('reports.financial', compact('transactions', 'summary', 'data'))
            ->setPaper('a4', 'portrait');
    }

    protected function generateGeneralReport(array $data): \Barryvdh\DomPDF\PDF
    {
        $services = Service::with(['client', 'serviceType'])
            ->whereBetween('scheduled_datetime', [$data['date_start'], $data['date_end']])
            ->get();

        $transactions = Transaction::with(['client', 'paymentMethod'])
            ->whereBetween('due_date', [$data['date_start'], $data['date_end']])
            ->get();

        $clients = Client::withCount(['services' => function ($query) use ($data) {
                $query->whereBetween('scheduled_datetime', [$data['date_start'], $data['date_end']]);
            }])
            ->having('services_count', '>', 0)
            ->get();

        $summary = [
            'services' => [
                'total' => $services->count(),
                'value' => $services->sum('value'),
                'by_status' => $services->groupBy('status')->map->count(),
            ],
            'financial' => [
                'income' => $transactions->where('type', 'income')->sum('amount'),
                'expense' => $transactions->where('type', 'expense')->sum('amount'),
                'balance' => $transactions->where('type', 'income')->sum('amount') - $transactions->where('type', 'expense')->sum('amount'),
            ],
            'clients' => [
                'active' => $clients->count(),
            ],
        ];

        return Pdf::loadView('reports.general', compact('services', 'transactions', 'clients', 'summary', 'data'))
            ->setPaper('a4', 'portrait');
    }

    public function exportExcel(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $data = $this->form->getState();

        if (empty($data['report_type'])) {
            Notification::make()
                ->title('Selecione um tipo de relatório')
                ->warning()
                ->send();
            return back();
        }

        $filename = "relatorio_{$data['report_type']}_" . now()->format('Y-m-d_His') . '.xlsx';

        return match ($data['report_type']) {
            'services' => Excel::download(
                new ServicesExport($data['date_start'], $data['date_end'], $data['client_id'] ?? null, $data['service_status'] ?? null),
                $filename
            ),
            'clients' => Excel::download(
                new ClientsExport($data['date_start'], $data['date_end']),
                $filename
            ),
            'financial' => Excel::download(
                new TransactionsExport($data['date_start'], $data['date_end'], $data['client_id'] ?? null, $data['transaction_type'] ?? null),
                $filename
            ),
            default => back(),
        };
    }

    public function exportCsv(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $data = $this->form->getState();

        if (empty($data['report_type'])) {
            Notification::make()
                ->title('Selecione um tipo de relatório')
                ->warning()
                ->send();
            return back();
        }

        $filename = "relatorio_{$data['report_type']}_" . now()->format('Y-m-d_His') . '.csv';

        return match ($data['report_type']) {
            'services' => Excel::download(
                new ServicesExport($data['date_start'], $data['date_end'], $data['client_id'] ?? null, $data['service_status'] ?? null),
                $filename,
                \Maatwebsite\Excel\Excel::CSV
            ),
            'clients' => Excel::download(
                new ClientsExport($data['date_start'], $data['date_end']),
                $filename,
                \Maatwebsite\Excel\Excel::CSV
            ),
            'financial' => Excel::download(
                new TransactionsExport($data['date_start'], $data['date_end'], $data['client_id'] ?? null, $data['transaction_type'] ?? null),
                $filename,
                \Maatwebsite\Excel\Excel::CSV
            ),
            default => back(),
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate')
                ->label('Gerar Relatório PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action('generateReport'),
        ];
    }
}
