<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Audiência',
                'code' => 'AUD',
                'description' => 'Participação em audiências judiciais como preposto ou representante.',
                'default_price' => 150.00,
                'default_deadline_days' => 0,
                'icon' => 'heroicon-o-scale',
                'color' => 'primary',
                'requires_deadline' => true,
                'requires_location' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Protocolo',
                'code' => 'PROT',
                'description' => 'Protocolo de petições e documentos em cartórios e tribunais.',
                'default_price' => 50.00,
                'default_deadline_days' => 1,
                'icon' => 'heroicon-o-paper-airplane',
                'color' => 'success',
                'requires_deadline' => true,
                'requires_location' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Cópias',
                'code' => 'COP',
                'description' => 'Obtenção de cópias de processos físicos.',
                'default_price' => 80.00,
                'default_deadline_days' => 3,
                'icon' => 'heroicon-o-document-duplicate',
                'color' => 'info',
                'requires_deadline' => true,
                'requires_location' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Diligência',
                'code' => 'DIL',
                'description' => 'Diligências externas diversas (cartórios, repartições, etc).',
                'default_price' => 100.00,
                'default_deadline_days' => 2,
                'icon' => 'heroicon-o-truck',
                'color' => 'warning',
                'requires_deadline' => true,
                'requires_location' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Pesquisa de Bens',
                'code' => 'PESQ',
                'description' => 'Pesquisa de bens e patrimônio em cartórios de registro.',
                'default_price' => 200.00,
                'default_deadline_days' => 5,
                'icon' => 'heroicon-o-magnifying-glass',
                'color' => 'danger',
                'requires_deadline' => true,
                'requires_location' => false,
                'sort_order' => 5,
            ],
            [
                'name' => 'Citação/Intimação',
                'code' => 'CIT',
                'description' => 'Acompanhamento de citações e intimações.',
                'default_price' => 80.00,
                'default_deadline_days' => 5,
                'icon' => 'heroicon-o-document-text',
                'color' => 'gray',
                'requires_deadline' => true,
                'requires_location' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'Fotografia/Documentação',
                'code' => 'FOTO',
                'description' => 'Registro fotográfico de documentos, locais ou situações.',
                'default_price' => 120.00,
                'default_deadline_days' => 2,
                'icon' => 'heroicon-o-camera',
                'color' => 'info',
                'requires_deadline' => true,
                'requires_location' => true,
                'sort_order' => 7,
            ],
        ];

        foreach ($types as $type) {
            ServiceType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
