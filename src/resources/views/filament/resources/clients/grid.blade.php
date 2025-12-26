<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4 p-4">
    @foreach ($records as $record)
        @include('filament.resources.clients.card', ['record' => $record])
    @endforeach
</div>
