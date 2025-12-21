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
        Schema::table('clients', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email');
            $table->boolean('portal_access')->default(false)->after('password');
            $table->timestamp('portal_last_login_at')->nullable()->after('portal_access');
            $table->string('portal_token')->nullable()->after('portal_last_login_at');
            $table->timestamp('portal_token_expires_at')->nullable()->after('portal_token');
            $table->rememberToken()->after('portal_token_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'password',
                'portal_access',
                'portal_last_login_at',
                'portal_token',
                'portal_token_expires_at',
                'remember_token',
            ]);
        });
    }
};
