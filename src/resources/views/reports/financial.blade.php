@extends('reports.layout')

@section('title', 'Relatório Financeiro')

@section('content')
    {{-- Resumo Principal --}}
    <div class="summary-box">
        <h3>💰 Resumo Financeiro do Período</h3>
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; text-align: center; width: 33%;">
                    <div class="summary-value success">R$ {{ number_format($summary['total_income'], 2, ',', '.') }}</div>
                    <div class="summary-label">Total de Receitas</div>
                </td>
                <td style="border: none; text-align: center; width: 33%;">
                    <div class="summary-value danger">R$ {{ number_format($summary['total_expense'], 2, ',', '.') }}</div>
                    <div class="summary-label">Total de Despesas</div>
                </td>
                <td style="border: none; text-align: center; width: 33%;">
                    <div class="summary-value {{ $summary['balance'] >= 0 ? 'success' : 'danger' }}">
                        R$ {{ number_format($summary['balance'], 2, ',', '.') }}
                    </div>
                    <div class="summary-label">Saldo do Período</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Status de Pagamento --}}
    <div class="summary-box">
        <h3>📋 Status das Transações</h3>
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; text-align: center; width: 33%;">
                    <div class="summary-value success">R$ {{ number_format($summary['paid'], 2, ',', '.') }}</div>
                    <div class="summary-label">✓ Pago</div>
                </td>
                <td style="border: none; text-align: center; width: 33%;">
                    <div class="summary-value warning">R$ {{ number_format($summary['pending'], 2, ',', '.') }}</div>
                    <div class="summary-label">⏳ Pendente</div>
                </td>
                <td style="border: none; text-align: center; width: 33%;">
                    <div class="summary-value danger">R$ {{ number_format($summary['overdue'], 2, ',', '.') }}</div>
                    <div class="summary-label">⚠ Atrasado</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Lista de Transações --}}
    <h3 class="section-title">📑 Detalhamento das Transações</h3>
    
    @if($transactions->count() > 0)
    
    {{-- Receitas --}}
    @php $incomes = $transactions->where('type', 'income'); @endphp
    @if($incomes->count() > 0)
    <h4 style="color: #16a34a; margin: 15px 0 10px; font-size: 12px;">💵 Receitas ({{ $incomes->count() }} transações)</h4>
    <table>
        <thead>
            <tr style="background: #16a34a;">
                <th style="width: 12%;">Vencimento</th>
                <th style="width: 25%;">Descrição</th>
                <th style="width: 18%;">Cliente</th>
                <th style="width: 15%;">Pagamento</th>
                <th style="width: 12%;">Status</th>
                <th style="width: 18%;" class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($incomes as $transaction)
            <tr>
                <td>{{ $transaction->due_date->format('d/m/Y') }}</td>
                <td>{{ Str::limit($transaction->description, 30) }}</td>
                <td>{{ Str::limit($transaction->client->name ?? 'N/A', 20) }}</td>
                <td>{{ $transaction->paymentMethod->name ?? 'N/A' }}</td>
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
                <td class="text-right money" style="color: #16a34a; font-weight: bold;">
                    R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #16a34a; color: white;">
                <td colspan="5"><strong>SUBTOTAL RECEITAS</strong></td>
                <td class="text-right money"><strong>R$ {{ number_format($incomes->sum('amount'), 2, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>
    @endif

    {{-- Despesas --}}
    @php $expenses = $transactions->where('type', 'expense'); @endphp
    @if($expenses->count() > 0)
    <h4 style="color: #dc2626; margin: 15px 0 10px; font-size: 12px;">💸 Despesas ({{ $expenses->count() }} transações)</h4>
    <table>
        <thead>
            <tr style="background: #dc2626;">
                <th style="width: 12%;">Vencimento</th>
                <th style="width: 25%;">Descrição</th>
                <th style="width: 18%;">Categoria</th>
                <th style="width: 15%;">Pagamento</th>
                <th style="width: 12%;">Status</th>
                <th style="width: 18%;" class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expenses as $transaction)
            <tr>
                <td>{{ $transaction->due_date->format('d/m/Y') }}</td>
                <td>{{ Str::limit($transaction->description, 30) }}</td>
                <td>{{ $transaction->category ?? 'Geral' }}</td>
                <td>{{ $transaction->paymentMethod->name ?? 'N/A' }}</td>
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
                <td class="text-right money" style="color: #dc2626; font-weight: bold;">
                    R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background: #dc2626; color: white;">
                <td colspan="5"><strong>SUBTOTAL DESPESAS</strong></td>
                <td class="text-right money"><strong>R$ {{ number_format($expenses->sum('amount'), 2, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>
    @endif

    {{-- Resultado Final --}}
    <div class="summary-box" style="margin-top: 20px; background: {{ $summary['balance'] >= 0 ? '#dcfce7' : '#fee2e2' }};">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; width: 50%; font-size: 14px; font-weight: bold;">
                    RESULTADO DO PERÍODO
                </td>
                <td style="border: none; width: 50%; text-align: right; font-size: 18px; font-weight: bold; color: {{ $summary['balance'] >= 0 ? '#16a34a' : '#dc2626' }};">
                    R$ {{ number_format($summary['balance'], 2, ',', '.') }}
                </td>
            </tr>
        </table>
    </div>

    @else
    <div class="no-data">
        Nenhuma transação encontrada no período selecionado.
    </div>
    @endif
@endsection
