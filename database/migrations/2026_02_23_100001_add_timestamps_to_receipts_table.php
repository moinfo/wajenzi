<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->timestamps();
        });

        // Backfill existing rows: use the `date` column as the best available proxy
        DB::statement("
            UPDATE receipts
            SET created_at = COALESCE(date, NOW()),
                updated_at = COALESCE(date, NOW())
            WHERE created_at IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};
