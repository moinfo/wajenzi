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
        Schema::table('billing_documents', function (Blueprint $table) {
            $table->decimal('late_fee_amount', 15, 2)->default(0)->after('total_amount');
            $table->decimal('late_fee_percentage', 5, 2)->nullable()->after('late_fee_amount');
            $table->timestamp('late_fee_applied_at')->nullable()->after('late_fee_percentage');
            $table->timestamp('last_reminder_sent_at')->nullable()->after('late_fee_applied_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_documents', function (Blueprint $table) {
            $table->dropColumn(['late_fee_amount', 'late_fee_percentage', 'late_fee_applied_at', 'last_reminder_sent_at']);
        });
    }
};
