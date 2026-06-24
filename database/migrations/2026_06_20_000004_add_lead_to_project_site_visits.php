<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allow a site visit to be linked to a CRM Lead (in addition to a Project or a
 * standalone Client). A lead-linked visit can resolve to the Survey schedule via
 * project_schedules.lead_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_site_visits', function (Blueprint $table) {
            if (!Schema::hasColumn('project_site_visits', 'lead_id')) {
                $table->unsignedBigInteger('lead_id')->nullable()->after('client_id');
                $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_site_visits', function (Blueprint $table) {
            if (Schema::hasColumn('project_site_visits', 'lead_id')) {
                $table->dropForeign(['lead_id']);
                $table->dropColumn('lead_id');
            }
        });
    }
};
