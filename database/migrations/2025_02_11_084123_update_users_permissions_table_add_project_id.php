<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsersPermissionsTableAddProjectId extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // First, create a new temporary table with desired structure
        Schema::create('users_permissions_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'permission_id', 'project_id'], 'unique_user_permission');
        });

        // Copy data from old table to new table
        DB::statement('INSERT INTO users_permissions_new (user_id, permission_id, created_at, updated_at)
                      SELECT user_id, permission_id, created_at, updated_at FROM users_permissions');

        // Drop the old table
        Schema::dropIfExists('users_permissions');

        // Rename new table to original name
        Schema::rename('users_permissions_new', 'users_permissions');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // Create temporary table with old structure
        Schema::create('users_permissions_old', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->timestamps();
            $table->primary(['user_id', 'permission_id']);
        });

        // Copy data back
        DB::statement('INSERT INTO users_permissions_old (user_id, permission_id, created_at, updated_at)
                      SELECT user_id, permission_id, created_at, updated_at FROM users_permissions');

        // Drop the new table
        Schema::dropIfExists('users_permissions');

        // Rename old structure table to original name
        Schema::rename('users_permissions_old', 'users_permissions');
    }
}
