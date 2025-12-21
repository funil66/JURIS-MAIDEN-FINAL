<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Agenda de Eventos
            </x-slot>
            <x-slot name="description">
                Acompanhe audiências, reuniões, prazos e compromissos relacionados aos seus processos
            </x-slot>

            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
