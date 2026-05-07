<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE imprest_requests MODIFY COLUMN status ENUM('CREATED','PENDING','APPROVED','REJECTED','REFILLED','COMPLETED') NOT NULL DEFAULT 'CREATED'");

        // Repair rows that lost their status when retire silently failed against
        // the old enum (MySQL non-strict mode saved '' instead of 'COMPLETED').
        DB::table('imprest_requests')
            ->whereNotNull('retired_at')
            ->where(function ($q) {
                $q->where('status', '')->orWhereNull('status');
            })
            ->update(['status' => 'COMPLETED']);
    }

    public function down(): void
    {
        // Move any COMPLETED rows back to APPROVED before shrinking the enum.
        DB::table('imprest_requests')->where('status', 'COMPLETED')->update(['status' => 'APPROVED']);
        DB::statement("ALTER TABLE imprest_requests MODIFY COLUMN status ENUM('CREATED','PENDING','APPROVED','REJECTED','REFILLED') NOT NULL DEFAULT 'CREATED'");
    }
};
