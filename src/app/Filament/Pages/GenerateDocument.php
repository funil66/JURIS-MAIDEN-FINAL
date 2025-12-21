<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\DocumentTemplate;
use App\Models\GeneratedDocument;
use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GenerateDocument extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';
    protected static ?string $navigationLabel = 'Gerar Documento';
    protected static ?string $title = 'Gerar Documento';
    protected static ?string $navigationGroup = 'Documentos';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.generate-document';

    public ?array $data = [];
    public ?string $preview = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('1. Selecione o Template')
                    ->description('Escolha o modelo de documento que deseja gerar')
                    ->schema([
                        Select::make('template_id')
                            ->label('Template')
                            ->options(
                                DocumentTemplate::active()
                                    ->orderBy('category')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn ($t) => [
                                        $t->id => DocumentTemplate::getCategoryOptions()[$t->category] . ' - ' . $t->name
                                    ])
                            )
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                if ($state) {
                                    $template = DocumentTemplate::find($state);
                                    if ($template) {
                                        $set('template_preview', $template->description);
                                    }
                                }
                                $this->preview = null;
                            }),

                        Textarea::make('template_preview')
                            ->label('Descrição do Template')
                            ->disabled()
                            ->rows(2)
                            ->visible(fn (Get $get) => !empty($get('template_id'))),
                    ]),

                Section::make('2. Vincular a Registros (Opcional)')
                    ->description('Vincule o documento a um cliente ou serviço para preencher automaticamente')
                    ->columns(2)
                    ->schema([
                        Select::make('client_id')
                            ->label('Cliente')
                            ->options(Client::active()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn () => $this->preview = null),

                        Select::make('service_id')
                            ->label('Serviço')
                            ->options(function (Get $get) {
                                $clientId = $get('client_id');
                                if ($clientId) {
                                    return Service::where('client_id', $clientId)
                                        ->orderBy('created_at', 'desc')
                                        ->get()
                                        ->mapWithKeys(fn ($s) => [$s->id => $s->code . ' - ' . ($s->serviceType?->name ?? 'Serviço')]);
                                }
                                return Service::orderBy('created_at', 'desc')
                                    ->take(50)
                                    ->get()
                                    ->mapWithKeys(fn ($s) => [$s->id => $s->code . ' - ' . ($s->serviceType?->name ?? 'Serviço')]);
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn () => $this->preview = null),
                    ]),

                Section::make('3. Informações do Documento')
                    ->schema([
                        TextInput::make('title')
                            ->label('Título do Documento')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Procuração - João Silva - Processo 1234567'),
                    ]),
            ])
            ->statePath('data');
    }

    public function generatePreview(): void
    {
        $this->validate();
        
        $template = DocumentTemplate::find($this->data['template_id']);
        if (!$template) {
            Notification::make()
                ->title('Template não encontrado')
                ->danger()
                ->send();
            return;
        }

        // Buscar dados relacionados
        $client = isset($this->data['client_id']) ? Client::find($this->data['client_id']) : null;
        $service = isset($this->data['service_id']) ? Service::find($this->data['service_id']) : null;
        $user = Auth::user();

        // Preencher variáveis do sistema
        $variables = $template->fillSystemVariables($user, $client, $service);

        // Gerar conteúdo
        $this->preview = $template->generateContent($variables);
    }

    public function generatePdf(): void
    {
        $this->validate();

        $template = DocumentTemplate::find($this->data['template_id']);
        if (!$template) {
            Notification::make()
                ->title('Template não encontrado')
                ->danger()
                ->send();
            return;
        }

        // Buscar dados relacionados
        $client = isset($this->data['client_id']) ? Client::find($this->data['client_id']) : null;
        $service = isset($this->data['service_id']) ? Service::find($this->data['service_id']) : null;
        $user = Auth::user();

        // Preencher variáveis
        $variables = $template->fillSystemVariables($user, $client, $service);
        $content = $template->generateContent($variables);

        // Gerar PDF
        $pdf = Pdf::loadView('documents.pdf-template', [
            'content' => $content,
            'title' => $this->data['title'],
            'template' => $template,
        ]);

        $pdf->setPaper($template->format, $template->orientation);

        // Salvar arquivo
        $fileName = \Illuminate\Support\Str::slug($this->data['title']) . '-' . now()->format('Y-m-d-His') . '.pdf';
        $filePath = 'documents/' . $fileName;
        
        Storage::put($filePath, $pdf->output());

        // Criar registro do documento gerado
        $generatedDoc = GeneratedDocument::create([
            'document_template_id' => $template->id,
            'client_id' => $client?->id,
            'service_id' => $service?->id,
            'user_id' => $user->id,
            'title' => $this->data['title'],
            'content' => $content,
            'variables_used' => $variables,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => Storage::size($filePath),
            'status' => 'generated',
        ]);

        // Incrementar uso do template
        $template->incrementUsage();

        Notification::make()
            ->title('Documento gerado com sucesso!')
            ->body("Arquivo: {$fileName}")
            ->success()
            ->send();

        // Redirecionar para download
        $this->redirect(route('filament.funil.resources.generated-documents.index'));
    }

    public function resetForm(): void
    {
        $this->form->fill();
        $this->preview = null;
    }
}
