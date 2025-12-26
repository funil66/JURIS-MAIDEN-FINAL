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
        // Add columns only if they do not already exist (idempotent)
        if (!Schema::hasColumn('users', 'oab')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('oab', 20)->nullable()->after('email')->comment('Número OAB');
            });
        }

        if (!Schema::hasColumn('users', 'oab_uf')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('oab_uf', 2)->nullable()->after('oab')->comment('UF do registro OAB');
            });
        }

        if (!Schema::hasColumn('users', 'specialties')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('specialties')->nullable()->after('oab_uf')->comment('Áreas de atuação');
            });
        }

        if (!Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone', 20)->nullable()->after('specialties')->comment('Telefone profissional');
            });
        }

        if (!Schema::hasColumn('users', 'whatsapp')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('whatsapp', 20)->nullable()->after('phone')->comment('WhatsApp');
            });
        }

        if (!Schema::hasColumn('users', 'bio')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('bio')->nullable()->after('whatsapp')->comment('Descrição/currículo resumido');
            });
        }

        if (!Schema::hasColumn('users', 'avatar')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('avatar')->nullable()->after('bio')->comment('Foto de perfil');
            });
        }

        if (!Schema::hasColumn('users', 'website')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('website')->nullable()->after('avatar')->comment('Site pessoal');
            });
        }

        if (!Schema::hasColumn('users', 'linkedin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('linkedin')->nullable()->after('website')->comment('Perfil LinkedIn');
            });
        }


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse only if the columns exist to avoid errors on partial rollbacks
        $columns = [
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
        ];

        foreach ($columns as $col) {
            if (Schema::hasColumn('users', $col)) {
                Schema::table('users', function (Blueprint $table) use ($col) {
                    // drop index if exists - best effort
                    try {
                        $table->dropIndex([$col]);
                    } catch (\Exception $e) {
                        // ignore if index not present
                    }

                    try {
                        $table->dropColumn($col);
                    } catch (\Exception $e) {
                        // ignore if column could not be dropped
                    }
                });
            }
        }
    }
};
