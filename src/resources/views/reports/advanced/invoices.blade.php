<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Faturas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; line-height: 1.4; color: #1f2937; }
        
        .header { text-align: center; padding: 15px 0; border-bottom: 3px solid #10b981; margin-bottom: 20px; }
        .header h1 { font-size: 22px; color: #10b981; margin-bottom: 5px; }
        .header .subtitle { font-size: 11px; color: #6b7280; }
        .header .period { font-size: 10px; color: #374151; margin-top: 5px; }
        
        .summary-grid { display: table; width: 100%; margin-bottom: 20px; }
        .summary-card { display: table-cell; width: 16.66%; padding: 12px; text-align: center; background: #ecfdf5; border: 1px solid #a7f3d0; }
        .summary-card .label { font-size: 8px; color: #6b7280; text-transform: uppercase; margin-bottom: 3px; }
        .summary-card .value { font-size: 16px; font-weight: bold; color: #059669; }
        .summary-card .value.danger { color: #dc2626; }
        .summary-card .value.warning { color: #f59e0b; }
        .summary-card .value.info { color: #3b82f6; }
        
        .section-title { font-size: 12px; font-weight: bold; color: #047857; margin: 15px 0 10px; padding-bottom: 5px; border-bottom: 2px solid #10b981; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table thead { background: #059669; }
        .data-table th { color: #fff; padding: 8px 5px; text-align: left; font-size: 8px; font-weight: bold; }
        .data-table td { padding: 6px 5px; border-bottom: 1px solid #e5e7eb; font-size: 8px; }
        .data-table tbody tr:nth-child(even) { background: #f0fdf4; }
        .data-table tbody tr.overdue { background: #fef2f2; }
        .data-table tbody tr.paid { background: #ecfdf5; }
        
        .badge { display: inline-block; padding: 2px 5px; border-radius: 3px; font-size: 7px; font-weight: bold; }
        .badge-draft { background: #e5e7eb; color: #6b7280; }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-paid { background: #d1fae5; color: #059669; }
        .badge-partial { background: #dbeafe; color: #2563eb; }
        .badge-overdue { background: #fee2e2; color: #dc2626; }
        .badge-cancelled { background: #e5e7eb; color: #6b7280; text-decoration: line-through; }
        
        .text-right { text-align: right; }
        .money { font-family: monospace; }
        .money-success { color: #059669; }
        .money-danger { color: #dc2626; }
        
        .chart-wrapper { margin: 15px 0; }
        .pie-chart { display: table; width: 100%; }
        .pie-segment { display: table-cell; padding: 10px; text-align: center; }
        .pie-value { font-size: 16px; font-weight: bold; }
        .pie-label { font-size: 8px; color: #6b7280; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 8px 20px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #9ca3af; }
        .footer .left { float: left; }
        .footer .right { float: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📄 Relatório de Faturas</h1>
        <div class="subtitle">LogísticaJus - Sistema de Gestão Jurídica</div>
        <div class="period">
            @if(!empty($summary['period']))
                Período: {{ $summary['period'] }} |
            @endif
            Gerado em: {{ $summary['generated_at'] ?? now()->format('d/m/Y H:i') }}
        </div>
    </div>

    {{-- Summary --}}
    @if($include_summary ?? true)
        @php
            $total = $summary['total'] ?? 0;
            $paid = $summary['paid'] ?? 0;
            $pending = $summary['pending'] ?? 0;
            $overdue = $summary['overdue'] ?? 0;
            $byStatus = $summary['by_status'] ?? collect([]);
            $paidPercent = $total > 0 ? ($paid / $total) * 100 : 0;
        @endphp

        <table class="summary-grid">
            <tr>
                <td class="summary-card">
                    <div class="label">Total Faturado</div>
                    <div class="value">R$ {{ number_format($total, 2, ',', '.') }}</div>
                </td>
                <td class="summary-card">
                    <div class="label">Recebido</div>
                    <div class="value">R$ {{ number_format($paid, 2, ',', '.') }}</div>
                </td>
                <td class="summary-card">
                    <div class="label">Pendente</div>
                    <div class="value warning">R$ {{ number_format($pending, 2, ',', '.') }}</div>
                </td>
                <td class="summary-card">
                    <div class="label">Vencido</div>
                    <div class="value danger">R$ {{ number_format($overdue, 2, ',', '.') }}</div>
                </td>
                <td class="summary-card">
                    <div class="label">% Recebido</div>
                    <div class="value {{ $paidPercent >= 80 ? '' : ($paidPercent >= 50 ? 'warning' : 'danger') }}">
                        {{ number_format($paidPercent, 1) }}%
                    </div>
                </td>
                <td class="summary-card">
                    <div class="label">Qtd. Faturas</div>
                    <div class="value info">{{ $summary['total_records'] ?? $data->count() }}</div>
                </td>
            </tr>
        </table>

        {{-- Status Distribution --}}
        @if($include_charts ?? true)
            <div class="chart-wrapper">
                <div style="font-size: 10px; font-weight: bold; margin-bottom: 10px;">Distribuição por Status</div>
                <table class="pie-chart" style="background: #f9fafb; border-radius: 5px;">
                    <tr>
                        @foreach(['draft' => 'Rascunho', 'pending' => 'Pendente', 'paid' => 'Paga', 'partial' => 'Parcial', 'overdue' => 'Vencida', 'cancelled' => 'Cancelada'] as $status => $label)
                            @php $count = $byStatus[$status] ?? 0; @endphp
                            @if($count > 0)
                                <td class="pie-segment">
                                    <div class="pie-value" style="color: {{ match($status) {
                                        'paid' => '#059669',
                                        'pending' => '#f59e0b',
                                        'overdue' => '#dc2626',
                                        'partial' => '#3b82f6',
                                        default => '#6b7280',
                                    } }};">{{ $count }}</div>
                                    <div class="pie-label">{{ $label }}</div>
                                </td>
                            @endif
                        @endforeach
                    </tr>
                </table>
            </div>
        @endif
    @endif

    {{-- Data Table --}}
    @if(($include_details ?? true) && $data->count() > 0)
        <div class="section-title">📋 Lista de Faturas ({{ $data->count() }})</div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 55px;">Código</th>
                    <th style="width: 60px;">Número</th>
                    <th>Cliente</th>
                    <th style="width: 55px;">Emissão</th>
                    <th style="width: 55px;">Vencimento</th>
                    <th style="width: 50px;">Status</th>
                    <th style="width: 70px;" class="text-right">Subtotal</th>
                    <th style="width: 60px;" class="text-right">Desconto</th>
                    <th style="width: 75px;" class="text-right">Total</th>
                    <th style="width: 70px;" class="text-right">Pago</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $invoice)
                    @php
                        $isOverdue = $invoice->status === 'overdue' || 
                            ($invoice->status === 'pending' && $invoice->due_date && $invoice->due_date->isPast());
                        $isPaid = $invoice->status === 'paid';
                        $rowClass = $isPaid ? 'paid' : ($isOverdue ? 'overdue' : '');
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td><strong>{{ $invoice->uid }}</strong></td>
                        <td>{{ $invoice->invoice_number ?? '-' }}</td>
                        <td>{{ Str::limit($invoice->client?->name ?? '-', 30) }}</td>
                        <td>{{ $invoice->issue_date?->format('d/m/Y') ?? '-' }}</td>
                        <td><strong>{{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</strong></td>
                        <td>
                            @php
                                $statusBadge = match($invoice->status) {
                                    'draft' => 'badge-draft',
                                    'pending' => 'badge-pending',
                                    'paid' => 'badge-paid',
                                    'partial' => 'badge-partial',
                                    'overdue' => 'badge-overdue',
                                    'cancelled' => 'badge-cancelled',
                                    default => 'badge-pending',
                                };
                                $statusLabel = match($invoice->status) {
                                    'draft' => 'Rasc.',
                                    'pending' => 'Pend.',
                                    'paid' => 'Paga',
                                    'partial' => 'Parc.',
                                    'overdue' => 'Venc.',
                                    'cancelled' => 'Canc.',
                                    default => $invoice->status,
                                };
                            @endphp
                            <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                        </td>
                        <td class="text-right money">R$ {{ number_format($invoice->subtotal ?? 0, 2, ',', '.') }}</td>
                        <td class="text-right money money-danger">
                            @if(($invoice->discount ?? 0) > 0)
                                -R$ {{ number_format($invoice->discount, 2, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-right money"><strong>R$ {{ number_format($invoice->total ?? 0, 2, ',', '.') }}</strong></td>
                        <td class="text-right money money-success">
                            @if(($invoice->paid_amount ?? 0) > 0)
                                R$ {{ number_format($invoice->paid_amount, 2, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background: #ecfdf5; font-weight: bold;">
                    <td colspan="6" class="text-right">TOTAIS:</td>
                    <td class="text-right money">R$ {{ number_format($data->sum('subtotal'), 2, ',', '.') }}</td>
                    <td class="text-right money money-danger">-R$ {{ number_format($data->sum('discount'), 2, ',', '.') }}</td>
                    <td class="text-right money">R$ {{ number_format($data->sum('total'), 2, ',', '.') }}</td>
                    <td class="text-right money money-success">R$ {{ number_format($data->sum('paid_amount'), 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="footer">
        <span class="left">LogísticaJus © {{ date('Y') }} - Relatório de Faturas</span>
        <span class="right">Página 1</span>
    </div>
</body>
</html>
