<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - LogísticaJus</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            color: white;
            padding: 20px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 12px;
            opacity: 0.9;
        }

        .header-info {
            margin-top: 10px;
            font-size: 10px;
        }

        .container {
            padding: 0 20px;
        }

        .summary-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .summary-box h3 {
            color: #1e40af;
            font-size: 14px;
            margin-bottom: 10px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 5px;
        }

        .summary-grid {
            display: table;
            width: 100%;
        }

        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 10px;
        }

        .summary-value {
            font-size: 20px;
            font-weight: bold;
            color: #1e40af;
        }

        .summary-value.success {
            color: #16a34a;
        }

        .summary-value.danger {
            color: #dc2626;
        }

        .summary-value.warning {
            color: #ca8a04;
        }

        .summary-label {
            font-size: 10px;
            color: #64748b;
            margin-top: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table thead {
            background: #1e40af;
            color: white;
        }

        table th {
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 10px;
        }

        table td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
        }

        table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        table tbody tr:hover {
            background: #eff6ff;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 600;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-in_progress {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-completed {
            background: #dcfce7;
            color: #166534;
        }

        .badge-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-paid {
            background: #dcfce7;
            color: #166534;
        }

        .badge-overdue {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-income {
            background: #dcfce7;
            color: #166534;
        }

        .badge-expense {
            background: #fee2e2;
            color: #991b1b;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #f1f5f9;
            padding: 10px 20px;
            font-size: 9px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }

        .footer-content {
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            text-align: left;
        }

        .footer-right {
            display: table-cell;
            text-align: right;
        }

        .page-break {
            page-break-after: always;
        }

        .section-title {
            color: #1e40af;
            font-size: 14px;
            margin: 20px 0 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #3b82f6;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #64748b;
            font-style: italic;
        }

        .money {
            font-family: 'DejaVu Sans Mono', monospace;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LogísticaJus</h1>
        <p>Sistema de Gestão para Advogados Correspondentes</p>
        <div class="header-info">
            <strong>@yield('title')</strong><br>
            Período: {{ \Carbon\Carbon::parse($data['date_start'])->format('d/m/Y') }} a {{ \Carbon\Carbon::parse($data['date_end'])->format('d/m/Y') }}<br>
            Gerado em: {{ now()->format('d/m/Y H:i:s') }}
        </div>
    </div>

    <div class="container">
        @yield('content')
    </div>

    <div class="footer">
        <div class="footer-content">
            <div class="footer-left">
                LogísticaJus - Sistema de Gestão Jurídica
            </div>
            <div class="footer-right">
                Documento gerado automaticamente
            </div>
        </div>
    </div>
</body>
</html>
