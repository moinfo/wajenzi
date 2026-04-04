<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE project_daily_reports MODIFY id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE project_daily_reports ADD PRIMARY KEY (id)');
        DB::statement('ALTER TABLE project_daily_reports MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE project_daily_reports MODIFY id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE project_daily_reports DROP PRIMARY KEY');
    }
};
