<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('juris_settings', function (Blueprint $table) {
            $table->id();
            $table->string('office_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('diligencias_email')->nullable();
            $table->string('address')->nullable();
            $table->string('oab')->nullable();
            $table->string('website')->nullable();
            $table->string('primary_color')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('juris_settings');
    }
};