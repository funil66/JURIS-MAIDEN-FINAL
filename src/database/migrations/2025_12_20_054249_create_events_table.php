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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            
            $table->string('title');
            $table->text('description')->nullable();
            
            // Tipo de evento
            $table->enum('type', [
                'hearing',      // Audiência
                'deadline',     // Prazo
                'meeting',      // Reunião
                'task',         // Tarefa
                'reminder',     // Lembrete
                'appointment',  // Compromisso
                'other'         // Outro
            ])->default('task');
            
            // Datas
            $table->datetime('starts_at');
            $table->datetime('ends_at')->nullable();
            $table->boolean('all_day')->default(false);
            
            // Recorrência
            $table->enum('recurrence', ['none', 'daily', 'weekly', 'monthly', 'yearly'])->default('none');
            $table->date('recurrence_end')->nullable();
            
            // Relacionamentos opcionais
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            
            // Localização
            $table->string('location')->nullable();
            $table->string('location_address')->nullable();
            
            // Visual
            $table->string('color', 20)->default('#3b82f6')->comment('Cor no calendário');
            
            // Status
            $table->enum('status', ['scheduled', 'confirmed', 'completed', 'cancelled'])->default('scheduled');
            
            // Lembrete
            $table->integer('reminder_minutes')->nullable()->comment('Lembrete X minutos antes');
            $table->boolean('reminder_sent')->default(false);
            
            // Google Calendar sync (futuro)
            $table->string('google_event_id')->nullable()->index();
            $table->datetime('google_synced_at')->nullable();
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('starts_at');
            $table->index('ends_at');
            $table->index('type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
