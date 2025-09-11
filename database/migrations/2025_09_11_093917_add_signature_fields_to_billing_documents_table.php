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
            $table->string('created_by_signature')->nullable()->after('created_by');
            $table->timestamp('signed_at')->nullable()->after('created_by_signature');
            $table->string('approved_by_signature')->nullable()->after('approved_by');
            $table->timestamp('approved_signed_at')->nullable()->after('approved_by_signature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_documents', function (Blueprint $table) {
            $table->dropColumn(['created_by_signature', 'signed_at', 'approved_by_signature', 'approved_signed_at']);
        });
    }
};