<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales_daily_reports')) {
            return;
        }

        DB::statement('ALTER TABLE sales_daily_reports MODIFY id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE sales_daily_reports ADD PRIMARY KEY (id)');
        DB::statement('ALTER TABLE sales_daily_reports MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    public function down(): void
    {
        if (!Schema::hasTable('sales_daily_reports')) {
            return;
        }

        DB::statement('ALTER TABLE sales_daily_reports MODIFY id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE sales_daily_reports DROP PRIMARY KEY');
    }
};
