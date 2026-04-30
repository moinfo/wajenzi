<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $originalSqlMode = DB::selectOne('SELECT @@SESSION.sql_mode AS sql_mode')->sql_mode ?? '';

        $tableInfo = DB::selectOne('SHOW CREATE TABLE project_clients');
        $createTableSql = $tableInfo->{'Create Table'} ?? '';

        $duplicateZeroIds = DB::table('project_clients')
            ->where('id', 0)
            ->orderBy('created_at')
            ->orderBy('updated_at')
            ->get();

        if ($duplicateZeroIds->count() > 1) {
            $nextId = ((int) DB::table('project_clients')->max('id')) + 1;

            foreach ($duplicateZeroIds->slice(1) as $duplicateRow) {
                DB::table('project_clients')
                    ->where('id', 0)
                    ->where('created_at', $duplicateRow->created_at)
                    ->where('updated_at', $duplicateRow->updated_at)
                    ->where('email', $duplicateRow->email)
                    ->limit(1)
                    ->update(['id' => $nextId++]);
            }
        }

        if (! str_contains($createTableSql, 'PRIMARY KEY (`id`)')) {
            DB::statement('ALTER TABLE project_clients ADD PRIMARY KEY (`id`)');
        }

        $tableInfo = DB::selectOne('SHOW CREATE TABLE project_clients');
        $createTableSql = $tableInfo->{'Create Table'} ?? '';

        if (! str_contains(strtolower($createTableSql), 'auto_increment')) {
            if (! str_contains($originalSqlMode, 'NO_AUTO_VALUE_ON_ZERO')) {
                $sqlMode = $originalSqlMode !== ''
                    ? $originalSqlMode . ',NO_AUTO_VALUE_ON_ZERO'
                    : 'NO_AUTO_VALUE_ON_ZERO';
                DB::statement("SET SESSION sql_mode = '{$sqlMode}'");
            }
            DB::statement('ALTER TABLE project_clients MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
            DB::statement("SET SESSION sql_mode = '{$originalSqlMode}'");
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE project_clients MODIFY `id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE project_clients DROP PRIMARY KEY');
    }
};
