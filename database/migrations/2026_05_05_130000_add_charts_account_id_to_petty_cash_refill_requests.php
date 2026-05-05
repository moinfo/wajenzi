<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('petty_cash_refill_requests', 'charts_account_id')) {
            Schema::table('petty_cash_refill_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('charts_account_id')->nullable()->after('document_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('petty_cash_refill_requests', 'charts_account_id')) {
            Schema::table('petty_cash_refill_requests', function (Blueprint $table) {
                $table->dropColumn('charts_account_id');
            });
        }
    }
};
