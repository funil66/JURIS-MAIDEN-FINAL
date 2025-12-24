<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\TimeEntry;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class CreateFromTimeEntries extends Page
{
    protected static string $resource = InvoiceResource::class;

    protected static string $view = 'filament.resources.invoice-resource.pages.create-from-time-entries';

    protected static ?string $title = 'Faturar Horas Trabalhadas';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Seleção de Cliente')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Cliente')
                            ->options(
                                Client::whereHas('timeEntries', function (Builder $query) {
                                    $query->where('status', 'approved')
                                        ->whereNull('invoice_id')
                                        ->where('is_billable', true);
                                })->pluck('name', 'id')
                            )
                            ->required()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('time_entries', [])),
                    ]),

                Forms\Components\Section::make('Horas a Faturar')
                    ->visible(fn (Get $get) => $get('client_id'))
                    ->schema([
                        Forms\Components\CheckboxList::make('time_entries')
                            ->label('')
                            ->options(function (Get $get) {
                                $clientId = $get('client_id');
                                if (!$clientId) return [];

                                return TimeEntry::where('client_id', $clientId)
                                    ->where('status', 'approved')
                                    ->whereNull('invoice_id')
                                    ->where('is_billable', true)
                                    ->orderBy('work_date', 'desc')
                                    ->get()
                                    ->mapWithKeys(fn ($entry) => [
                                        $entry->id => sprintf(
                                            '%s | %s | %s | %s | R$ %s',
                                            $entry->work_date->format('d/m/Y'),
                                            $entry->user->name ?? '-',
                                            $entry->formatted_duration,
                                            substr($entry->description, 0, 50),
                                            number_format($entry->total_amount, 2, ',', '.')
                                        )
                                    ]);
                            })
                            ->required()
                            ->columns(1)
                            ->bulkToggleable()
                            ->gridDirection('row'),

                        Forms\Components\Placeholder::make('summary')
                            ->label('Resumo')
                            ->content(function (Get $get) {
                                $entries = $get('time_entries') ?? [];
                                if (empty($entries)) {
                                    return 'Selecione as horas a serem faturadas';
                                }

                                $timeEntries = TimeEntry::whereIn('id', $entries)->get();
                                $totalMinutes = $timeEntries->sum('duration_minutes');
                                $totalAmount = $timeEntries->sum('total_amount');

                                $hours = floor($totalMinutes / 60);
                                $mins = $totalMinutes % 60;

                                return sprintf(
                                    '%d registro(s) | %dh%02dm | Total: R$ %s',
                                    count($entries),
                                    $hours,
                                    $mins,
                                    number_format($totalAmount, 2, ',', '.')
                                );
                            }),
                    ]),

                Forms\Components\Section::make('Dados da Fatura')
                    ->columns(2)
                    ->visible(fn (Get $get) => !empty($get('time_entries')))
                    ->schema([
                        Forms\Components\TextInput::make('description')
                            ->label('Descrição')
                            ->default('Fatura de Honorários - Horas Trabalhadas')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('issue_date')
                            ->label('Data de Emissão')
                            ->required()
                            ->native(false)
                            ->default(now()),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Data de Vencimento')
                            ->required()
                            ->native(false)
                            ->default(now()->addDays(30)),

                        Forms\Components\Textarea::make('notes')
                            ->label('Observações')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        $data = $this->form->getState();

        if (empty($data['time_entries'])) {
            Notification::make()
                ->title('Erro')
                ->body('Selecione pelo menos uma entrada de tempo.')
                ->danger()
                ->send();
            return;
        }

        $invoice = Invoice::createFromTimeEntries(
            $data['time_entries'],
            $data['client_id'],
            [
                'description' => $data['description'],
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'notes' => $data['notes'] ?? null,
            ]
        );

        Notification::make()
            ->title('Fatura Criada')
            ->body("Fatura {$invoice->invoice_number} criada com sucesso.")
            ->success()
            ->send();

        $this->redirect(InvoiceResource::getUrl('view', ['record' => $invoice]));
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('create')
                ->label('Criar Fatura')
                ->submit('create'),
        ];
    }
}
