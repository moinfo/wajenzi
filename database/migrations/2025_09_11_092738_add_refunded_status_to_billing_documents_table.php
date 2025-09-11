<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add refunded status to the enum
        DB::statement("ALTER TABLE billing_documents MODIFY COLUMN status ENUM('draft', 'pending', 'sent', 'viewed', 'accepted', 'rejected', 'partial_paid', 'paid', 'overdue', 'cancelled', 'void', 'refunded') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove refunded status from the enum
        DB::statement("ALTER TABLE billing_documents MODIFY COLUMN status ENUM('draft', 'pending', 'sent', 'viewed', 'accepted', 'rejected', 'partial_paid', 'paid', 'overdue', 'cancelled', 'void') DEFAULT 'draft'");
    }
};