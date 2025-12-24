<?php

namespace App\Filament\Resources\DeadlineResource\Pages;

use App\Filament\Resources\DeadlineResource;
use App\Models\Deadline;
use App\Models\DeadlineType;
use App\Models\Process;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

class CalculateDeadline extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = DeadlineResource::class;

    protected static string $view = 'filament.resources.deadline-resource.pages.calculate-deadline';

    protected static ?string $title = 'Calculadora de Prazos';

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    public ?array $data = [];
    public ?Carbon $calculatedDate = null;
    public ?array $calculationDetails = null;

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->format('Y-m-d'),
            'days_count' => 15,
            'counting_type' => Deadline::COUNTING_BUSINESS_DAYS,
            'excludes_start_date' => true,
            'extends_to_next_business_day' => true,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Calculadora de Prazos')
                    ->description('Calcule a data de vencimento de um prazo processual')
                    ->schema([
                        Forms\Components\Select::make('deadline_type_id')
                            ->label('Tipo de Prazo (opcional)')
                            ->options(DeadlineType::active()->pluck('name', 'id'))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $type = DeadlineType::find($state);
                                    if ($type) {
                                        $set('days_count', $type->default_days);
                                        $set('counting_type', $type->counting_type);
                                        $set('excludes_start_date', $type->excludes_start_date);
                                        $set('extends_to_next_business_day', $type->extends_to_next_business_day);
                                    }
                                }
                            }),

                        Forms\Components\DatePicker::make('start_date')
                            ->label('Data de Início (Publicação/Intimação)')
                            ->required()
                            ->default(now()),

                        Forms\Components\TextInput::make('days_count')
                            ->label('Quantidade de Dias')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->default(15),

                        Forms\Components\Select::make('counting_type')
                            ->label('Tipo de Contagem')
                            ->options(Deadline::COUNTING_TYPES)
                            ->required()
                            ->default(Deadline::COUNTING_BUSINESS_DAYS),

                        Forms\Components\Select::make('state')
                            ->label('Estado (para feriados)')
                            ->options([
                                'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
                                'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
                                'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
                                'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
                                'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
                                'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
                                'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins',
                            ])
                            ->searchable()
                            ->placeholder('Nacional (sem estado específico)'),

                        Forms\Components\Toggle::make('excludes_start_date')
                            ->label('Exclui dia inicial')
                            ->helperText('Prazo começa no dia seguinte à publicação')
                            ->default(true),

                        Forms\Components\Toggle::make('extends_to_next_business_day')
                            ->label('Prorroga para próximo dia útil')
                            ->helperText('Se vencer em fds/feriado, prorroga para próximo dia útil')
                            ->default(true),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Criar Prazo (opcional)')
                    ->description('Preencha para criar um novo prazo automaticamente')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('process_id')
                            ->label('Processo')
                            ->options(Process::active()->limit(100)->get()->pluck('title', 'id'))
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('title')
                            ->label('Título do Prazo')
                            ->maxLength(255),

                        Forms\Components\Select::make('priority')
                            ->label('Prioridade')
                            ->options(Deadline::PRIORITIES)
                            ->default(Deadline::PRIORITY_NORMAL),

                        Forms\Components\Textarea::make('description')
                            ->label('Descrição')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function calculate(): void
    {
        $data = $this->form->getState();

        $startDate = Carbon::parse($data['start_date']);
        
        $this->calculatedDate = Deadline::calculateDueDate(
            $startDate,
            (int) $data['days_count'],
            $data['counting_type'],
            $data['excludes_start_date'],
            $data['extends_to_next_business_day'],
            $data['state'] ?? null
        );

        // Calcular detalhes
        $this->calculationDetails = [
            'start_date' => $startDate->format('d/m/Y'),
            'start_day_name' => $startDate->translatedFormat('l'),
            'days_count' => $data['days_count'],
            'counting_type' => Deadline::COUNTING_TYPES[$data['counting_type']] ?? $data['counting_type'],
            'due_date' => $this->calculatedDate->format('d/m/Y'),
            'due_day_name' => $this->calculatedDate->translatedFormat('l'),
            'calendar_days' => $startDate->diffInDays($this->calculatedDate),
            'excludes_start' => $data['excludes_start_date'] ? 'Sim' : 'Não',
            'extends' => $data['extends_to_next_business_day'] ? 'Sim' : 'Não',
        ];

        Notification::make()
            ->title('Prazo Calculado')
            ->body("Data de vencimento: {$this->calculatedDate->format('d/m/Y')} ({$this->calculationDetails['due_day_name']})")
            ->success()
            ->send();
    }

    public function createDeadline(): void
    {
        if (!$this->calculatedDate) {
            Notification::make()
                ->title('Calcule primeiro')
                ->body('Clique em "Calcular" antes de criar o prazo.')
                ->warning()
                ->send();
            return;
        }

        $data = $this->form->getState();

        if (empty($data['process_id'])) {
            Notification::make()
                ->title('Selecione um processo')
                ->body('Para criar o prazo, selecione um processo.')
                ->warning()
                ->send();
            return;
        }

        $deadline = Deadline::create([
            'process_id' => $data['process_id'],
            'deadline_type_id' => $data['deadline_type_id'] ?? null,
            'start_date' => $data['start_date'],
            'due_date' => $this->calculatedDate,
            'title' => $data['title'] ?? ($data['deadline_type_id'] ? DeadlineType::find($data['deadline_type_id'])?->name : 'Prazo Processual'),
            'description' => $data['description'] ?? null,
            'days_count' => $data['days_count'],
            'counting_type' => $data['counting_type'],
            'priority' => $data['priority'] ?? Deadline::PRIORITY_NORMAL,
            'created_by_user_id' => auth()->id(),
            'assigned_user_id' => auth()->id(),
        ]);

        Notification::make()
            ->title('Prazo Criado!')
            ->body("Prazo {$deadline->uid} criado com sucesso.")
            ->success()
            ->send();

        $this->redirect(DeadlineResource::getUrl('view', ['record' => $deadline]));
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
