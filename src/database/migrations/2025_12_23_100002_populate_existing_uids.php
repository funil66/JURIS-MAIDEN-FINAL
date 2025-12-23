<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Popula UIDs para todos os registros existentes no banco.
     */
    public function up(): void
    {
        // Ordem de processamento (para manter consistência)
        $tables = [
            'users' => 'USR',
            'clients' => 'CLI',
            'service_types' => 'TPS',
            'payment_methods' => 'MPG',
            'services' => 'SRV',
            'events' => 'EVT',
            'transactions' => 'TRX',
            'document_templates' => 'TPL',
            'generated_documents' => 'DOC',
            'google_calendar_events' => 'GCE',
        ];

        DB::transaction(function () use ($tables) {
            // Obter número atual da sequência
            $sequence = DB::table('global_sequences')->lockForUpdate()->first();
            $currentNumber = $sequence ? $sequence->last_number : 10000;

            foreach ($tables as $table => $prefix) {
                // Verificar se a tabela existe
                if (!DB::getSchemaBuilder()->hasTable($table)) {
                    continue;
                }

                // Verificar se a coluna uid existe
                if (!DB::getSchemaBuilder()->hasColumn($table, 'uid')) {
                    continue;
                }

                // Buscar registros sem UID
                $records = DB::table($table)
                    ->whereNull('uid')
                    ->orderBy('id')
                    ->get();

                foreach ($records as $record) {
                    $currentNumber++;
                    $uid = sprintf('%s-%d', $prefix, $currentNumber);
                    
                    DB::table($table)
                        ->where('id', $record->id)
                        ->update(['uid' => $uid]);
                }
            }

            // Atualizar a sequência global
            DB::table('global_sequences')->update([
                'last_number' => $currentNumber,
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Limpar UIDs (opcional, para rollback)
        $tables = [
            'users',
            'clients',
            'service_types',
            'payment_methods',
            'services',
            'events',
            'transactions',
            'document_templates',
            'generated_documents',
            'google_calendar_events',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table) && 
                DB::getSchemaBuilder()->hasColumn($table, 'uid')) {
                DB::table($table)->update(['uid' => null]);
            }
        }

        // Resetar sequência
        DB::table('global_sequences')->update(['last_number' => 10000]);
    }
};
