<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class EditProfile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Meu Perfil';
    protected static ?string $title = 'Meu Perfil';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.edit-profile';

    public ?array $profileData = [];
    public ?array $passwordData = [];

    public function mount(): void
    {
        $user = Auth::user();
        
        $this->profileData = [
            'name' => $user->name,
            'email' => $user->email,
            'oab' => $user->oab,
            'oab_uf' => $user->oab_uf,
            'specialties' => $user->specialties ?? [],
            'phone' => $user->phone,
            'whatsapp' => $user->whatsapp,
            'bio' => $user->bio,
            'website' => $user->website,
            'linkedin' => $user->linkedin,
        ];
    }

    protected function getForms(): array
    {
        return [
            'profileForm',
            'passwordForm',
        ];
    }

    public function profileForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informações Pessoais')
                    ->description('Dados básicos da sua conta')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Nome Completo')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('email')
                                ->label('E-mail')
                                ->email()
                                ->required()
                                ->unique('users', 'email', ignoreRecord: true),
                        ]),

                        Grid::make(2)->schema([
                            TextInput::make('phone')
                                ->label('Telefone')
                                ->tel()
                                ->mask('(99) 99999-9999'),

                            TextInput::make('whatsapp')
                                ->label('WhatsApp')
                                ->tel()
                                ->mask('(99) 99999-9999'),
                        ]),
                    ]),

                Section::make('Dados Profissionais (OAB)')
                    ->description('Informações da sua inscrição na OAB')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('oab')
                                ->label('Número OAB')
                                ->placeholder('123.456')
                                ->maxLength(20),

                            Select::make('oab_uf')
                                ->label('UF da OAB')
                                ->options(User::getOabStates())
                                ->searchable(),
                        ]),

                        TagsInput::make('specialties')
                            ->label('Áreas de Atuação')
                            ->placeholder('Digite e pressione Enter')
                            ->suggestions(User::getLegalSpecialties())
                            ->helperText('Selecione ou digite suas áreas de especialização'),
                    ]),

                Section::make('Informações Adicionais')
                    ->description('Dados complementares do seu perfil')
                    ->icon('heroicon-o-information-circle')
                    ->collapsed()
                    ->schema([
                        Textarea::make('bio')
                            ->label('Biografia / Currículo Resumido')
                            ->rows(4)
                            ->maxLength(1000)
                            ->placeholder('Conte um pouco sobre sua experiência profissional...'),

                        Grid::make(2)->schema([
                            TextInput::make('website')
                                ->label('Site Pessoal')
                                ->url()
                                ->placeholder('https://seusite.com.br'),

                            TextInput::make('linkedin')
                                ->label('LinkedIn')
                                ->url()
                                ->placeholder('https://linkedin.com/in/seuperfil'),
                        ]),
                    ]),
            ])
            ->statePath('profileData');
    }

    public function passwordForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Alterar Senha')
                    ->description('Deixe em branco para manter a senha atual')
                    ->icon('heroicon-o-lock-closed')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Senha Atual')
                            ->password()
                            ->autocomplete('current-password')
                            ->requiredWith('new_password'),

                        TextInput::make('new_password')
                            ->label('Nova Senha')
                            ->password()
                            ->autocomplete('new-password')
                            ->minLength(8)
                            ->same('new_password_confirmation'),

                        TextInput::make('new_password_confirmation')
                            ->label('Confirmar Nova Senha')
                            ->password()
                            ->autocomplete('new-password')
                            ->requiredWith('new_password'),
                    ]),
            ])
            ->statePath('passwordData');
    }

    public function updateProfile(): void
    {
        $data = $this->profileForm->getState();
        
        $user = Auth::user();
        $user->update($data);

        Notification::make()
            ->title('Perfil atualizado!')
            ->success()
            ->send();
    }

    public function updatePassword(): void
    {
        $data = $this->passwordForm->getState();
        
        if (empty($data['new_password'])) {
            return;
        }

        $user = Auth::user();

        if (!Hash::check($data['current_password'], $user->password)) {
            Notification::make()
                ->title('Senha atual incorreta!')
                ->danger()
                ->send();
            return;
        }

        $user->update([
            'password' => Hash::make($data['new_password']),
        ]);

        $this->passwordData = [];

        Notification::make()
            ->title('Senha alterada com sucesso!')
            ->success()
            ->send();
    }
}
