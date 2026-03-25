<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert receipt_date and date columns from DD/MM/YYYY to YYYY-MM-DD.
     */
    public function up(): void
    {
        // Update receipt_date: DD/MM/YYYY → YYYY-MM-DD
        DB::statement("
            UPDATE receipts
            SET receipt_date = CONCAT(
                SUBSTRING(receipt_date, 7, 4), '-',
                SUBSTRING(receipt_date, 4, 2), '-',
                SUBSTRING(receipt_date, 1, 2)
            )
            WHERE receipt_date LIKE '__/__/____'
        ");

        // Update date column: DD/MM/YYYY → YYYY-MM-DD
        DB::statement("
            UPDATE receipts
            SET date = CONCAT(
                SUBSTRING(date, 7, 4), '-',
                SUBSTRING(date, 4, 2), '-',
                SUBSTRING(date, 1, 2)
            )
            WHERE date LIKE '__/__/____'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert back: YYYY-MM-DD → DD/MM/YYYY for records that were changed
        // Note: This only affects records that were in DD/MM/YYYY format before
    }
};
