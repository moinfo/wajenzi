<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relate a site visit to the Site Visit Calculator's location entity
 * (site_visit_locations), so the billing stage can derive the invoice amount
 * from the location's cost presets × the number of visit days.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_site_visits', function (Blueprint $table) {
            if (!Schema::hasColumn('project_site_visits', 'site_visit_location_id')) {
                $table->unsignedBigInteger('site_visit_location_id')->nullable()->after('location');
                $table->foreign('site_visit_location_id')
                    ->references('id')->on('site_visit_locations')->nullOnDelete();
            }
            if (!Schema::hasColumn('project_site_visits', 'visit_days')) {
                $table->unsignedInteger('visit_days')->default(1)->after('site_visit_location_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_site_visits', function (Blueprint $table) {
            if (Schema::hasColumn('project_site_visits', 'site_visit_location_id')) {
                $table->dropForeign(['site_visit_location_id']);
                $table->dropColumn('site_visit_location_id');
            }
            if (Schema::hasColumn('project_site_visits', 'visit_days')) {
                $table->dropColumn('visit_days');
            }
        });
    }
};
