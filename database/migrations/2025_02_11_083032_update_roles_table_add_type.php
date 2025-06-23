<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateRolesTableAddType extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('type')->default('system')->after('description');
        });

        // Seed default roles
        $roles = [
            [
                'name' => 'admin',
                'description' => 'System Administrator',
                'type' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'project_manager',
                'description' => 'Project Manager',
                'type' => 'project',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'site_supervisor',
                'description' => 'Site Supervisor',
                'type' => 'project',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'designer',
                'description' => 'Project Designer',
                'type' => 'project',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('roles')->insert($roles);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
