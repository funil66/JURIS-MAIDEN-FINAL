<div class="space-y-4">
    {{-- Informações Básicas --}}
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-sm text-gray-500">Código</p>
            <p class="font-medium">{{ $service->code }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Nº Ordem</p>
            <p class="font-medium">#{{ str_pad($service->order_number, 5, '0', STR_PAD_LEFT) }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Tipo de Serviço</p>
            <p class="font-medium">{{ $service->serviceType?->name ?? '-' }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Status</p>
            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                @if($service->status === 'completed') bg-green-100 text-green-700
                @elseif($service->status === 'in_progress') bg-blue-100 text-blue-700
                @elseif($service->status === 'confirmed') bg-purple-100 text-purple-700
                @elseif($service->status === 'cancelled') bg-red-100 text-red-700
                @else bg-yellow-100 text-yellow-700
                @endif">
                {{ \App\Models\Service::getStatusOptions()[$service->status] ?? $service->status }}
            </span>
        </div>
    </div>

    {{-- Processo --}}
    @if($service->process_number)
        <div class="border-t pt-4">
            <h4 class="font-medium mb-2">Dados do Processo</h4>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Número do Processo</p>
                    <p class="font-medium">{{ $service->process_number }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Comarca</p>
                    <p class="font-medium">{{ $service->court ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Vara/Jurisdição</p>
                    <p class="font-medium">{{ $service->jurisdiction ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Estado</p>
                    <p class="font-medium">{{ $service->state ?? '-' }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Datas --}}
    <div class="border-t pt-4">
        <h4 class="font-medium mb-2">Datas</h4>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">Data de Solicitação</p>
                <p class="font-medium">{{ $service->request_date?->format('d/m/Y') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Data Agendada</p>
                <p class="font-medium">{{ $service->scheduled_datetime?->format('d/m/Y H:i') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Prazo Final</p>
                <p class="font-medium @if($service->deadline_date && $service->deadline_date->isPast() && $service->status !== 'completed') text-red-600 @endif">
                    {{ $service->deadline_date?->format('d/m/Y') ?? '-' }}
                </p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Data de Conclusão</p>
                <p class="font-medium">{{ $service->completion_date?->format('d/m/Y') ?? '-' }}</p>
            </div>
        </div>
    </div>

    {{-- Local --}}
    @if($service->location_name || $service->location_address)
        <div class="border-t pt-4">
            <h4 class="font-medium mb-2">Local</h4>
            <div>
                @if($service->location_name)
                    <p class="font-medium">{{ $service->location_name }}</p>
                @endif
                @if($service->location_address)
                    <p class="text-gray-600">{{ $service->location_address }}</p>
                @endif
                @if($service->location_city)
                    <p class="text-gray-500">{{ $service->location_city }} - {{ $service->location_state }}</p>
                @endif
            </div>
        </div>
    @endif

    {{-- Valores --}}
    <div class="border-t pt-4">
        <h4 class="font-medium mb-2">Valores</h4>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <p class="text-sm text-gray-500">Valor Acordado</p>
                <p class="font-medium">R$ {{ number_format($service->agreed_price ?? 0, 2, ',', '.') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Despesas</p>
                <p class="font-medium">R$ {{ number_format($service->expenses ?? 0, 2, ',', '.') }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total</p>
                <p class="font-bold text-lg">R$ {{ number_format($service->total_price ?? 0, 2, ',', '.') }}</p>
            </div>
        </div>
        <div class="mt-2">
            <p class="text-sm text-gray-500">Status do Pagamento</p>
            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full
                @if($service->payment_status === 'pago') bg-green-100 text-green-700
                @elseif($service->payment_status === 'parcial') bg-yellow-100 text-yellow-700
                @else bg-red-100 text-red-700
                @endif">
                {{ \App\Models\Service::getPaymentStatusOptions()[$service->payment_status] ?? $service->payment_status }}
            </span>
        </div>
    </div>

    {{-- Resultado --}}
    @if($service->result_summary || $service->result_notes)
        <div class="border-t pt-4">
            <h4 class="font-medium mb-2">Resultado</h4>
            @if($service->result_summary)
                <p class="text-gray-700 dark:text-gray-300">{{ $service->result_summary }}</p>
            @endif
            @if($service->result_notes)
                <p class="text-gray-600 mt-2">{{ $service->result_notes }}</p>
            @endif
        </div>
    @endif

    {{-- Descrição --}}
    @if($service->description)
        <div class="border-t pt-4">
            <h4 class="font-medium mb-2">Descrição</h4>
            <p class="text-gray-600 whitespace-pre-line">{{ $service->description }}</p>
        </div>
    @endif
</div>
