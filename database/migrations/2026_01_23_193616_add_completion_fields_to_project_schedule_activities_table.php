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
        Schema::table('project_schedule_activities', function (Blueprint $table) {
            $table->text('completion_notes')->nullable()->after('notes');
            $table->string('attachment_path')->nullable()->after('completion_notes');
            $table->string('attachment_name')->nullable()->after('attachment_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_schedule_activities', function (Blueprint $table) {
            $table->dropColumn(['completion_notes', 'attachment_path', 'attachment_name']);
        });
    }
};
