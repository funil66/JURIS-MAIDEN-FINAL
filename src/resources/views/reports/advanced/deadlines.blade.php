<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Prazos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; line-height: 1.4; color: #1f2937; }
        
        .header { text-align: center; padding: 15px 0; border-bottom: 3px solid #f59e0b; margin-bottom: 20px; }
        .header h1 { font-size: 22px; color: #f59e0b; margin-bottom: 5px; }
        .header .subtitle { font-size: 11px; color: #6b7280; }
        .header .period { font-size: 10px; color: #374151; margin-top: 5px; }
        
        .summary-grid { display: table; width: 100%; margin-bottom: 20px; }
        .summary-card { display: table-cell; width: 16.66%; padding: 10px; text-align: center; background: #fffbeb; border: 1px solid #fde68a; }
        .summary-card .label { font-size: 8px; color: #6b7280; text-transform: uppercase; }
        .summary-card .value { font-size: 16px; font-weight: bold; color: #d97706; }
        .summary-card .value.success { color: #059669; }
        .summary-card .value.danger { color: #dc2626; }
        .summary-card .value.warning { color: #f59e0b; }
        
        .alert-box { padding: 10px 15px; border-radius: 5px; margin-bottom: 15px; }
        .alert-danger { background: #fee2e2; border-left: 4px solid #dc2626; }
        .alert-warning { background: #fef3c7; border-left: 4px solid #f59e0b; }
        .alert-title { font-weight: bold; font-size: 11px; margin-bottom: 3px; }
        .alert-text { font-size: 9px; color: #6b7280; }
        
        .section-title { font-size: 12px; font-weight: bold; color: #92400e; margin: 15px 0 10px; padding-bottom: 5px; border-bottom: 2px solid #f59e0b; }
        
        .data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .data-table thead { background: #d97706; }
        .data-table th { color: #fff; padding: 8px 5px; text-align: left; font-size: 8px; font-weight: bold; }
        .data-table td { padding: 6px 5px; border-bottom: 1px solid #e5e7eb; font-size: 8px; }
        .data-table tbody tr:nth-child(even) { background: #fffbeb; }
        .data-table tbody tr.overdue { background: #fee2e2; }
        .data-table tbody tr.today { background: #fef3c7; }
        .data-table tbody tr.completed { background: #d1fae5; }
        
        .badge { display: inline-block; padding: 2px 5px; border-radius: 3px; font-size: 7px; font-weight: bold; }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-in-progress { background: #dbeafe; color: #2563eb; }
        .badge-completed { background: #d1fae5; color: #059669; }
        .badge-extended { background: #e0e7ff; color: #4f46e5; }
        .badge-missed { background: #fee2e2; color: #dc2626; }
        .badge-cancelled { background: #e5e7eb; color: #6b7280; }
        
        .priority-low { color: #6b7280; }
        .priority-normal { color: #2563eb; }
        .priority-high { color: #f59e0b; font-weight: bold; }
        .priority-critical { color: #dc2626; font-weight: bold; text-transform: uppercase; }
        
        .days-remaining { font-weight: bold; }
        .days-overdue { color: #dc2626; }
        .days-today { color: #f59e0b; }
        .days-ok { color: #059669; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 8px 20px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #9ca3af; }
        .footer .left { float: left; }
        .footer .right { float: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>⏰ Relatório de Prazos</h1>
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
            $overdue = $summary['overdue'] ?? 0;
            $dueThisWeek = $summary['due_this_week'] ?? 0;
            $byStatus = $summary['by_status'] ?? collect([]);
        @endphp
        
        {{-- Alerts --}}
        @if($overdue > 0)
            <div class="alert-box alert-danger">
                <div class="alert-title">⚠️ ATENÇÃO: {{ $overdue }} prazo(s) vencido(s)!</div>
                <div class="alert-text">Existem prazos que já expiraram e precisam de atenção imediata.</div>
            </div>
        @endif
        
        @if($dueThisWeek > 0)
            <div class="alert-box alert-warning">
                <div class="alert-title">📅 {{ $dueThisWeek }} prazo(s) vencem esta semana</div>
                <div class="alert-text">Prazos próximos do vencimento que requerem acompanhamento.</div>
            </div>
        @endif

        <table class="summary-grid">
            <tr>
                <td class="summary-card">
                    <div class="label">Total de Prazos</div>
                    <div class="value">{{ number_format($summary['total_records'] ?? 0) }}</div>
                </td>
                <td class="summary-card">
                    <div class="label">Vencidos</div>
                    <div class="value danger">{{ $overdue }}</div>
                </td>
                <td class="summary-card">
                    <div class="label">Esta Semana</div>
                    <div class="value warning">{{ $dueThisWeek }}</div>
                </td>
                <td class="summary-card">
                    <div class="label">Pendentes</div>
                    <div class="value">{{ $byStatus['pending'] ?? 0 }}</div>
                </td>
                <td class="summary-card">
                    <div class="label">Cumpridos</div>
                    <div class="value success">{{ $byStatus['completed'] ?? 0 }}</div>
                </td>
                <td class="summary-card">
                    <div class="label">Perdidos</div>
                    <div class="value danger">{{ $byStatus['missed'] ?? 0 }}</div>
                </td>
            </tr>
        </table>
    @endif

    {{-- Data Table --}}
    @if(($include_details ?? true) && $data->count() > 0)
        <div class="section-title">📋 Lista de Prazos ({{ $data->count() }})</div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 55px;">Código</th>
                    <th>Título</th>
                    <th style="width: 90px;">Processo</th>
                    <th style="width: 70px;">Tipo</th>
                    <th style="width: 60px;">Início</th>
                    <th style="width: 60px;">Vencimento</th>
                    <th style="width: 50px;">Restam</th>
                    <th style="width: 50px;">Status</th>
                    <th style="width: 50px;">Prior.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $deadline)
                    @php
                        $now = now()->startOfDay();
                        $dueDate = $deadline->due_date ? \Carbon\Carbon::parse($deadline->due_date)->startOfDay() : null;
                        $isOverdue = $dueDate && $dueDate->lt($now) && $deadline->status === 'pending';
                        $isToday = $dueDate && $dueDate->eq($now);
                        $daysRemaining = $dueDate ? $now->diffInDays($dueDate, false) : null;
                        
                        $rowClass = '';
                        if ($deadline->status === 'completed') $rowClass = 'completed';
                        elseif ($isOverdue) $rowClass = 'overdue';
                        elseif ($isToday) $rowClass = 'today';
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td><strong>{{ $deadline->uid }}</strong></td>
                        <td>{{ Str::limit($deadline->title, 40) }}</td>
                        <td style="font-size: 7px;">{{ $deadline->process?->cnj_number ?? $deadline->process?->title ?? '-' }}</td>
                        <td>{{ $deadline->type?->name ?? '-' }}</td>
                        <td>{{ $deadline->start_date?->format('d/m/Y') ?? '-' }}</td>
                        <td><strong>{{ $deadline->due_date?->format('d/m/Y') ?? '-' }}</strong></td>
                        <td>
                            @if($deadline->status === 'completed')
                                <span class="days-ok">✓</span>
                            @elseif($daysRemaining !== null)
                                <span class="days-remaining {{ $daysRemaining < 0 ? 'days-overdue' : ($daysRemaining <= 0 ? 'days-today' : 'days-ok') }}">
                                    @if($daysRemaining < 0)
                                        {{ abs($daysRemaining) }}d atrás
                                    @elseif($daysRemaining === 0)
                                        HOJE
                                    @else
                                        {{ $daysRemaining }}d
                                    @endif
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @php
                                $statusBadge = match($deadline->status) {
                                    'pending' => 'badge-pending',
                                    'in_progress' => 'badge-in-progress',
                                    'completed' => 'badge-completed',
                                    'extended' => 'badge-extended',
                                    'missed' => 'badge-missed',
                                    'cancelled' => 'badge-cancelled',
                                    default => 'badge-pending',
                                };
                                $statusLabel = match($deadline->status) {
                                    'pending' => 'Pend.',
                                    'in_progress' => 'Em And.',
                                    'completed' => 'Cumpr.',
                                    'extended' => 'Prorr.',
                                    'missed' => 'Perdido',
                                    'cancelled' => 'Cancel.',
                                    default => $deadline->status,
                                };
                            @endphp
                            <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                        </td>
                        <td>
                            @php
                                $priorityClass = match($deadline->priority) {
                                    'low' => 'priority-low',
                                    'normal' => 'priority-normal',
                                    'high' => 'priority-high',
                                    'critical' => 'priority-critical',
                                    default => 'priority-normal',
                                };
                                $priorityLabel = match($deadline->priority) {
                                    'low' => 'Baixa',
                                    'normal' => 'Normal',
                                    'high' => 'Alta',
                                    'critical' => 'CRÍTICA',
                                    default => $deadline->priority,
                                };
                            @endphp
                            <span class="{{ $priorityClass }}">{{ $priorityLabel }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        <span class="left">LogísticaJus © {{ date('Y') }} - Relatório de Prazos</span>
        <span class="right">Página 1</span>
    </div>
</body>
</html>
