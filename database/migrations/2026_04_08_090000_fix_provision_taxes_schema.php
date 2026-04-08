<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('provision_taxes')) {
            return;
        }

        DB::statement('
            CREATE TABLE provision_taxes_repaired (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                date DATE NOT NULL,
                amount INT NOT NULL,
                description TEXT NULL,
                file VARCHAR(255) NULL,
                created_at TIMESTAMP NULL DEFAULT NULL,
                updated_at TIMESTAMP NULL DEFAULT NULL,
                bank_id INT NULL,
                debit_number VARCHAR(255) NULL
            )
        ');

        DB::statement('
            INSERT INTO provision_taxes_repaired (
                date,
                amount,
                description,
                file,
                created_at,
                updated_at,
                bank_id,
                debit_number
            )
            SELECT
                date,
                amount,
                description,
                file,
                created_at,
                updated_at,
                NULLIF(bank_id, 0),
                NULLIF(CAST(debit_number AS CHAR), \'\')
            FROM provision_taxes
            ORDER BY
                CASE WHEN id IS NULL OR id = 0 THEN 1 ELSE 0 END,
                id,
                created_at,
                updated_at
        ');

        Schema::drop('provision_taxes');
        Schema::rename('provision_taxes_repaired', 'provision_taxes');
    }

    public function down(): void
    {
        // Intentionally left empty because the previous schema was invalid.
    }
};
