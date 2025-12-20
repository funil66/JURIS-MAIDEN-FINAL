@extends('reports.layout')

@section('title', 'Relatório Geral')

@section('content')
    {{-- Resumo Executivo --}}
    <div class="summary-box">
        <h3>📊 Resumo Executivo do Período</h3>
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; text-align: center; width: 25%;">
                    <div class="summary-value">{{ $summary['clients']['active'] }}</div>
                    <div class="summary-label">👥 Clientes Ativos</div>
                </td>
                <td style="border: none; text-align: center; width: 25%;">
                    <div class="summary-value">{{ $summary['services']['total'] }}</div>
                    <div class="summary-label">📋 Serviços Realizados</div>
                </td>
                <td style="border: none; text-align: center; width: 25%;">
                    <div class="summary-value success">R$ {{ number_format($summary['financial']['income'], 2, ',', '.') }}</div>
                    <div class="summary-label">💵 Receitas</div>
                </td>
                <td style="border: none; text-align: center; width: 25%;">
                    <div class="summary-value {{ $summary['financial']['balance'] >= 0 ? 'success' : 'danger' }}">
                        R$ {{ number_format($summary['financial']['balance'], 2, ',', '.') }}
                    </div>
                    <div class="summary-label">📈 Saldo</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- SEÇÃO: SERVIÇOS --}}
    <h3 class="section-title">📋 Visão Geral dos Serviços</h3>
    
    <div class="summary-box">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; text-align: center; width: 20%;">
                    <div class="summary-value">{{ $summary['services']['total'] }}</div>
                    <div class="summary-label">Total</div>
                </td>
                <td style="border: none; text-align: center; width: 20%;">
                    <div class="summary-value warning">{{ $summary['services']['by_status']['pending'] ?? 0 }}</div>
                    <div class="summary-label">Pendentes</div>
                </td>
                <td style="border: none; text-align: center; width: 20%;">
                    <div class="summary-value" style="color: #3b82f6;">{{ $summary['services']['by_status']['in_progress'] ?? 0 }}</div>
                    <div class="summary-label">Em Andamento</div>
                </td>
                <td style="border: none; text-align: center; width: 20%;">
                    <div class="summary-value success">{{ $summary['services']['by_status']['completed'] ?? 0 }}</div>
                    <div class="summary-label">Concluídos</div>
                </td>
                <td style="border: none; text-align: center; width: 20%;">
                    <div class="summary-value danger">{{ $summary['services']['by_status']['cancelled'] ?? 0 }}</div>
                    <div class="summary-label">Cancelados</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Últimos 10 Serviços --}}
    @if($services->count() > 0)
    <h4 style="margin: 15px 0 10px; font-size: 11px; color: #64748b;">Últimos Serviços (até 10)</h4>
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Data</th>
                <th>Cliente</th>
                <th>Tipo</th>
                <th>Status</th>
                <th class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($services->take(10) as $service)
            <tr>
                <td><strong>{{ $service->code }}</strong></td>
                <td>{{ $service->scheduled_datetime->format('d/m/Y') }}</td>
                <td>{{ Str::limit($service->client->name ?? 'N/A', 20) }}</td>
                <td>{{ $service->serviceType->name ?? 'N/A' }}</td>
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
    </table>
    @endif

    {{-- SEÇÃO: FINANCEIRO --}}
    <h3 class="section-title">💰 Visão Geral Financeira</h3>
    
    <div class="summary-box">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; text-align: center; width: 33%;">
                    <div class="summary-value success">R$ {{ number_format($summary['financial']['income'], 2, ',', '.') }}</div>
                    <div class="summary-label">Receitas</div>
                </td>
                <td style="border: none; text-align: center; width: 33%;">
                    <div class="summary-value danger">R$ {{ number_format($summary['financial']['expense'], 2, ',', '.') }}</div>
                    <div class="summary-label">Despesas</div>
                </td>
                <td style="border: none; text-align: center; width: 33%;">
                    <div class="summary-value {{ $summary['financial']['balance'] >= 0 ? 'success' : 'danger' }}">
                        R$ {{ number_format($summary['financial']['balance'], 2, ',', '.') }}
                    </div>
                    <div class="summary-label">Saldo</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Últimas 10 Transações --}}
    @if($transactions->count() > 0)
    <h4 style="margin: 15px 0 10px; font-size: 11px; color: #64748b;">Últimas Transações (até 10)</h4>
    <table>
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Vencimento</th>
                <th>Descrição</th>
                <th>Status</th>
                <th class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions->take(10) as $transaction)
            <tr>
                <td>
                    <span class="badge badge-{{ $transaction->type }}">
                        {{ $transaction->type === 'income' ? '📈 Receita' : '📉 Despesa' }}
                    </span>
                </td>
                <td>{{ $transaction->due_date->format('d/m/Y') }}</td>
                <td>{{ Str::limit($transaction->description, 30) }}</td>
                <td>
                    <span class="badge badge-{{ $transaction->status }}">
                        {{ match($transaction->status) {
                            'pending' => 'Pendente',
                            'paid' => 'Pago',
                            'overdue' => 'Atrasado',
                            'cancelled' => 'Cancelado',
                            default => $transaction->status
                        } }}
                    </span>
                </td>
                <td class="text-right money" style="color: {{ $transaction->type === 'income' ? '#16a34a' : '#dc2626' }}; font-weight: bold;">
                    R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- SEÇÃO: TOP CLIENTES --}}
    <h3 class="section-title">👥 Top 5 Clientes por Faturamento</h3>
    
    @if($clients->count() > 0)
    <table>
        <thead>
            <tr>
                <th style="width: 8%;">#</th>
                <th style="width: 40%;">Cliente</th>
                <th style="width: 20%;" class="text-center">Serviços</th>
                <th style="width: 32%;" class="text-right">Valor Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($clients->take(5) as $index => $client)
            <tr>
                <td>
                    <span style="font-size: 14px;">
                        {{ match($index) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => ($index + 1) . 'º' } }}
                    </span>
                </td>
                <td><strong>{{ $client->name }}</strong></td>
                <td class="text-center">{{ $client->services_count }}</td>
                <td class="text-right money" style="font-weight: bold;">
                    R$ {{ number_format($client->services_sum_value ?? 0, 2, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="no-data">
        Nenhum cliente com serviços no período.
    </div>
    @endif

    {{-- Resultado Final --}}
    <div class="summary-box" style="margin-top: 30px; background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); color: white;">
        <h3 style="color: white; border-bottom-color: rgba(255,255,255,0.3);">📈 Resultado Consolidado</h3>
        <table style="width: 100%; border: none; color: white;">
            <tr>
                <td style="border: none; text-align: center; width: 25%;">
                    <div style="font-size: 18px; font-weight: bold;">{{ $summary['clients']['active'] }}</div>
                    <div style="font-size: 10px; opacity: 0.8;">Clientes</div>
                </td>
                <td style="border: none; text-align: center; width: 25%;">
                    <div style="font-size: 18px; font-weight: bold;">{{ $summary['services']['total'] }}</div>
                    <div style="font-size: 10px; opacity: 0.8;">Serviços</div>
                </td>
                <td style="border: none; text-align: center; width: 25%;">
                    <div style="font-size: 18px; font-weight: bold;">R$ {{ number_format($summary['services']['value'], 2, ',', '.') }}</div>
                    <div style="font-size: 10px; opacity: 0.8;">Faturamento</div>
                </td>
                <td style="border: none; text-align: center; width: 25%;">
                    <div style="font-size: 18px; font-weight: bold;">R$ {{ number_format($summary['financial']['balance'], 2, ',', '.') }}</div>
                    <div style="font-size: 10px; opacity: 0.8;">Saldo Final</div>
                </td>
            </tr>
        </table>
    </div>
@endsection
