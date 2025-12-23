<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adiciona coluna UID em todas as tabelas principais do sistema.
     * O UID é um identificador único global que não se repete em nenhuma tabela.
     */
    public function up(): void
    {
        // Clientes
        Schema::table('clients', function (Blueprint $table) {
            $table->string('uid', 20)->nullable()->unique()->after('id');
        });

        // Serviços
        Schema::table('services', function (Blueprint $table) {
            $table->string('uid', 20)->nullable()->unique()->after('id');
        });

        // Eventos
        Schema::table('events', function (Blueprint $table) {
            $table->string('uid', 20)->nullable()->unique()->after('id');
        });

        // Transações
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('uid', 20)->nullable()->unique()->after('id');
        });

        // Templates de Documentos
        Schema::table('document_templates', function (Blueprint $table) {
            $table->string('uid', 20)->nullable()->unique()->after('id');
        });

        // Documentos Gerados
        Schema::table('generated_documents', function (Blueprint $table) {
            $table->string('uid', 20)->nullable()->unique()->after('id');
        });

        // Tipos de Serviço
        Schema::table('service_types', function (Blueprint $table) {
            $table->string('uid', 20)->nullable()->unique()->after('id');
        });

        // Métodos de Pagamento
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('uid', 20)->nullable()->unique()->after('id');
        });

        // Usuários
        Schema::table('users', function (Blueprint $table) {
            $table->string('uid', 20)->nullable()->unique()->after('id');
        });

        // Google Calendar Events
        Schema::table('google_calendar_events', function (Blueprint $table) {
            $table->string('uid', 20)->nullable()->unique()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('uid');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('uid');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('uid');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('uid');
        });

        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropColumn('uid');
        });

        Schema::table('generated_documents', function (Blueprint $table) {
            $table->dropColumn('uid');
        });

        Schema::table('service_types', function (Blueprint $table) {
            $table->dropColumn('uid');
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn('uid');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('uid');
        });

        Schema::table('google_calendar_events', function (Blueprint $table) {
            $table->dropColumn('uid');
        });
    }
};
