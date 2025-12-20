<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Service;
use App\Models\ServiceType;
use App\Models\Event;
use App\Models\Transaction;
use App\Models\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * Seed de dados de teste para demonstração do sistema.
     * 
     * Cria:
     * - 10 clientes (5 PF, 5 PJ)
     * - 20 serviços variados
     * - 15 eventos no calendário
     * - 30 transações (receitas e despesas)
     */
    public function run(): void
    {
        $this->command->info('🚀 Criando dados de teste...');

        // Clientes Pessoa Física
        $clientesPF = [
            ['name' => 'Maria Silva Santos', 'email' => 'maria.silva@email.com', 'type' => 'pf', 'document' => '123.456.789-00', 'phone' => '(11) 98765-4321', 'city' => 'São Paulo', 'state' => 'SP'],
            ['name' => 'João Pedro Oliveira', 'email' => 'joao.oliveira@email.com', 'type' => 'pf', 'document' => '234.567.890-11', 'phone' => '(21) 97654-3210', 'city' => 'Rio de Janeiro', 'state' => 'RJ'],
            ['name' => 'Ana Carolina Ferreira', 'email' => 'ana.ferreira@email.com', 'type' => 'pf', 'document' => '345.678.901-22', 'phone' => '(31) 96543-2109', 'city' => 'Belo Horizonte', 'state' => 'MG'],
            ['name' => 'Carlos Eduardo Lima', 'email' => 'carlos.lima@email.com', 'type' => 'pf', 'document' => '456.789.012-33', 'phone' => '(41) 95432-1098', 'city' => 'Curitiba', 'state' => 'PR'],
            ['name' => 'Fernanda Costa Souza', 'email' => 'fernanda.souza@email.com', 'type' => 'pf', 'document' => '567.890.123-44', 'phone' => '(51) 94321-0987', 'city' => 'Porto Alegre', 'state' => 'RS'],
        ];

        // Clientes Pessoa Jurídica
        $clientesPJ = [
            ['name' => 'Advocacia Silva & Associados', 'email' => 'contato@silvaadvocacia.com.br', 'type' => 'pj', 'document' => '12.345.678/0001-00', 'phone' => '(11) 3456-7890', 'city' => 'São Paulo', 'state' => 'SP'],
            ['name' => 'Escritório Jurídico Oliveira', 'email' => 'contato@oliveiralaw.com.br', 'type' => 'pj', 'document' => '23.456.789/0001-11', 'phone' => '(21) 2345-6789', 'city' => 'Rio de Janeiro', 'state' => 'RJ'],
            ['name' => 'Ferreira Advogados Associados', 'email' => 'juridico@ferreiraadv.com.br', 'type' => 'pj', 'document' => '34.567.890/0001-22', 'phone' => '(31) 3234-5678', 'city' => 'Belo Horizonte', 'state' => 'MG'],
            ['name' => 'Lima & Costa Advocacia', 'email' => 'atendimento@limacosta.adv.br', 'type' => 'pj', 'document' => '45.678.901/0001-33', 'phone' => '(41) 4123-4567', 'city' => 'Curitiba', 'state' => 'PR'],
            ['name' => 'Souza Advogados S/C', 'email' => 'contato@souzaadvogados.com.br', 'type' => 'pj', 'document' => '56.789.012/0001-44', 'phone' => '(51) 5012-3456', 'city' => 'Porto Alegre', 'state' => 'RS'],
        ];

        $clientes = [];
        foreach (array_merge($clientesPF, $clientesPJ) as $clienteData) {
            $clientes[] = Client::create([
                'name' => $clienteData['name'],
                'email' => $clienteData['email'],
                'type' => $clienteData['type'],
                'document' => $clienteData['document'],
                'phone' => $clienteData['phone'],
                'street' => 'Rua Exemplo',
                'number' => (string) rand(100, 999),
                'neighborhood' => 'Centro',
                'city' => $clienteData['city'],
                'state' => $clienteData['state'],
                'cep' => rand(10000, 99999) . '-' . rand(100, 999),
                'notes' => 'Cliente de teste criado pelo seeder.',
                'is_active' => true,
            ]);
        }
        $this->command->info('✅ 10 clientes criados');

        // Buscar tipos de serviço e métodos de pagamento
        $tiposServico = ServiceType::all();
        $metodosPagamento = PaymentMethod::all();

        // Criar serviços variados
        $statusList = ['pending', 'in_progress', 'completed', 'cancelled'];
        $paymentStatusList = ['pending', 'partial', 'paid'];
        $tribunais = ['TJSP', 'TJRJ', 'TJMG', 'TJPR', 'TJRS', 'TRT-2', 'TRT-1', 'JF-SP', 'JF-RJ'];
        $varas = ['1ª Vara Cível', '2ª Vara Cível', '3ª Vara do Trabalho', 'Vara de Família', '1ª Vara Criminal', 'Juizado Especial'];

        for ($i = 0; $i < 20; $i++) {
            $cliente = $clientes[array_rand($clientes)];
            $tipoServico = $tiposServico->random();
            $dataBase = Carbon::now()->subDays(rand(-30, 60));
            $preco = rand(150, 2500);

            Service::create([
                'code' => 'SRV-' . date('Y') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'client_id' => $cliente->id,
                'service_type_id' => $tipoServico->id,
                'process_number' => rand(1000000, 9999999) . '-' . rand(10, 99) . '.' . date('Y') . '.8.26.' . rand(1000, 9999),
                'court' => $tribunais[array_rand($tribunais)],
                'jurisdiction' => $varas[array_rand($varas)],
                'state' => $cliente->state,
                'plaintiff' => 'Autor ' . ($i + 1),
                'defendant' => 'Réu ' . ($i + 1),
                'request_date' => $dataBase->copy()->subDays(rand(1, 10)),
                'deadline_date' => $dataBase->copy()->addDays(rand(5, 30)),
                'scheduled_datetime' => $dataBase,
                'location_name' => 'Fórum ' . $cliente->city,
                'location_address' => 'Praça da Justiça, ' . rand(1, 100),
                'location_city' => $cliente->city,
                'location_state' => $cliente->state,
                'agreed_price' => $preco,
                'expenses' => rand(0, 100),
                'total_price' => $preco + rand(0, 100),
                'status' => $statusList[array_rand($statusList)],
                'payment_status' => $paymentStatusList[array_rand($paymentStatusList)],
                'priority' => ['low', 'normal', 'high', 'urgent'][array_rand(['low', 'normal', 'high', 'urgent'])],
                'description' => 'Serviço de teste #' . ($i + 1) . ' - ' . $tipoServico->name,
                'instructions' => 'Instruções de teste para o serviço.',
            ]);
        }
        $this->command->info('✅ 20 serviços criados');

        // Criar eventos no calendário
        $tiposEvento = ['hearing', 'deadline', 'meeting', 'task', 'reminder', 'appointment', 'other'];
        $cores = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'];

        for ($i = 0; $i < 15; $i++) {
            $dataEvento = Carbon::now()->addDays(rand(-7, 30))->setTime(rand(8, 17), rand(0, 1) * 30);
            $tipoEvento = $tiposEvento[array_rand($tiposEvento)];
            
            Event::create([
                'title' => ucfirst($tipoEvento) . ' - Cliente ' . ($i + 1),
                'description' => 'Evento de teste criado pelo seeder.',
                'type' => $tipoEvento,
                'starts_at' => $dataEvento,
                'ends_at' => $dataEvento->copy()->addHours(rand(1, 3)),
                'location' => 'Local do evento ' . ($i + 1),
                'all_day' => rand(0, 1) === 1,
                'color' => $cores[array_rand($cores)],
                'reminder_minutes' => [15, 30, 60, 1440][array_rand([15, 30, 60, 1440])],
                'client_id' => $clientes[array_rand($clientes)]->id,
                'status' => 'scheduled',
            ]);
        }
        $this->command->info('✅ 15 eventos criados');

        // Criar transações (receitas e despesas)
        $categoriasReceita = ['Honorários', 'Diligência', 'Audiência', 'Consultoria', 'Protocolo'];
        $categoriasDespesa = ['Transporte', 'Alimentação', 'Cópias', 'Taxas judiciais', 'Material de escritório'];

        for ($i = 0; $i < 30; $i++) {
            $isReceita = rand(0, 1) === 1;
            $cliente = $clientes[array_rand($clientes)];
            $metodoPagamento = $metodosPagamento->random();
            $dataVencimento = Carbon::now()->addDays(rand(-15, 45));
            $valor = rand(50, 3000);
            $isPago = rand(0, 1) === 1;

            Transaction::create([
                'code' => ($isReceita ? 'REC' : 'DES') . '-' . date('Y') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'type' => $isReceita ? 'income' : 'expense',
                'client_id' => $isReceita ? $cliente->id : null,
                'payment_method_id' => $metodoPagamento->id,
                'category' => $isReceita ? $categoriasReceita[array_rand($categoriasReceita)] : $categoriasDespesa[array_rand($categoriasDespesa)],
                'amount' => $valor,
                'discount' => rand(0, 1) === 1 ? rand(5, 50) : 0,
                'fees' => rand(0, 1) === 1 ? rand(1, 20) : 0,
                'net_amount' => $valor,
                'due_date' => $dataVencimento,
                'paid_date' => $isPago ? $dataVencimento->copy()->subDays(rand(0, 5)) : null,
                'status' => $isPago ? 'paid' : ($dataVencimento->isPast() ? 'overdue' : 'pending'),
                'description' => 'Transação de teste #' . ($i + 1),
                'notes' => 'Criado pelo seeder de dados de teste.',
            ]);
        }
        $this->command->info('✅ 30 transações criadas');

        $this->command->info('');
        $this->command->info('🎉 Dados de teste criados com sucesso!');
        $this->command->info('');
        $this->command->table(
            ['Entidade', 'Quantidade'],
            [
                ['Clientes', '10 (5 PF + 5 PJ)'],
                ['Serviços', '20'],
                ['Eventos', '15'],
                ['Transações', '30'],
            ]
        );
    }
}
