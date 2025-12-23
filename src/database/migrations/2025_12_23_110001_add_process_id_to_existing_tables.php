<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adiciona coluna process_id nas tabelas existentes para vinculação com processos.
     */
    public function up(): void
    {
        // Serviços podem ser vinculados a um processo
        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('process_id')
                ->nullable()
                ->after('service_type_id')
                ->constrained('processes')
                ->nullOnDelete();
        });

        // Eventos podem ser vinculados a um processo
        Schema::table('events', function (Blueprint $table) {
            $table->foreignId('process_id')
                ->nullable()
                ->after('client_id')
                ->constrained('processes')
                ->nullOnDelete();
        });

        // Transações podem ser vinculadas a um processo
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('process_id')
                ->nullable()
                ->after('client_id')
                ->constrained('processes')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['process_id']);
            $table->dropColumn('process_id');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['process_id']);
            $table->dropColumn('process_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['process_id']);
            $table->dropColumn('process_id');
        });
    }
};
