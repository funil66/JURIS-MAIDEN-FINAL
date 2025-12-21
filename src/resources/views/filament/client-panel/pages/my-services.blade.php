<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Lista de Serviços
            </x-slot>
            <x-slot name="description">
                Acompanhe todos os serviços jurídicos contratados
            </x-slot>

            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
