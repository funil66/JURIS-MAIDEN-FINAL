<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta tabela gerencia a sequência global de IDs únicos do sistema.
     * Cada registro em qualquer tabela terá um UID único e irrepetível.
     */
    public function up(): void
    {
        Schema::create('global_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('last_number')->default(10000);
            $table->timestamps();
        });

        // Inserir registro inicial com sequência começando em 10000
        DB::table('global_sequences')->insert([
            'last_number' => 10000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_sequences');
    }
};
