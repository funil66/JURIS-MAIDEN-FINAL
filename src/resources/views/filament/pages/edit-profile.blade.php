<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Formulário de Perfil --}}
        <form wire:submit="updateProfile">
            {{ $this->profileForm }}

            <div class="mt-6 flex justify-end">
                <x-filament::button type="submit" icon="heroicon-o-check">
                    Salvar Perfil
                </x-filament::button>
            </div>
        </form>

        {{-- Formulário de Senha --}}
        <form wire:submit="updatePassword">
            {{ $this->passwordForm }}

            <div class="mt-6 flex justify-end">
                <x-filament::button type="submit" color="warning" icon="heroicon-o-key">
                    Alterar Senha
                </x-filament::button>
            </div>
        </form>

        {{-- Informações da Conta --}}
        <x-filament::section>
            <x-slot name="heading">
                📊 Informações da Conta
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Membro desde:</span>
                    <p class="font-medium">{{ auth()->user()->created_at->format('d/m/Y') }}</p>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Último acesso:</span>
                    <p class="font-medium">{{ auth()->user()->updated_at->diffForHumans() }}</p>
                </div>
                <div>
                    <span class="text-gray-500 dark:text-gray-400">Status:</span>
                    <p class="font-medium">
                        @if(auth()->user()->is_active)
                            <span class="text-green-600">✅ Ativo</span>
                        @else
                            <span class="text-red-600">❌ Inativo</span>
                        @endif
                    </p>
                </div>
            </div>

            @if(auth()->user()->oab_formatted)
                <div class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                    <span class="text-amber-700 dark:text-amber-300 font-semibold">
                        🎓 {{ auth()->user()->oab_formatted }}
                    </span>
                    @if(auth()->user()->specialties_text)
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Especialidades: {{ auth()->user()->specialties_text }}
                        </p>
                    @endif
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
