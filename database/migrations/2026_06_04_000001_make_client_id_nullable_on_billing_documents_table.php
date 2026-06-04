<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Allow billing documents (e.g. invoices) to be created against a lead
     * without an associated client.
     */
    public function up(): void
    {
        Schema::table('billing_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable()->change();
        });

        Schema::table('billing_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable(false)->change();
        });

        Schema::table('billing_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('client_id')->nullable(false)->change();
        });
    }
};
