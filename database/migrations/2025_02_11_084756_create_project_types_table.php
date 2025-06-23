<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateProjectTypesTable extends Migration
{
    public function up()
    {
        Schema::create('project_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Add foreign key to projects table if project_type_id doesn't exist
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'project_type_id')) {
                // First drop the existing project_type column if it exists
                if (Schema::hasColumn('projects', 'project_type')) {
                    $table->dropColumn('project_type');
                }

                // Add the new foreign key column
                $table->foreignId('project_type_id')->nullable()->after('project_name')
                    ->constrained('project_types')->nullOnDelete();
            }
        });

        $types = [
            [
                'name' => 'Residential',
                'description' => 'Residential construction projects',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Commercial',
                'description' => 'Commercial building projects',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Industrial',
                'description' => 'Industrial construction projects',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Infrastructure',
                'description' => 'Infrastructure development projects',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        DB::table('project_types')->insert($types);
    }

    public function down()
    {
        // Remove foreign key from projects table if it exists
        if (Schema::hasColumn('projects', 'project_type_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropForeign(['project_type_id']);
                $table->dropColumn('project_type_id');
                // Restore the original project_type column
                $table->string('project_type', 50)->nullable();
            });
        }

        Schema::dropIfExists('project_types');
    }
}
