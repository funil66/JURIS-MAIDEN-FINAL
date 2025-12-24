<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Produtividade</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; line-height: 1.4; color: #1f2937; }
        
        .header { text-align: center; padding: 15px 0; border-bottom: 3px solid #8b5cf6; margin-bottom: 20px; }
        .header h1 { font-size: 22px; color: #8b5cf6; margin-bottom: 5px; }
        .header .subtitle { font-size: 11px; color: #6b7280; }
        .header .period { font-size: 10px; color: #374151; margin-top: 5px; }
        
        .summary-grid { display: table; width: 100%; margin-bottom: 20px; }
        .summary-card { display: table-cell; width: 25%; padding: 12px; text-align: center; background: #f5f3ff; border: 1px solid #ddd6fe; }
        .summary-card .label { font-size: 9px; color: #6b7280; text-transform: uppercase; margin-bottom: 3px; }
        .summary-card .value { font-size: 18px; font-weight: bold; color: #7c3aed; }
        .summary-card .value.success { color: #059669; }
        
        .section-title { font-size: 12px; font-weight: bold; color: #5b21b6; margin: 15px 0 10px; padding-bottom: 5px; border-bottom: 2px solid #8b5cf6; }
        
        .user-card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; margin-bottom: 10px; }
        .user-card-header { display: table; width: 100%; margin-bottom: 10px; }
        .user-info { display: table-cell; width: 60%; }
        .user-stats { display: table-cell; width: 40%; text-align: right; }
        .user-name { font-size: 12px; font-weight: bold; color: #374151; }
        .user-email { font-size: 8px; color: #9ca3af; }
        .stat-item { display: inline-block; margin-left: 15px; text-align: center; }
        .stat-value { font-size: 14px; font-weight: bold; }
        .stat-label { font-size: 7px; color: #6b7280; text-transform: uppercase; }
        
        .progress-bar { height: 8px; background: #e5e7eb; border-radius: 4px; margin: 5px 0; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 4px; }
        .progress-billable { background: #22c55e; }
        .progress-non-billable { background: #f59e0b; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table thead { background: #7c3aed; }
        .data-table th { color: #fff; padding: 8px 5px; text-align: left; font-size: 8px; font-weight: bold; }
        .data-table td { padding: 6px 5px; border-bottom: 1px solid #e5e7eb; font-size: 8px; }
        .data-table tbody tr:nth-child(even) { background: #f5f3ff; }
        
        .text-right { text-align: right; }
        .money { font-family: monospace; color: #059669; }
        
        .hours-bar { display: table; width: 100%; }
        .hours-segment { display: table-cell; height: 20px; text-align: center; font-size: 8px; font-weight: bold; color: #fff; }
        .hours-billable { background: #22c55e; }
        .hours-non-billable { background: #f59e0b; }
        
        .legend { margin-top: 10px; font-size: 8px; }
        .legend-item { display: inline-block; margin-right: 15px; }
        .legend-color { display: inline-block; width: 12px; height: 12px; border-radius: 2px; vertical-align: middle; margin-right: 4px; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 8px 20px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #9ca3af; }
        .footer .left { float: left; }
        .footer .right { float: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Relatório de Produtividade</h1>
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
            $totalHours = $summary['total_hours'] ?? 0;
            $billableHours = $summary['billable_hours'] ?? 0;
            $nonBillableHours = $totalHours - $billableHours;
            $totalBilled = $summary['total_billed'] ?? 0;
            $totalUsers = $summary['total_users'] ?? $data->count();
            $avgPerUser = $totalUsers > 0 ? $totalHours / $totalUsers : 0;
            $billablePercent = $totalHours > 0 ? ($billableHours / $totalHours) * 100 : 0;
        @endphp

        <table class="summary-grid">
            <tr>
                <td class="summary-card">
                    <div class="label">Total de Horas</div>
                    <div class="value">{{ number_format($totalHours, 1, ',', '.') }}h</div>
                </td>
                <td class="summary-card">
                    <div class="label">Horas Faturáveis</div>
                    <div class="value success">{{ number_format($billableHours, 1, ',', '.') }}h</div>
                </td>
                <td class="summary-card">
                    <div class="label">% Faturável</div>
                    <div class="value">{{ number_format($billablePercent, 1) }}%</div>
                </td>
                <td class="summary-card">
                    <div class="label">Total Faturado</div>
                    <div class="value success">R$ {{ number_format($totalBilled, 2, ',', '.') }}</div>
                </td>
            </tr>
        </table>

        @if($include_charts ?? true)
            {{-- Hours Distribution Bar --}}
            <div style="margin: 15px 0;">
                <div style="font-size: 10px; font-weight: bold; margin-bottom: 5px;">Distribuição de Horas</div>
                <div class="hours-bar" style="border-radius: 4px; overflow: hidden;">
                    @if($billableHours > 0)
                        <div class="hours-segment hours-billable" style="width: {{ $billablePercent }}%;">
                            {{ number_format($billableHours, 1) }}h
                        </div>
                    @endif
                    @if($nonBillableHours > 0)
                        <div class="hours-segment hours-non-billable" style="width: {{ 100 - $billablePercent }}%;">
                            {{ number_format($nonBillableHours, 1) }}h
                        </div>
                    @endif
                </div>
                <div class="legend">
                    <span class="legend-item">
                        <span class="legend-color" style="background: #22c55e;"></span>
                        Faturável ({{ number_format($billablePercent, 0) }}%)
                    </span>
                    <span class="legend-item">
                        <span class="legend-color" style="background: #f59e0b;"></span>
                        Não Faturável ({{ number_format(100 - $billablePercent, 0) }}%)
                    </span>
                </div>
            </div>

            {{-- By User Summary --}}
            @if(isset($summary['by_user']) && count($summary['by_user']) > 0)
                <div class="section-title">👥 Por Usuário</div>
                @foreach($summary['by_user'] as $userName => $hours)
                    @php $userPercent = $totalHours > 0 ? ($hours / $totalHours) * 100 : 0; @endphp
                    <div style="margin: 5px 0;">
                        <div style="display: table; width: 100%;">
                            <span style="display: table-cell; width: 150px;">{{ $userName }}</span>
                            <span style="display: table-cell;">
                                <div class="progress-bar">
                                    <div class="progress-fill progress-billable" style="width: {{ $userPercent }}%;"></div>
                                </div>
                            </span>
                            <span style="display: table-cell; width: 80px; text-align: right; font-weight: bold;">
                                {{ number_format($hours, 1) }}h ({{ number_format($userPercent, 0) }}%)
                            </span>
                        </div>
                    </div>
                @endforeach
            @endif
        @endif
    @endif

    {{-- User Details --}}
    @if(($include_details ?? true) && $data->count() > 0)
        <div class="section-title">📋 Detalhamento por Usuário ({{ $data->count() }})</div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th style="width: 70px;">Total Horas</th>
                    <th style="width: 70px;">Faturáveis</th>
                    <th style="width: 70px;">Não Fat.</th>
                    <th style="width: 60px;">% Fat.</th>
                    <th style="width: 80px;">Valor Fat.</th>
                    <th style="width: 60px;">Média/Dia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $user)
                    @php
                        $userTotalMinutes = $user->total_minutes ?? 0;
                        $userBillableMinutes = $user->billable_minutes ?? 0;
                        $userNonBillableMinutes = $userTotalMinutes - $userBillableMinutes;
                        $userTotalHours = $userTotalMinutes / 60;
                        $userBillableHours = $userBillableMinutes / 60;
                        $userNonBillableHours = $userNonBillableMinutes / 60;
                        $userBillablePercent = $userTotalMinutes > 0 ? ($userBillableMinutes / $userTotalMinutes) * 100 : 0;
                        $userBilledValue = $user->billed_value ?? 0;
                        
                        // Média por dia útil (considerando 22 dias úteis/mês)
                        $workDays = 22;
                        $avgPerDay = $userTotalHours / $workDays;
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $user->name }}</strong>
                            <br>
                            <span style="font-size: 7px; color: #9ca3af;">{{ $user->email }}</span>
                        </td>
                        <td class="text-right"><strong>{{ number_format($userTotalHours, 1, ',', '.') }}h</strong></td>
                        <td class="text-right" style="color: #059669;">{{ number_format($userBillableHours, 1, ',', '.') }}h</td>
                        <td class="text-right" style="color: #f59e0b;">{{ number_format($userNonBillableHours, 1, ',', '.') }}h</td>
                        <td class="text-right">
                            <span style="color: {{ $userBillablePercent >= 70 ? '#059669' : ($userBillablePercent >= 50 ? '#f59e0b' : '#dc2626') }};">
                                {{ number_format($userBillablePercent, 0) }}%
                            </span>
                        </td>
                        <td class="text-right money">R$ {{ number_format($userBilledValue, 2, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($avgPerDay, 1, ',', '.') }}h</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background: #f3f4f6; font-weight: bold;">
                    <td>TOTAL</td>
                    <td class="text-right">{{ number_format($totalHours, 1, ',', '.') }}h</td>
                    <td class="text-right" style="color: #059669;">{{ number_format($billableHours, 1, ',', '.') }}h</td>
                    <td class="text-right" style="color: #f59e0b;">{{ number_format($nonBillableHours, 1, ',', '.') }}h</td>
                    <td class="text-right">{{ number_format($billablePercent, 0) }}%</td>
                    <td class="text-right money">R$ {{ number_format($totalBilled, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($avgPerUser, 1, ',', '.') }}h</td>
                </tr>
            </tfoot>
        </table>
    @endif

    {{-- Performance Tips --}}
    <div style="margin-top: 20px; padding: 10px; background: #f5f3ff; border-radius: 5px; font-size: 8px;">
        <strong>📌 Métricas de Referência:</strong>
        <ul style="margin: 5px 0 0 15px;">
            <li>Meta de horas faturáveis: 70% ou mais</li>
            <li>Média diária recomendada: 6-8 horas</li>
            <li>Taxa de utilização ideal: 75-85%</li>
        </ul>
    </div>

    <div class="footer">
        <span class="left">LogísticaJus © {{ date('Y') }} - Relatório de Produtividade</span>
        <span class="right">Página 1</span>
    </div>
</body>
</html>
