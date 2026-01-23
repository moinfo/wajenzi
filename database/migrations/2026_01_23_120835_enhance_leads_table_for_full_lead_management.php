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
        // Service Interested
        if (!Schema::hasColumn('leads', 'service_interested_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->unsignedBigInteger('service_interested_id')->nullable()->after('lead_source_id');
            });
        }

        // Location details
        if (!Schema::hasColumn('leads', 'site_location')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->string('site_location')->nullable()->after('address');
            });
        }
        if (!Schema::hasColumn('leads', 'city')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->string('city')->nullable()->after('site_location');
            });
        }

        // Financial
        if (!Schema::hasColumn('leads', 'estimated_value')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->decimal('estimated_value', 15, 2)->nullable()->after('city');
            });
        }

        // Lead Status
        if (!Schema::hasColumn('leads', 'lead_status_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->unsignedBigInteger('lead_status_id')->nullable()->after('estimated_value');
            });
        }

        // Salesperson
        if (!Schema::hasColumn('leads', 'salesperson_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->unsignedBigInteger('salesperson_id')->nullable()->after('lead_status_id');
            });
        }

        // Notes
        if (!Schema::hasColumn('leads', 'notes')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('salesperson_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'lead_number',
                'lead_date',
                'lead_source_id',
                'service_interested_id',
                'site_location',
                'city',
                'estimated_value',
                'lead_status_id',
                'salesperson_id',
                'notes'
            ]);
        });
    }
};
