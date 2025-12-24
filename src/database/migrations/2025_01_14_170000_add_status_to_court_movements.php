<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('court_movements', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('source');
        });

        // Migrar dados existentes: is_imported=true -> 'imported', false -> 'pending'
        DB::table('court_movements')
            ->where('is_imported', true)
            ->update(['status' => 'imported']);

        DB::table('court_movements')
            ->where('is_imported', false)
            ->update(['status' => 'pending']);

        // Adicionar índice
        Schema::table('court_movements', function (Blueprint $table) {
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('court_movements', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
