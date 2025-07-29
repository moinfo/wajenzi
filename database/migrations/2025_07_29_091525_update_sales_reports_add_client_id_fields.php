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
        // Add client_id to sales_lead_followups table
        Schema::table('sales_lead_followups', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->constrained('project_clients')->after('lead_name');
        });
        
        // Add client_id to sales_client_concerns table  
        Schema::table('sales_client_concerns', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->constrained('project_clients')->after('client_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_lead_followups', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });
        
        Schema::table('sales_client_concerns', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });
    }
};
