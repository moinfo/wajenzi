<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $hasPK = DB::select("SHOW INDEX FROM project_daily_reports WHERE Key_name = 'PRIMARY'");

        if (!$hasPK) {
            DB::statement('ALTER TABLE project_daily_reports MODIFY id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE project_daily_reports ADD PRIMARY KEY (id)');
        }

        DB::statement('ALTER TABLE project_daily_reports MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE project_daily_reports MODIFY id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE project_daily_reports DROP PRIMARY KEY');
    }
};
