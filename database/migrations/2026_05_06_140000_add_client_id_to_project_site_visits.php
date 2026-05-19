<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing FK so we can make project_id nullable
        Schema::table('project_site_visits', function (Blueprint $table) {
            $table->dropForeign('project_site_visits_project_id_foreign');
        });

        DB::statement('ALTER TABLE project_site_visits MODIFY project_id BIGINT UNSIGNED NULL');

        Schema::table('project_site_visits', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');

            if (!Schema::hasColumn('project_site_visits', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->after('project_id');
                $table->foreign('client_id')->references('id')->on('project_clients')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_site_visits', function (Blueprint $table) {
            if (Schema::hasColumn('project_site_visits', 'client_id')) {
                $table->dropForeign(['client_id']);
                $table->dropColumn('client_id');
            }
        });
    }
};
