<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_interesteds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Create permissions for Service Interested CRUD
        $permissions = [
            ['name' => 'Service Interesteds', 'guard_name' => 'web', 'permission_type' => 'SETTING', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Add Service Interested', 'guard_name' => 'web', 'permission_type' => 'CRUD', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Edit Service Interested', 'guard_name' => 'web', 'permission_type' => 'CRUD', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Delete Service Interested', 'guard_name' => 'web', 'permission_type' => 'CRUD', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('permissions')->insert($permissions);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove permissions
        DB::table('permissions')->whereIn('name', [
            'Service Interesteds',
            'Add Service Interested',
            'Edit Service Interested',
            'Delete Service Interested',
        ])->delete();

        Schema::dropIfExists('service_interesteds');
    }
};
