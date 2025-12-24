<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Processos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; line-height: 1.4; color: #1f2937; }
        
        .header { text-align: center; padding: 15px 0; border-bottom: 3px solid #3b82f6; margin-bottom: 20px; }
        .header h1 { font-size: 22px; color: #3b82f6; margin-bottom: 5px; }
        .header .subtitle { font-size: 11px; color: #6b7280; }
        .header .period { font-size: 10px; color: #374151; margin-top: 5px; }
        
        .summary-grid { display: table; width: 100%; margin-bottom: 20px; }
        .summary-card { display: table-cell; width: 16.66%; padding: 10px; text-align: center; background: #eff6ff; border: 1px solid #bfdbfe; }
        .summary-card .label { font-size: 8px; color: #6b7280; text-transform: uppercase; }
        .summary-card .value { font-size: 16px; font-weight: bold; color: #1d4ed8; }
        .summary-card .value.success { color: #059669; }
        .summary-card .value.danger { color: #dc2626; }
        
        .section-title { font-size: 12px; font-weight: bold; color: #1e40af; margin: 15px 0 10px; padding-bottom: 5px; border-bottom: 2px solid #3b82f6; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table thead { background: #1d4ed8; }
        .data-table th { color: #fff; padding: 8px 5px; text-align: left; font-size: 8px; font-weight: bold; }
        .data-table td { padding: 6px 5px; border-bottom: 1px solid #e5e7eb; font-size: 8px; }
        .data-table tbody tr:nth-child(even) { background: #f8fafc; }
        
        .badge { display: inline-block; padding: 2px 5px; border-radius: 3px; font-size: 7px; font-weight: bold; }
        .badge-active { background: #d1fae5; color: #059669; }
        .badge-suspended { background: #fef3c7; color: #d97706; }
        .badge-archived { background: #e5e7eb; color: #6b7280; }
        .badge-closed-won { background: #dbeafe; color: #2563eb; }
        .badge-closed-lost { background: #fee2e2; color: #dc2626; }
        .badge-closed-settled { background: #f3e8ff; color: #7c3aed; }
        
        .phase-knowledge { background: #dbeafe; color: #1d4ed8; }
        .phase-execution { background: #fef3c7; color: #d97706; }
        .phase-appeal { background: #f3e8ff; color: #7c3aed; }
        .phase-precautionary { background: #d1fae5; color: #059669; }
        
        .charts-row { display: table; width: 100%; margin: 15px 0; }
        .chart-box { display: table-cell; width: 50%; padding: 10px; vertical-align: top; }
        .chart-title { font-size: 10px; font-weight: bold; margin-bottom: 8px; color: #374151; }
        .chart-bar { height: 20px; background: #e5e7eb; border-radius: 3px; margin: 3px 0; position: relative; }
        .chart-bar-fill { height: 100%; border-radius: 3px; }
        .chart-bar-label { position: absolute; left: 5px; top: 3px; font-size: 8px; color: #fff; font-weight: bold; }
        .chart-bar-value { position: absolute; right: 5px; top: 3px; font-size: 8px; color: #374151; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 8px 20px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #9ca3af; }
        .footer .left { float: left; }
        .footer .right { float: right; }
        
        .text-right { text-align: right; }
        .money { font-family: monospace; }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚖️ Relatório de Processos</h1>
        <div class="subtitle">LogísticaJus - Sistema de Gestão Jurídica</div>
        <div class="period">
            @if(!empty($summary['period']))
                Período: {{ $summary['period'] }} |
            @endif
            Gerado em: {{ $summary['generated_at'] ?? now()->format('d/m/Y H:i') }}
        </div>
    </div>

    {{-- Summary Cards --}}
    @if($include_summary ?? true)
        <table class="summary-grid">
            <tr>
                <td class="summary-card">
                    <div class="label">Total de Processos</div>
                    <div class="value">{{ number_format($summary['total_records'] ?? 0) }}</div>
                </td>
                <td class="summary-card">
                    <div class="label">Ativos</div>
                    <div class="value success">{{ number_format($summary['active_count'] ?? 0) }}</div>
                </td>
                <td class="summary-card">
                    <div class="label">Valor Total</div>
                    <div class="value">R$ {{ number_format($summary['total_value'] ?? 0, 2, ',', '.') }}</div>
                </td>
                @php
                    $byStatus = $summary['by_status'] ?? collect([]);
                    $won = $byStatus['closed_won'] ?? 0;
                    $lost = $byStatus['closed_lost'] ?? 0;
                    $settled = $byStatus['closed_settled'] ?? 0;
                @endphp
                <td class="summary-card">
                    <div class="label">Ganhos</div>
                    <div class="value success">{{ $won }}</div>
                </td>
                <td class="summary-card">
                    <div class="label">Perdidos</div>
                    <div class="value danger">{{ $lost }}</div>
                </td>
                <td class="summary-card">
                    <div class="label">Acordos</div>
                    <div class="value">{{ $settled }}</div>
                </td>
            </tr>
        </table>

        {{-- Charts --}}
        @if($include_charts ?? true)
            <div class="charts-row">
                <div class="chart-box">
                    <div class="chart-title">📊 Por Status</div>
                    @php
                        $statusColors = [
                            'active' => '#22c55e',
                            'suspended' => '#eab308',
                            'archived' => '#9ca3af',
                            'closed_won' => '#3b82f6',
                            'closed_lost' => '#ef4444',
                            'closed_settled' => '#8b5cf6',
                        ];
                        $statusLabels = [
                            'active' => 'Ativos',
                            'suspended' => 'Suspensos',
                            'archived' => 'Arquivados',
                            'closed_won' => 'Ganhos',
                            'closed_lost' => 'Perdidos',
                            'closed_settled' => 'Acordos',
                        ];
                        $maxStatus = max(1, $byStatus->max());
                    @endphp
                    @foreach($byStatus as $status => $count)
                        @if($count > 0)
                            <div class="chart-bar">
                                <div class="chart-bar-fill" style="width: {{ ($count / $maxStatus) * 100 }}%; background: {{ $statusColors[$status] ?? '#9ca3af' }};"></div>
                                <span class="chart-bar-label">{{ $statusLabels[$status] ?? $status }}</span>
                                <span class="chart-bar-value">{{ $count }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
                
                <div class="chart-box">
                    <div class="chart-title">📈 Por Fase</div>
                    @php
                        $byPhase = $summary['by_phase'] ?? collect([]);
                        $phaseColors = [
                            'knowledge' => '#3b82f6',
                            'execution' => '#eab308',
                            'appeal' => '#8b5cf6',
                            'precautionary' => '#22c55e',
                        ];
                        $phaseLabels = [
                            'knowledge' => 'Conhecimento',
                            'execution' => 'Execução',
                            'appeal' => 'Recursal',
                            'precautionary' => 'Cautelar',
                        ];
                        $maxPhase = max(1, $byPhase->max());
                    @endphp
                    @foreach($byPhase as $phase => $count)
                        @if($count > 0)
                            <div class="chart-bar">
                                <div class="chart-bar-fill" style="width: {{ ($count / $maxPhase) * 100 }}%; background: {{ $phaseColors[$phase] ?? '#9ca3af' }};"></div>
                                <span class="chart-bar-label">{{ $phaseLabels[$phase] ?? $phase }}</span>
                                <span class="chart-bar-value">{{ $count }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    {{-- Data Table --}}
    @if(($include_details ?? true) && $data->count() > 0)
        <div class="section-title">📋 Lista de Processos ({{ $data->count() }})</div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 60px;">Código</th>
                    <th style="width: 100px;">Nº CNJ</th>
                    <th>Título / Cliente</th>
                    <th style="width: 80px;">Tribunal</th>
                    <th style="width: 60px;">Status</th>
                    <th style="width: 60px;">Fase</th>
                    <th style="width: 80px;">Valor</th>
                    <th style="width: 60px;">Distribuição</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $process)
                    <tr>
                        <td><strong>{{ $process->uid }}</strong></td>
                        <td style="font-size: 7px;">{{ $process->cnj_number ?? '-' }}</td>
                        <td>
                            {{ Str::limit($process->title, 35) }}
                            <br>
                            <span style="color: #6b7280; font-size: 7px;">{{ $process->client?->name ?? '-' }}</span>
                        </td>
                        <td>{{ $process->court ?? '-' }}</td>
                        <td>
                            @php
                                $statusBadge = match($process->status) {
                                    'active' => 'badge-active',
                                    'suspended' => 'badge-suspended',
                                    'archived' => 'badge-archived',
                                    'closed_won' => 'badge-closed-won',
                                    'closed_lost' => 'badge-closed-lost',
                                    'closed_settled' => 'badge-closed-settled',
                                    default => 'badge-archived',
                                };
                                $statusLabel = match($process->status) {
                                    'active' => 'Ativo',
                                    'suspended' => 'Suspenso',
                                    'archived' => 'Arquivado',
                                    'closed_won' => 'Ganho',
                                    'closed_lost' => 'Perdido',
                                    'closed_settled' => 'Acordo',
                                    default => $process->status,
                                };
                            @endphp
                            <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                        </td>
                        <td>
                            @php
                                $phaseBadge = match($process->phase) {
                                    'knowledge' => 'phase-knowledge',
                                    'execution' => 'phase-execution',
                                    'appeal' => 'phase-appeal',
                                    'precautionary' => 'phase-precautionary',
                                    default => '',
                                };
                                $phaseLabel = match($process->phase) {
                                    'knowledge' => 'Conhec.',
                                    'execution' => 'Execução',
                                    'appeal' => 'Recursal',
                                    'precautionary' => 'Cautelar',
                                    default => $process->phase,
                                };
                            @endphp
                            <span class="badge {{ $phaseBadge }}">{{ $phaseLabel }}</span>
                        </td>
                        <td class="text-right money">
                            @if($process->case_value)
                                R$ {{ number_format($process->case_value, 2, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $process->distribution_date?->format('d/m/Y') ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        <span class="left">LogísticaJus © {{ date('Y') }} - Relatório de Processos</span>
        <span class="right">Página 1</span>
    </div>
</body>
</html>
