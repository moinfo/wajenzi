<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a Spatie role_id column to approval_levels so legacy approvals can
 * resolve approvers via Spatie roles instead of the unused user_groups table.
 *
 * user_group_id is kept (nullable now in practice) as a fallback for any
 * approval_levels not yet re-saved with a role_id.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('approval_levels', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id')->nullable()->after('user_group_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('approval_levels', function (Blueprint $table) {
            $table->dropIndex(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
