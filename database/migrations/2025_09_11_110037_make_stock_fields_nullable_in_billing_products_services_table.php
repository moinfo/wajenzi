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
        Schema::table('billing_products_services', function (Blueprint $table) {
            // Make stock fields nullable for services
            $table->decimal('current_stock', 15, 4)->nullable()->default(0)->change();
            $table->decimal('minimum_stock', 15, 4)->nullable()->default(0)->change();
            $table->decimal('reorder_level', 15, 4)->nullable()->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_products_services', function (Blueprint $table) {
            // Revert stock fields to not nullable
            $table->decimal('current_stock', 15, 4)->default(0)->change();
            $table->decimal('minimum_stock', 15, 4)->default(0)->change();
            $table->decimal('reorder_level', 15, 4)->default(0)->change();
        });
    }
};
