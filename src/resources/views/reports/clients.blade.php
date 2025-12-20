@extends('reports.layout')

@section('title', 'Relatório de Clientes')

@section('content')
    {{-- Resumo --}}
    <div class="summary-box">
        <h3>📊 Resumo do Período</h3>
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; text-align: center; width: 33%;">
                    <div class="summary-value">{{ $summary['total_clients'] }}</div>
                    <div class="summary-label">Clientes Ativos</div>
                </td>
                <td style="border: none; text-align: center; width: 33%;">
                    <div class="summary-value">{{ $summary['total_services'] }}</div>
                    <div class="summary-label">Total de Serviços</div>
                </td>
                <td style="border: none; text-align: center; width: 33%;">
                    <div class="summary-value success">R$ {{ number_format($summary['total_value'], 2, ',', '.') }}</div>
                    <div class="summary-label">Valor Total</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Ranking de Clientes --}}
    <h3 class="section-title">👥 Ranking de Clientes por Faturamento</h3>
    
    @if($clients->count() > 0)
    <table>
        <thead>
            <tr>
                <th style="width: 8%;">#</th>
                <th style="width: 30%;">Cliente</th>
                <th style="width: 20%;">Documento</th>
                <th style="width: 12%;">Tipo</th>
                <th style="width: 15%;" class="text-center">Serviços</th>
                <th style="width: 15%;" class="text-right">Valor Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clients as $index => $client)
            <tr>
                <td>
                    @if($index < 3)
                        <span style="font-size: 16px;">
                            {{ match($index) { 0 => '🥇', 1 => '🥈', 2 => '🥉' } }}
                        </span>
                    @else
                        <strong>{{ $index + 1 }}º</strong>
                    @endif
                </td>
                <td><strong>{{ $client->name }}</strong></td>
                <td>{{ $client->document }}</td>
                <td>
                    <span class="badge {{ $client->type === 'PF' ? 'badge-income' : 'badge-pending' }}">
                        {{ $client->type === 'PF' ? 'Pessoa Física' : 'Pessoa Jurídica' }}
                    </span>
                </td>
                <td class="text-center">{{ $client->services_count }}</td>
                <td class="text-right money">R$ {{ number_format($client->services_sum_value ?? 0, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #1e40af; color: white;">
                <td colspan="4"><strong>TOTAL</strong></td>
                <td class="text-center"><strong>{{ $summary['total_services'] }}</strong></td>
                <td class="text-right money"><strong>R$ {{ number_format($summary['total_value'], 2, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>

    {{-- Gráfico de Barras Simples (Representação Textual) --}}
    <div class="summary-box" style="margin-top: 20px;">
        <h3>📈 Distribuição por Cliente (Top 10)</h3>
        @php
            $maxValue = $clients->max('services_sum_value') ?: 1;
            $topClients = $clients->take(10);
        @endphp
        @foreach($topClients as $client)
        <div style="margin: 8px 0;">
            <div style="display: table; width: 100%;">
                <div style="display: table-cell; width: 25%; font-size: 10px;">
                    {{ Str::limit($client->name, 25) }}
                </div>
                <div style="display: table-cell; width: 55%;">
                    <div style="background: #e2e8f0; border-radius: 4px; height: 16px; width: 100%;">
                        <div style="background: linear-gradient(90deg, #1e40af, #3b82f6); border-radius: 4px; height: 16px; width: {{ ($client->services_sum_value / $maxValue) * 100 }}%;"></div>
                    </div>
                </div>
                <div style="display: table-cell; width: 20%; text-align: right; font-size: 10px;">
                    R$ {{ number_format($client->services_sum_value ?? 0, 2, ',', '.') }}
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="no-data">
        Nenhum cliente com serviços encontrado no período selecionado.
    </div>
    @endif
@endsection
