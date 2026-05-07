<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_activity_templates', function (Blueprint $table) {
            $table->boolean('requires_approval')->default(false)->after('sort_order');
        });

        Schema::table('project_schedule_activities', function (Blueprint $table) {
            $table->boolean('requires_approval')->default(false)->after('sort_order');
            $table->string('approval_notes', 500)->nullable()->after('requires_approval');
        });
    }

    public function down(): void
    {
        Schema::table('project_activity_templates', function (Blueprint $table) {
            $table->dropColumn('requires_approval');
        });

        Schema::table('project_schedule_activities', function (Blueprint $table) {
            $table->dropColumn(['requires_approval', 'approval_notes']);
        });
    }
};
