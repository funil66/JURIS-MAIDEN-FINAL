<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'name' => 'PIX',
                'code' => 'PIX',
                'description' => 'Transferência instantânea via PIX',
                'icon' => 'heroicon-o-qr-code',
                'color' => 'success',
                'sort_order' => 1,
            ],
            [
                'name' => 'Transferência Bancária',
                'code' => 'TED',
                'description' => 'TED ou DOC bancário',
                'icon' => 'heroicon-o-building-library',
                'color' => 'primary',
                'sort_order' => 2,
            ],
            [
                'name' => 'Boleto Bancário',
                'code' => 'BOL',
                'description' => 'Boleto bancário',
                'icon' => 'heroicon-o-document-text',
                'color' => 'warning',
                'sort_order' => 3,
            ],
            [
                'name' => 'Dinheiro',
                'code' => 'DIN',
                'description' => 'Pagamento em espécie',
                'icon' => 'heroicon-o-banknotes',
                'color' => 'success',
                'sort_order' => 4,
            ],
            [
                'name' => 'Cartão de Crédito',
                'code' => 'CC',
                'description' => 'Pagamento via cartão de crédito',
                'icon' => 'heroicon-o-credit-card',
                'color' => 'info',
                'sort_order' => 5,
            ],
            [
                'name' => 'Cartão de Débito',
                'code' => 'CD',
                'description' => 'Pagamento via cartão de débito',
                'icon' => 'heroicon-o-credit-card',
                'color' => 'info',
                'sort_order' => 6,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }
    }
}
