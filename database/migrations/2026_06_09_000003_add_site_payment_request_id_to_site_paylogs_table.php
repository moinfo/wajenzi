<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Re-parent paylog rows under a payment request. They become the request's
     * line items. Nullable so pre-existing (legacy) rows remain valid and keep
     * showing in the daily/monthly reports without an approval trail.
     */
    public function up(): void
    {
        Schema::table('site_paylogs', function (Blueprint $table) {
            $table->foreignId('site_payment_request_id')
                ->nullable()
                ->after('site_id')
                ->constrained('site_payment_requests')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('site_paylogs', function (Blueprint $table) {
            $table->dropForeign(['site_payment_request_id']);
            $table->dropColumn('site_payment_request_id');
        });
    }
};
