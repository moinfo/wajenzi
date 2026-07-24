<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a site to its project (a project can have many sites). Nullable so
 * existing standalone sites and sites not tied to a project remain valid; the
 * FK nulls out rather than blocking if a project is ever deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->foreignId('project_id')
                ->nullable()
                ->after('id')
                ->constrained('projects')
                ->nullOnDelete();
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
