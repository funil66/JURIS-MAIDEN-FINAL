<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Lista de Integrações Google Drive</x-slot>
            <x-slot name="description">Gerencie as contas do Google Drive conectadas por usuário.</x-slot>

            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
