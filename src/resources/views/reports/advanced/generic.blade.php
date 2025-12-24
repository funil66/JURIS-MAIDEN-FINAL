<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de {{ $typeName }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #1f2937;
            background: #fff;
        }
        
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 3px solid #4f46e5;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 24px;
            color: #4f46e5;
            margin-bottom: 5px;
        }
        
        .header .subtitle {
            font-size: 12px;
            color: #6b7280;
        }
        
        .header .period {
            font-size: 11px;
            color: #374151;
            margin-top: 5px;
        }
        
        .summary-section {
            margin-bottom: 25px;
        }
        
        .summary-title {
            font-size: 14px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .summary-grid {
            display: table;
            width: 100%;
        }
        
        .summary-row {
            display: table-row;
        }
        
        .summary-card {
            display: table-cell;
            width: 25%;
            padding: 10px;
            text-align: center;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
        }
        
        .summary-card .label {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        
        .summary-card .value {
            font-size: 16px;
            font-weight: bold;
            color: #4f46e5;
        }
        
        .summary-card .value.success { color: #059669; }
        .summary-card .value.danger { color: #dc2626; }
        .summary-card .value.warning { color: #d97706; }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .data-table thead {
            background: #4f46e5;
        }
        
        .data-table th {
            color: #fff;
            padding: 8px 6px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .data-table td {
            padding: 6px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
        }
        
        .data-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        
        .data-table tbody tr:hover {
            background: #f3f4f6;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
        }
        
        .badge-success { background: #d1fae5; color: #059669; }
        .badge-danger { background: #fee2e2; color: #dc2626; }
        .badge-warning { background: #fef3c7; color: #d97706; }
        .badge-info { background: #dbeafe; color: #2563eb; }
        .badge-gray { background: #f3f4f6; color: #4b5563; }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 10px 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 8px;
            color: #9ca3af;
        }
        
        .footer .left {
            float: left;
        }
        
        .footer .right {
            float: right;
        }
        
        .page-break {
            page-break-after: always;
        }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        .status-active { color: #059669; }
        .status-pending { color: #d97706; }
        .status-cancelled { color: #dc2626; }
        .status-completed { color: #2563eb; }
        
        .chart-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #9ca3af;
        }
        
        .breakdown-table {
            width: 50%;
            margin: 10px 0;
            font-size: 9px;
        }
        
        .breakdown-table td {
            padding: 4px 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .breakdown-table td:last-child {
            text-align: right;
            font-weight: bold;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <h1>Relatório de {{ $typeName }}</h1>
        <div class="subtitle">LogísticaJus - Sistema de Gestão Jurídica</div>
        <div class="period">
            @if(!empty($summary['period']))
                Período: {{ $summary['period'] }}
            @endif
            | Gerado em: {{ $summary['generated_at'] ?? now()->format('d/m/Y H:i') }}
        </div>
    </div>

    {{-- Summary Section --}}
    @if($include_summary ?? true)
        <div class="summary-section">
            <div class="summary-title">📊 Resumo Executivo</div>
            
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td class="summary-card">
                        <div class="label">Total de Registros</div>
                        <div class="value">{{ number_format($summary['total_records'] ?? 0) }}</div>
                    </td>
                    
                    @if(isset($summary['total_value']))
                        <td class="summary-card">
                            <div class="label">Valor Total</div>
                            <div class="value success">R$ {{ number_format($summary['total_value'], 2, ',', '.') }}</div>
                        </td>
                    @endif
                    
                    @if(isset($summary['total_income']))
                        <td class="summary-card">
                            <div class="label">Receitas</div>
                            <div class="value success">R$ {{ number_format($summary['total_income'], 2, ',', '.') }}</div>
                        </td>
                    @endif
                    
                    @if(isset($summary['total_expense']))
                        <td class="summary-card">
                            <div class="label">Despesas</div>
                            <div class="value danger">R$ {{ number_format($summary['total_expense'], 2, ',', '.') }}</div>
                        </td>
                    @endif
                    
                    @if(isset($summary['balance']))
                        <td class="summary-card">
                            <div class="label">Saldo</div>
                            <div class="value {{ $summary['balance'] >= 0 ? 'success' : 'danger' }}">
                                R$ {{ number_format($summary['balance'], 2, ',', '.') }}
                            </div>
                        </td>
                    @endif
                    
                    @if(isset($summary['total_hours']))
                        <td class="summary-card">
                            <div class="label">Total Horas</div>
                            <div class="value">{{ number_format($summary['total_hours'], 1, ',', '.') }}h</div>
                        </td>
                    @endif
                    
                    @if(isset($summary['billable_hours']))
                        <td class="summary-card">
                            <div class="label">Horas Faturáveis</div>
                            <div class="value success">{{ number_format($summary['billable_hours'], 1, ',', '.') }}h</div>
                        </td>
                    @endif
                </tr>
            </table>
            
            {{-- Status Breakdown --}}
            @if(isset($summary['by_status']) && count($summary['by_status']) > 0)
                <div style="margin-top: 15px;">
                    <strong style="font-size: 10px;">Por Status:</strong>
                    <table class="breakdown-table">
                        @foreach($summary['by_status'] as $status => $count)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $status)) }}</td>
                                <td>{{ $count }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif
            
            @if(isset($summary['by_type']) && count($summary['by_type']) > 0)
                <div style="margin-top: 15px; display: inline-block; vertical-align: top; margin-left: 30px;">
                    <strong style="font-size: 10px;">Por Tipo:</strong>
                    <table class="breakdown-table">
                        @foreach($summary['by_type'] as $type => $count)
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $type)) }}</td>
                                <td>{{ $count }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif
        </div>
    @endif

    {{-- Data Table --}}
    @if(($include_details ?? true) && $data->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    @foreach($columns as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($data as $item)
                    <tr>
                        @foreach(array_keys($columns) as $key)
                            <td>
                                @php
                                    $parts = explode('.', $key);
                                    $value = $item;
                                    foreach($parts as $part) {
                                        $value = is_object($value) ? ($value->{$part} ?? null) : ($value[$part] ?? null);
                                    }
                                @endphp
                                
                                @if($value instanceof \Carbon\Carbon)
                                    {{ $value->format('d/m/Y') }}
                                @elseif(is_bool($value))
                                    {{ $value ? 'Sim' : 'Não' }}
                                @elseif(in_array($key, ['status', 'phase', 'priority', 'result']))
                                    @php
                                        $badgeClass = match($value) {
                                            'active', 'paid', 'completed', 'positive' => 'badge-success',
                                            'pending', 'in_progress', 'partial' => 'badge-warning',
                                            'cancelled', 'missed', 'overdue', 'negative' => 'badge-danger',
                                            'critical', 'high' => 'badge-danger',
                                            default => 'badge-gray',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $value)) }}</span>
                                @elseif(str_contains($key, 'value') || str_contains($key, 'cost') || str_contains($key, 'amount') || str_contains($key, 'total') || str_contains($key, 'rate'))
                                    @if(is_numeric($value))
                                        R$ {{ number_format($value, 2, ',', '.') }}
                                    @else
                                        {{ $value ?? '-' }}
                                    @endif
                                @elseif(str_contains($key, 'minutes'))
                                    @if(is_numeric($value))
                                        {{ floor($value / 60) }}h {{ $value % 60 }}m
                                    @else
                                        {{ $value ?? '-' }}
                                    @endif
                                @else
                                    {{ $value ?? '-' }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @elseif($data->count() === 0)
        <div class="no-data">
            <p>Nenhum registro encontrado para os filtros selecionados.</p>
        </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <span class="left">LogísticaJus © {{ date('Y') }}</span>
        <span class="right">Página 1</span>
    </div>
</body>
</html>
