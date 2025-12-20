<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $dateStart;
    protected $dateEnd;

    public function __construct($dateStart, $dateEnd)
    {
        $this->dateStart = $dateStart;
        $this->dateEnd = $dateEnd;
    }

    public function collection()
    {
        return Client::withCount(['services' => function ($query) {
                $query->whereBetween('scheduled_datetime', [$this->dateStart, $this->dateEnd]);
            }])
            ->withSum(['services' => function ($query) {
                $query->whereBetween('scheduled_datetime', [$this->dateStart, $this->dateEnd]);
            }], 'value')
            ->having('services_count', '>', 0)
            ->orderBy('services_sum_value', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Nome',
            'Tipo',
            'Documento',
            'Email',
            'Telefone',
            'Cidade/UF',
            'Qtd. Serviços',
            'Valor Total (R$)',
        ];
    }

    public function map($client): array
    {
        return [
            $client->name,
            $client->type === 'PF' ? 'Pessoa Física' : 'Pessoa Jurídica',
            $client->document,
            $client->email ?? 'N/A',
            $client->phone ?? 'N/A',
            $client->city && $client->state ? "{$client->city}/{$client->state}" : 'N/A',
            $client->services_count,
            number_format($client->services_sum_value ?? 0, 2, ',', '.'),
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
