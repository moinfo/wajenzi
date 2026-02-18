<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_schedule_activities', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('assigned_to')->constrained('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_schedule_activities', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
