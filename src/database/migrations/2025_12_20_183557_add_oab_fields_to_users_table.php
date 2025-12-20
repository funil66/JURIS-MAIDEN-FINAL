<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adiciona campos profissionais para advogados:
     * - OAB: número de inscrição na Ordem dos Advogados do Brasil
     * - OAB_UF: estado de registro (SP, RJ, MG, etc.)
     * - Especialidades: áreas de atuação (JSON)
     * - Telefone: contato profissional
     * - Bio: descrição/currículo resumido
     * - Avatar: foto de perfil
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('oab', 20)->nullable()->after('email')->comment('Número OAB');
            $table->string('oab_uf', 2)->nullable()->after('oab')->comment('UF do registro OAB');
            $table->json('specialties')->nullable()->after('oab_uf')->comment('Áreas de atuação');
            $table->string('phone', 20)->nullable()->after('specialties')->comment('Telefone profissional');
            $table->string('whatsapp', 20)->nullable()->after('phone')->comment('WhatsApp');
            $table->text('bio')->nullable()->after('whatsapp')->comment('Descrição/currículo resumido');
            $table->string('avatar')->nullable()->after('bio')->comment('Foto de perfil');
            $table->string('website')->nullable()->after('avatar')->comment('Site pessoal');
            $table->string('linkedin')->nullable()->after('website')->comment('Perfil LinkedIn');
            $table->boolean('is_active')->default(true)->after('linkedin');
            
            // Índices
            $table->index('oab');
            $table->index('oab_uf');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['oab']);
            $table->dropIndex(['oab_uf']);
            $table->dropIndex(['is_active']);
            
            $table->dropColumn([
                'oab',
                'oab_uf',
                'specialties',
                'phone',
                'whatsapp',
                'bio',
                'avatar',
                'website',
                'linkedin',
                'is_active',
            ]);
        });
    }
};
