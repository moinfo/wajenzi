<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'billing_products_services';

        if (!DB::getSchemaBuilder()->hasTable($table)) {
            return;
        }

        $rows = DB::table($table)
            ->where('id', '<=', 0)
            ->orderBy('created_at')
            ->orderBy('code')
            ->get();

        if ($rows->isNotEmpty()) {
            $maxId = (int) DB::table($table)->where('id', '>', 0)->max('id');

            foreach ($rows as $row) {
                $maxId++;
                DB::table($table)
                    ->where('code', $row->code)
                    ->where('name', $row->name)
                    ->where('created_at', $row->created_at)
                    ->update(['id' => $maxId]);
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $primaryExists = collect(DB::select("SHOW INDEX FROM {$table}"))
            ->contains(fn ($index) => ($index->Key_name ?? null) === 'PRIMARY');

        if (!$primaryExists) {
            DB::statement("ALTER TABLE {$table} ADD PRIMARY KEY (id)");
        }

        DB::statement("ALTER TABLE {$table} MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");

        $codeUniqueExists = collect(DB::select("SHOW INDEX FROM {$table}"))
            ->contains(fn ($index) => ($index->Key_name ?? null) === 'billing_products_services_code_unique');

        if (!$codeUniqueExists) {
            DB::statement("ALTER TABLE {$table} ADD UNIQUE INDEX billing_products_services_code_unique (code)");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        // Intentionally left empty: this migration repairs corrupted local schema/data.
    }
};
