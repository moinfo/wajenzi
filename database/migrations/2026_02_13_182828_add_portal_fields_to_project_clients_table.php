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
        Schema::table('project_clients', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email');
            $table->rememberToken()->after('password');
            $table->boolean('portal_access_enabled')->default(true)->after('remember_token');
            $table->timestamp('last_login_at')->nullable()->after('portal_access_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_clients', function (Blueprint $table) {
            $table->dropColumn(['password', 'remember_token', 'portal_access_enabled', 'last_login_at']);
        });
    }
};
