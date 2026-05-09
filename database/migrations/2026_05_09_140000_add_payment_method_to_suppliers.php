<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'payment_method')) {
                $table->enum('payment_method', ['BANK', 'MOBILE', 'CASH'])
                    ->nullable()
                    ->after('account_name');
            }
            if (! Schema::hasColumn('suppliers', 'bank_name')) {
                $table->string('bank_name', 60)->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('suppliers', 'bank_account_number')) {
                $table->string('bank_account_number', 40)->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('suppliers', 'mobile_provider')) {
                $table->string('mobile_provider', 40)->nullable()->after('bank_account_number');
            }
            if (! Schema::hasColumn('suppliers', 'mobile_number')) {
                $table->string('mobile_number', 30)->nullable()->after('mobile_provider');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            foreach (['mobile_number', 'mobile_provider', 'bank_account_number', 'bank_name', 'payment_method'] as $col) {
                if (Schema::hasColumn('suppliers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
