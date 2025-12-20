@extends('reports.layout')

@section('title', 'Relatório de Serviços')

@section('content')
    {{-- Resumo --}}
    <div class="summary-box">
        <h3>📊 Resumo do Período</h3>
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; text-align: center; width: 25%;">
                    <div class="summary-value">{{ $summary['total'] }}</div>
                    <div class="summary-label">Total de Serviços</div>
                </td>
                <td style="border: none; text-align: center; width: 25%;">
                    <div class="summary-value success">R$ {{ number_format($summary['total_value'], 2, ',', '.') }}</div>
                    <div class="summary-label">Valor Total</div>
                </td>
                <td style="border: none; text-align: center; width: 25%;">
                    <div class="summary-value">{{ $summary['by_status']['completed'] ?? 0 }}</div>
                    <div class="summary-label">Concluídos</div>
                </td>
                <td style="border: none; text-align: center; width: 25%;">
                    <div class="summary-value warning">{{ $summary['by_status']['pending'] ?? 0 }}</div>
                    <div class="summary-label">Pendentes</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Por Tipo de Serviço --}}
    @if(count($summary['by_type']) > 0)
    <div class="summary-box">
        <h3>📋 Por Tipo de Serviço</h3>
        <table style="width: 100%; border: none;">
            <tr>
                @foreach($summary['by_type'] as $type => $count)
                <td style="border: none; text-align: center;">
                    <div class="summary-value">{{ $count }}</div>
                    <div class="summary-label">{{ $type ?: 'Sem tipo' }}</div>
                </td>
                @endforeach
            </tr>
        </table>
    </div>
    @endif

    {{-- Lista de Serviços --}}
    <h3 class="section-title">📋 Lista de Serviços</h3>
    
    @if($services->count() > 0)
    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Código</th>
                <th style="width: 15%;">Data</th>
                <th style="width: 18%;">Cliente</th>
                <th style="width: 15%;">Tipo</th>
                <th style="width: 20%;">Local</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 10%;" class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($services as $service)
            <tr>
                <td><strong>{{ $service->code }}</strong></td>
                <td>{{ $service->scheduled_datetime->format('d/m/Y') }}</td>
                <td>{{ Str::limit($service->client->name ?? 'N/A', 20) }}</td>
                <td>{{ $service->serviceType->name ?? 'N/A' }}</td>
                <td>{{ Str::limit($service->location ?? 'N/A', 25) }}</td>
                <td>
                    <span class="badge badge-{{ $service->status }}">
                        {{ match($service->status) {
                            'pending' => 'Pendente',
                            'in_progress' => 'Em Andamento',
                            'completed' => 'Concluído',
                            'cancelled' => 'Cancelado',
                            default => $service->status
                        } }}
                    </span>
                </td>
                <td class="text-right money">R$ {{ number_format($service->value ?? 0, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #1e40af; color: white;">
                <td colspan="6"><strong>TOTAL</strong></td>
                <td class="text-right money"><strong>R$ {{ number_format($summary['total_value'], 2, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>
    @else
    <div class="no-data">
        Nenhum serviço encontrado no período selecionado.
    </div>
    @endif
@endsection
