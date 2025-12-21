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
        // Campos no User para OAuth tokens
        Schema::table('users', function (Blueprint $table) {
            $table->text('google_access_token')->nullable()->after('remember_token');
            $table->text('google_refresh_token')->nullable()->after('google_access_token');
            $table->timestamp('google_token_expires_at')->nullable()->after('google_refresh_token');
            $table->string('google_calendar_id')->nullable()->after('google_token_expires_at');
        });

        // Tabela para eventos sincronizados
        Schema::create('google_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('eventable'); // Pode ser Event ou Service
            $table->string('google_event_id');
            $table->string('google_calendar_id');
            $table->enum('sync_status', ['synced', 'pending', 'error'])->default('synced');
            $table->text('last_error')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'eventable_type', 'eventable_id'], 'unique_user_eventable');
            $table->index('google_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('google_calendar_events');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'google_access_token',
                'google_refresh_token',
                'google_token_expires_at',
                'google_calendar_id',
            ]);
        });
    }
};
