<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_lead_followups', function (Blueprint $table) {
            $table->enum('status', ['pending', 'completed', 'cancelled', 'rescheduled'])->default('pending')->after('followup_date');
            $table->timestamp('attended_at')->nullable()->after('status');
            $table->unsignedBigInteger('attended_by')->nullable()->after('attended_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_lead_followups', function (Blueprint $table) {
            $table->dropColumn(['status', 'attended_at', 'attended_by']);
        });
    }
};
