<?php

namespace App\Exports;

use App\Models\Service;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ServicesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $dateStart;
    protected $dateEnd;
    protected $clientId;
    protected $status;

    public function __construct($dateStart, $dateEnd, $clientId = null, $status = null)
    {
        $this->dateStart = $dateStart;
        $this->dateEnd = $dateEnd;
        $this->clientId = $clientId;
        $this->status = $status;
    }

    public function collection()
    {
        $query = Service::with(['client', 'serviceType'])
            ->whereBetween('scheduled_datetime', [$this->dateStart, $this->dateEnd]);

        if ($this->clientId) {
            $query->where('client_id', $this->clientId);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        return $query->orderBy('scheduled_datetime', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'Código',
            'Data',
            'Cliente',
            'Tipo de Serviço',
            'Local',
            'Status',
            'Valor (R$)',
            'Observações',
            'Criado em',
        ];
    }

    public function map($service): array
    {
        $statusLabels = [
            'pending' => 'Pendente',
            'in_progress' => 'Em Andamento',
            'completed' => 'Concluído',
            'cancelled' => 'Cancelado',
        ];

        return [
            $service->code,
            $service->scheduled_datetime->format('d/m/Y'),
            $service->client->name ?? 'N/A',
            $service->serviceType->name ?? 'N/A',
            $service->location ?? 'N/A',
            $statusLabels[$service->status] ?? $service->status,
            number_format($service->value ?? 0, 2, ',', '.'),
            $service->notes ?? '',
            $service->created_at->format('d/m/Y H:i'),
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
