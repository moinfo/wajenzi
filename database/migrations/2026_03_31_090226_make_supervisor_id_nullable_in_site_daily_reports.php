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
        Schema::table('site_daily_reports', function (Blueprint $table) {
            if (Schema::hasColumn('site_daily_reports', 'supervisor_id')) {
                // Column exists, modify using raw SQL
                \DB::statement('ALTER TABLE site_daily_reports MODIFY COLUMN supervisor_id BIGINT UNSIGNED NULL');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_daily_reports', function (Blueprint $table) {
            //
        });
    }
};
