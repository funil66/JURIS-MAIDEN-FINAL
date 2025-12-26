<?php

namespace App\Filament\Pages;

use App\Models\JurisSetting;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class JurisSettingsPage extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationLabel = 'Configurações (Escritório)';
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?string $slug = 'juris-settings';
    protected static ?int $navigationSort = 32;
    protected static string $view = 'filament.pages.juris-settings';

    protected function getHeaderActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('export')
                ->label('Exportar')
                ->action('export')
                ->visible(fn () => auth()->user()?->hasRole('admin')),

            Forms\Components\Actions\Action::make('import')
                ->label('Importar')
                ->modalHeading('Importar configurações (conteúdo encriptado)')
                ->form([
                    Forms\Components\Textarea::make('content')->label('Conteúdo criptografado')->rows(6)->required(),
                ])
                ->action(fn (array $data) => $this->import($data))
                ->visible(fn () => auth()->user()?->hasRole('admin')),
        ];
    }

    public $office_name;
    public $phone;
    public $whatsapp;
    public $contact_email;
    public $diligencias_email;
    public $address;
    public $oab;
    public $website;
    public $primary_color;

    public function mount(): void
    {
        $s = JurisSetting::firstOrMakeFromConfig();

        $this->form->fill([
            'office_name' => $s->office_name,
            'phone' => $s->phone,
            'whatsapp' => $s->whatsapp,
            'contact_email' => $s->contact_email,
            'diligencias_email' => $s->diligencias_email,
            'address' => $s->address,
            'oab' => $s->oab,
            'website' => $s->website,
            'primary_color' => $s->primary_color,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('office_name')->required(),
            Forms\Components\TextInput::make('phone')->required(),
            Forms\Components\TextInput::make('whatsapp'),
            Forms\Components\TextInput::make('contact_email')->email()->required(),
            Forms\Components\TextInput::make('diligencias_email')->email(),
            Forms\Components\TextInput::make('address'),
            Forms\Components\TextInput::make('oab'),
            Forms\Components\TextInput::make('website')->url(),
            Forms\Components\ColorPicker::make('primary_color'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $s = JurisSetting::firstOrMakeFromConfig();
        $s->update($data);

        Notification::make()->title('Configurações salvas')->success()->send();
    }

    // Export settings to encrypted file and return path
    public function export(): void
    {
        $s = JurisSetting::firstOrMakeFromConfig();
        $payload = json_encode($s->toArray());
        $encrypted = encrypt($payload);
        $path = 'juris-settings-' . now()->format('YmdHis') . '.json.enc';
        \Illuminate\Support\Facades\Storage::put($path, $encrypted);

        Notification::make()->title('Export gerado')->body("Arquivo: {$path}")->success()->send();
    }

    // Import from pasted encrypted content
    public function import(array $data): void
    {
        if (empty($data['content'])) {
            Notification::make()->title('Conteúdo vazio')->danger()->send();
            return;
        }

        try {
            $json = decrypt($data['content']);
            $payload = json_decode($json, true);
        } catch (\Exception $e) {
            Notification::make()->title('Falha ao importar')->danger()->send();
            return;
        }

        $s = JurisSetting::firstOrMakeFromConfig();
        $s->update($payload);

        Notification::make()->title('Importado com sucesso')->success()->send();
    }
}
