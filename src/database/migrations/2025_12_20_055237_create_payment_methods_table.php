<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            
            $table->string('name')->comment('PIX, Transferência, Boleto, etc');
            $table->string('code', 20)->unique();
            $table->text('description')->nullable();
            
            // Configurações
            $table->string('icon')->nullable();
            $table->string('color', 20)->default('primary');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            // Para integração futura (PIX, etc)
            $table->json('settings')->nullable()->comment('Configurações específicas');
            
            $table->timestamps();
            
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
