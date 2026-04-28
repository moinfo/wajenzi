<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $maxId = (int) (DB::table('project_types')
            ->where('id', '>', 0)
            ->max('id') ?? 0);

        $invalidCount = (int) DB::table('project_types')
            ->where('id', '<=', 0)
            ->count();

        for ($i = 0; $i < $invalidCount; $i++) {
            $maxId++;
            DB::statement(
                "UPDATE project_types
                 SET id = {$maxId}
                 WHERE id <= 0
                 ORDER BY created_at ASC, name ASC
                 LIMIT 1"
            );
        }

        $duplicateIds = DB::select(
            'SELECT id, COUNT(*) AS total
             FROM project_types
             GROUP BY id
             HAVING COUNT(*) > 1'
        );

        foreach ($duplicateIds as $duplicate) {
            $duplicateId = (int) $duplicate->id;
            $extraRows = ((int) $duplicate->total) - 1;

            for ($i = 0; $i < $extraRows; $i++) {
                $maxId++;
                DB::statement(
                    "UPDATE project_types
                     SET id = {$maxId}
                     WHERE id = {$duplicateId}
                     ORDER BY created_at DESC, name DESC
                     LIMIT 1"
                );
            }
        }

        $hasPK = !empty(DB::select("SHOW INDEX FROM project_types WHERE Key_name = 'PRIMARY'"));
        if (!$hasPK) {
            DB::statement('ALTER TABLE project_types MODIFY id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE project_types ADD PRIMARY KEY (id)');
        }
        DB::statement('ALTER TABLE project_types MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    public function down(): void
    {
        if (!Schema::hasTable('project_types')) {
            return;
        }
        DB::statement('ALTER TABLE project_types MODIFY id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE project_types DROP PRIMARY KEY');
    }
};
