<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link a site visit to the real billing invoice (BillingDocument) generated at
 * the billing stage, so the workflow reuses the full billing module (line items,
 * due dates, terms, payments, PDF, email) instead of a lightweight on-row invoice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_site_visits', function (Blueprint $table) {
            if (!Schema::hasColumn('project_site_visits', 'billing_document_id')) {
                $table->unsignedBigInteger('billing_document_id')->nullable()->after('invoice_number');
                $table->foreign('billing_document_id')->references('id')->on('billing_documents')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_site_visits', function (Blueprint $table) {
            if (Schema::hasColumn('project_site_visits', 'billing_document_id')) {
                $table->dropForeign(['billing_document_id']);
                $table->dropColumn('billing_document_id');
            }
        });
    }
};
