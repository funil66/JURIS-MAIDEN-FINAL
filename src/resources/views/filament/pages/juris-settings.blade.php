<x-filament-panels::page>
    <div class="p-4 max-w-3xl">
        <x-filament::section>
            <x-slot name="heading">Configurações do Escritório</x-slot>
            <x-slot name="description">Altere informações de contato, OAB e cor primária do sistema.</x-slot>

            {{ $this->form }}

            <div class="mt-4">
                <x-filament::button wire:click="save">Salvar</x-filament::button>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
