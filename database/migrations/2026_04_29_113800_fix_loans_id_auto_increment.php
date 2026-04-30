<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tableInfo = DB::selectOne('SHOW CREATE TABLE loans');
        $createTableSql = $tableInfo->{'Create Table'} ?? '';

        if (! str_contains($createTableSql, 'PRIMARY KEY (`id`)')) {
            DB::statement('ALTER TABLE loans ADD PRIMARY KEY (`id`)');
        }

        $zeroIdExists = DB::table('loans')->where('id', 0)->exists();
        if ($zeroIdExists) {
            $nextId = ((int) DB::table('loans')->max('id')) + 1;
            DB::table('loans')->where('id', 0)->update(['id' => $nextId]);
        }

        if (! str_contains(strtolower($createTableSql), 'auto_increment')) {
            DB::statement('ALTER TABLE loans MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE loans MODIFY `id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE loans DROP PRIMARY KEY');
    }
};
