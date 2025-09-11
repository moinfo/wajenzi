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
        Schema::table('billing_payments', function (Blueprint $table) {
            $table->string('received_by_signature')->nullable()->after('received_by');
            $table->timestamp('receipt_signed_at')->nullable()->after('received_by_signature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_payments', function (Blueprint $table) {
            $table->dropColumn(['received_by_signature', 'receipt_signed_at']);
        });
    }
};