<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório Financeiro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; line-height: 1.4; color: #1f2937; }
        
        .header { text-align: center; padding: 15px 0; border-bottom: 3px solid #059669; margin-bottom: 20px; }
        .header h1 { font-size: 22px; color: #059669; margin-bottom: 5px; }
        .header .subtitle { font-size: 11px; color: #6b7280; }
        .header .period { font-size: 10px; color: #374151; margin-top: 5px; }
        
        .summary-grid { display: table; width: 100%; margin-bottom: 20px; }
        .summary-card { display: table-cell; width: 20%; padding: 12px; text-align: center; border: 1px solid #d1d5db; }
        .summary-card.income { background: #d1fae5; border-color: #6ee7b7; }
        .summary-card.expense { background: #fee2e2; border-color: #fca5a5; }
        .summary-card.balance { background: #dbeafe; border-color: #93c5fd; }
        .summary-card .label { font-size: 9px; color: #6b7280; text-transform: uppercase; margin-bottom: 3px; }
        .summary-card .value { font-size: 18px; font-weight: bold; }
        .summary-card .value.success { color: #059669; }
        .summary-card .value.danger { color: #dc2626; }
        .summary-card .value.info { color: #2563eb; }
        
        .section-title { font-size: 12px; font-weight: bold; color: #065f46; margin: 15px 0 10px; padding-bottom: 5px; border-bottom: 2px solid #059669; }
        
        .two-columns { display: table; width: 100%; }
        .column { display: table-cell; width: 50%; padding: 0 10px; vertical-align: top; }
        .column:first-child { padding-left: 0; }
        .column:last-child { padding-right: 0; }
        
        .mini-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        .mini-table td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; font-size: 9px; }
        .mini-table td:last-child { text-align: right; font-weight: bold; }
        .mini-table tr:last-child { background: #f3f4f6; font-weight: bold; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table thead { background: #059669; }
        .data-table th { color: #fff; padding: 8px 5px; text-align: left; font-size: 8px; font-weight: bold; }
        .data-table td { padding: 6px 5px; border-bottom: 1px solid #e5e7eb; font-size: 8px; }
        .data-table tbody tr:nth-child(even) { background: #f0fdf4; }
        .data-table tbody tr.income-row { }
        .data-table tbody tr.expense-row { background: #fef2f2; }
        
        .badge { display: inline-block; padding: 2px 5px; border-radius: 3px; font-size: 7px; font-weight: bold; }
        .badge-income { background: #d1fae5; color: #059669; }
        .badge-expense { background: #fee2e2; color: #dc2626; }
        .badge-paid { background: #d1fae5; color: #059669; }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-overdue { background: #fee2e2; color: #dc2626; }
        
        .text-right { text-align: right; }
        .money { font-family: monospace; }
        .money-income { color: #059669; }
        .money-expense { color: #dc2626; }
        
        .chart-bar { height: 25px; background: #e5e7eb; border-radius: 4px; margin: 8px 0; position: relative; overflow: hidden; }
        .chart-bar-income { background: #22c55e; height: 100%; float: left; }
        .chart-bar-expense { background: #ef4444; height: 100%; float: left; }
        .chart-label { position: absolute; top: 5px; font-size: 9px; font-weight: bold; color: #fff; }
        .chart-label.left { left: 10px; }
        .chart-label.right { right: 10px; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 8px 20px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #9ca3af; }
        .footer .left { float: left; }
        .footer .right { float: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>💰 Relatório Financeiro</h1>
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
            $income = $summary['total_income'] ?? 0;
            $expense = $summary['total_expense'] ?? 0;
            $balance = $summary['balance'] ?? ($income - $expense);
            $paid = $summary['paid'] ?? 0;
            $pending = $summary['pending'] ?? 0;
            $total = max(1, $income + $expense);
        @endphp

        <table class="summary-grid">
            <tr>
                <td class="summary-card income">
                    <div class="label">Total Receitas</div>
                    <div class="value success">R$ {{ number_format($income, 2, ',', '.') }}</div>
                </td>
                <td class="summary-card expense">
                    <div class="label">Total Despesas</div>
                    <div class="value danger">R$ {{ number_format($expense, 2, ',', '.') }}</div>
                </td>
                <td class="summary-card balance">
                    <div class="label">Saldo</div>
                    <div class="value {{ $balance >= 0 ? 'success' : 'danger' }}">R$ {{ number_format($balance, 2, ',', '.') }}</div>
                </td>
                <td class="summary-card">
                    <div class="label">Recebido</div>
                    <div class="value success">R$ {{ number_format($paid, 2, ',', '.') }}</div>
                </td>
                <td class="summary-card">
                    <div class="label">Pendente</div>
                    <div class="value info">R$ {{ number_format($pending, 2, ',', '.') }}</div>
                </td>
            </tr>
        </table>

        {{-- Visual Chart --}}
        @if($include_charts ?? true)
            <div style="margin: 15px 0;">
                <div style="font-size: 10px; font-weight: bold; margin-bottom: 5px;">Proporção Receitas x Despesas</div>
                <div class="chart-bar">
                    <div class="chart-bar-income" style="width: {{ ($income / $total) * 100 }}%;"></div>
                    <div class="chart-bar-expense" style="width: {{ ($expense / $total) * 100 }}%;"></div>
                    @if($income > 0)
                        <span class="chart-label left">Receitas: {{ number_format(($income / $total) * 100, 0) }}%</span>
                    @endif
                    @if($expense > 0)
                        <span class="chart-label right">Despesas: {{ number_format(($expense / $total) * 100, 0) }}%</span>
                    @endif
                </div>
            </div>

            {{-- Breakdown by Status --}}
            @if(isset($summary['by_status']))
                <div class="two-columns">
                    <div class="column">
                        <div style="font-size: 10px; font-weight: bold; margin-bottom: 5px;">📊 Por Status</div>
                        <table class="mini-table">
                            @foreach($summary['by_status'] as $status => $amount)
                                <tr>
                                    <td>
                                        @php
                                            $statusLabel = match($status) {
                                                'paid' => '✅ Pago',
                                                'pending' => '⏳ Pendente',
                                                'overdue' => '⚠️ Vencido',
                                                'cancelled' => '❌ Cancelado',
                                                default => ucfirst($status),
                                            };
                                        @endphp
                                        {{ $statusLabel }}
                                    </td>
                                    <td class="money">R$ {{ number_format($amount, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                    <div class="column">
                        <div style="font-size: 10px; font-weight: bold; margin-bottom: 5px;">📈 Resumo</div>
                        <table class="mini-table">
                            <tr>
                                <td>Total de Transações</td>
                                <td>{{ $summary['total_records'] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td>Margem</td>
                                <td class="{{ $balance >= 0 ? 'money-income' : 'money-expense' }}">
                                    @if($income > 0)
                                        {{ number_format(($balance / $income) * 100, 1) }}%
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>Taxa de Recebimento</td>
                                <td>
                                    @if($income > 0)
                                        {{ number_format(($paid / $income) * 100, 1) }}%
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            @endif
        @endif
    @endif

    {{-- Data Table --}}
    @if(($include_details ?? true) && $data->count() > 0)
        <div class="section-title">📋 Lista de Transações ({{ $data->count() }})</div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 55px;">Código</th>
                    <th style="width: 50px;">Tipo</th>
                    <th>Descrição / Cliente</th>
                    <th style="width: 60px;">Vencimento</th>
                    <th style="width: 60px;">Pagamento</th>
                    <th style="width: 50px;">Status</th>
                    <th style="width: 75px;" class="text-right">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $transaction)
                    <tr class="{{ $transaction->type === 'income' ? 'income-row' : 'expense-row' }}">
                        <td><strong>{{ $transaction->uid }}</strong></td>
                        <td>
                            <span class="badge {{ $transaction->type === 'income' ? 'badge-income' : 'badge-expense' }}">
                                {{ $transaction->type === 'income' ? '↑ Receita' : '↓ Despesa' }}
                            </span>
                        </td>
                        <td>
                            {{ Str::limit($transaction->description ?? 'Sem descrição', 40) }}
                            @if($transaction->client)
                                <br>
                                <span style="color: #6b7280; font-size: 7px;">{{ $transaction->client->name }}</span>
                            @endif
                        </td>
                        <td>{{ $transaction->due_date?->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ $transaction->payment_date?->format('d/m/Y') ?? '-' }}</td>
                        <td>
                            @php
                                $statusBadge = match($transaction->status) {
                                    'paid' => 'badge-paid',
                                    'pending' => 'badge-pending',
                                    'overdue' => 'badge-overdue',
                                    default => 'badge-pending',
                                };
                                $statusLabel = match($transaction->status) {
                                    'paid' => 'Pago',
                                    'pending' => 'Pend.',
                                    'overdue' => 'Vencido',
                                    'cancelled' => 'Cancel.',
                                    default => $transaction->status,
                                };
                            @endphp
                            <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                        </td>
                        <td class="text-right money {{ $transaction->type === 'income' ? 'money-income' : 'money-expense' }}">
                            {{ $transaction->type === 'expense' ? '-' : '' }}R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background: #f3f4f6; font-weight: bold;">
                    <td colspan="6" class="text-right">TOTAL:</td>
                    <td class="text-right money {{ $balance >= 0 ? 'money-income' : 'money-expense' }}">
                        R$ {{ number_format($balance, 2, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="footer">
        <span class="left">LogísticaJus © {{ date('Y') }} - Relatório Financeiro</span>
        <span class="right">Página 1</span>
    </div>
</body>
</html>
