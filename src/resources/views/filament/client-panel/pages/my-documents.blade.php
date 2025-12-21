<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Documentos Gerados
            </x-slot>
            <x-slot name="description">
                Acesse e faça download dos documentos gerados para seus processos
            </x-slot>

            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
