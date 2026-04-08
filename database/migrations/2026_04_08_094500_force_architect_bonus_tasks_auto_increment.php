<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('architect_bonus_tasks')) {
            return;
        }

        DB::statement('UPDATE architect_bonus_tasks SET id = NULL WHERE id = 0');
        DB::statement('ALTER TABLE architect_bonus_tasks MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    public function down(): void
    {
        // Intentionally left empty.
    }
};
