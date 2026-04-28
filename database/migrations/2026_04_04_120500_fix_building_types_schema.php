<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('building_types')) {
            return;
        }

        $maxId = (int) (DB::table('building_types')
            ->where('id', '>', 0)
            ->max('id') ?? 0);

        $invalidCount = (int) DB::table('building_types')
            ->where('id', '<=', 0)
            ->count();

        for ($i = 0; $i < $invalidCount; $i++) {
            $maxId++;
            DB::statement(
                "UPDATE building_types
                 SET id = {$maxId}
                 WHERE id <= 0
                 ORDER BY created_at ASC, name ASC
                 LIMIT 1"
            );
        }

        $duplicateIds = DB::select(
            'SELECT id, COUNT(*) AS total
             FROM building_types
             GROUP BY id
             HAVING COUNT(*) > 1'
        );

        foreach ($duplicateIds as $duplicate) {
            $duplicateId = (int) $duplicate->id;
            $extraRows = ((int) $duplicate->total) - 1;

            for ($i = 0; $i < $extraRows; $i++) {
                $maxId++;
                DB::statement(
                    "UPDATE building_types
                     SET id = {$maxId}
                     WHERE id = {$duplicateId}
                     ORDER BY created_at DESC, name DESC
                     LIMIT 1"
                );
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $hasPrimaryKey = !empty(DB::select("SHOW INDEX FROM building_types WHERE Key_name = 'PRIMARY'"));
        if (!$hasPrimaryKey) {
            DB::statement('ALTER TABLE building_types MODIFY id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE building_types ADD PRIMARY KEY (id)');
        }

        DB::statement('ALTER TABLE building_types MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        if (!Schema::hasTable('building_types')) {
            return;
        }

        DB::statement('ALTER TABLE building_types MODIFY id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE building_types DROP PRIMARY KEY');
    }
};
