<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $dateStart;
    protected $dateEnd;
    protected $clientId;
    protected $type;

    public function __construct($dateStart, $dateEnd, $clientId = null, $type = null)
    {
        $this->dateStart = $dateStart;
        $this->dateEnd = $dateEnd;
        $this->clientId = $clientId;
        $this->type = $type;
    }

    public function collection()
    {
        $query = Transaction::with(['client', 'paymentMethod', 'service'])
            ->whereBetween('due_date', [$this->dateStart, $this->dateEnd]);

        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }

        if ($this->type) {
            $query->where('type', $this->type);
        }

        return $query->orderBy('due_date', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Tipo',
            'Data Vencimento',
            'Data Pagamento',
            'Descrição',
            'Cliente',
            'Serviço',
            'Forma de Pagamento',
            'Status',
            'Valor (R$)',
            'Parcela',
        ];
    }

    public function map($transaction): array
    {
        $typeLabels = [
            'income' => 'Receita',
            'expense' => 'Despesa',
        ];

        $statusLabels = [
            'pending' => 'Pendente',
            'paid' => 'Pago',
            'overdue' => 'Atrasado',
            'cancelled' => 'Cancelado',
        ];

        return [
            $typeLabels[$transaction->type] ?? $transaction->type,
            $transaction->due_date->format('d/m/Y'),
            $transaction->payment_date?->format('d/m/Y') ?? '-',
            $transaction->description,
            $transaction->client->name ?? 'N/A',
            $transaction->service->code ?? 'N/A',
            $transaction->paymentMethod->name ?? 'N/A',
            $statusLabels[$transaction->status] ?? $transaction->status,
            number_format($transaction->amount, 2, ',', '.'),
            $transaction->installment ? "{$transaction->installment}/{$transaction->total_installments}" : '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E40AF'],
                ],
            ],
        ];
    }
}
