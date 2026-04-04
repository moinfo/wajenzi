<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('personal_access_tokens')) {
            return;
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $databaseName = DB::getDatabaseName();

        $rows = DB::table('personal_access_tokens')
            ->select('token')
            ->orderBy('created_at')
            ->get();

        $nextId = 1;
        foreach ($rows as $row) {
            DB::table('personal_access_tokens')
                ->where('token', $row->token)
                ->update(['id' => $nextId]);
            $nextId++;
        }

        $hasPrimaryKey = DB::table('information_schema.statistics')
            ->where('table_schema', $databaseName)
            ->where('table_name', 'personal_access_tokens')
            ->where('index_name', 'PRIMARY')
            ->exists();

        if (!$hasPrimaryKey) {
            DB::statement('ALTER TABLE `personal_access_tokens` ADD PRIMARY KEY (`id`)');
        }

        DB::statement(
            'ALTER TABLE `personal_access_tokens` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT'
        );

        $hasTokenableIndex = DB::table('information_schema.statistics')
            ->where('table_schema', $databaseName)
            ->where('table_name', 'personal_access_tokens')
            ->where('index_name', 'personal_access_tokens_tokenable_type_tokenable_id_index')
            ->exists();

        if (!$hasTokenableIndex) {
            DB::statement(
                'ALTER TABLE `personal_access_tokens` ADD INDEX `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`, `tokenable_id`)'
            );
        }

        $hasTokenUnique = DB::table('information_schema.statistics')
            ->where('table_schema', $databaseName)
            ->where('table_name', 'personal_access_tokens')
            ->where('index_name', 'personal_access_tokens_token_unique')
            ->exists();

        if (!$hasTokenUnique) {
            DB::statement(
                'ALTER TABLE `personal_access_tokens` ADD UNIQUE `personal_access_tokens_token_unique` (`token`)'
            );
        }

        $hasExpiresIndex = DB::table('information_schema.statistics')
            ->where('table_schema', $databaseName)
            ->where('table_name', 'personal_access_tokens')
            ->where('index_name', 'personal_access_tokens_expires_at_index')
            ->exists();

        if (!$hasExpiresIndex) {
            DB::statement(
                'ALTER TABLE `personal_access_tokens` ADD INDEX `personal_access_tokens_expires_at_index` (`expires_at`)'
            );
        }

        DB::statement(
            'ALTER TABLE `personal_access_tokens` AUTO_INCREMENT = ' . max($nextId, 1)
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This repair migration is intentionally not reversible.
    }
};
