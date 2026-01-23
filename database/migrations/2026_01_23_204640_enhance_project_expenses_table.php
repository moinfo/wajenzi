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
        // Add columns if they don't exist
        if (!Schema::hasColumn('project_expenses', 'cost_category_id')) {
            Schema::table('project_expenses', function (Blueprint $table) {
                $table->unsignedBigInteger('cost_category_id')->nullable()->after('project_id');
            });
        }

        // Add foreign key - use try/catch in case it already exists
        try {
            Schema::table('project_expenses', function (Blueprint $table) {
                $table->foreign('cost_category_id')->references('id')->on('cost_categories')->onDelete('set null');
            });
        } catch (\Exception $e) {
            // Foreign key may already exist, ignore
        }

        if (!Schema::hasColumn('project_expenses', 'remarks')) {
            Schema::table('project_expenses', function (Blueprint $table) {
                $table->text('remarks')->nullable()->after('description');
            });
        }

        // Migrate existing category data to cost_categories if they don't exist
        if (Schema::hasColumn('project_expenses', 'category')) {
            $existingCategories = DB::table('project_expenses')->distinct()->pluck('category');
            foreach ($existingCategories as $categoryName) {
                if ($categoryName) {
                    $exists = DB::table('cost_categories')->where('name', $categoryName)->exists();
                    if (!$exists) {
                        DB::table('cost_categories')->insert([
                            'name' => $categoryName,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    // Update project_expenses to link to the cost_category
                    $categoryId = DB::table('cost_categories')->where('name', $categoryName)->value('id');
                    DB::table('project_expenses')
                        ->where('category', $categoryName)
                        ->update(['cost_category_id' => $categoryId]);
                }
            }

            // Remove the old category column after data migration
            Schema::table('project_expenses', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }

        // Create permissions for Project Cost CRUD
        $permissions = [
            ['name' => 'Project Costs', 'guard_name' => 'web', 'permission_type' => 'MENU', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Add Project Cost', 'guard_name' => 'web', 'permission_type' => 'CRUD', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Edit Project Cost', 'guard_name' => 'web', 'permission_type' => 'CRUD', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Delete Project Cost', 'guard_name' => 'web', 'permission_type' => 'CRUD', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($permissions as $permission) {
            $exists = DB::table('permissions')->where('name', $permission['name'])->exists();
            if (!$exists) {
                DB::table('permissions')->insert($permission);
            }
        }

        // Assign permissions to System Administrator role (role_id = 1)
        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['Project Costs', 'Add Project Cost', 'Edit Project Cost', 'Delete Project Cost'])
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            $exists = DB::table('role_has_permissions')
                ->where('permission_id', $permissionId)
                ->where('role_id', 1)
                ->exists();
            if (!$exists) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => 1
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_expenses', function (Blueprint $table) {
            // Re-add category column
            $table->string('category', 50)->nullable()->after('project_id');
        });

        // Migrate data back
        $expenses = DB::table('project_expenses')
            ->join('cost_categories', 'project_expenses.cost_category_id', '=', 'cost_categories.id')
            ->select('project_expenses.id', 'cost_categories.name')
            ->get();

        foreach ($expenses as $expense) {
            DB::table('project_expenses')
                ->where('id', $expense->id)
                ->update(['category' => $expense->name]);
        }

        Schema::table('project_expenses', function (Blueprint $table) {
            $table->dropForeign(['cost_category_id']);
            $table->dropColumn('cost_category_id');
            $table->dropColumn('remarks');
        });

        // Remove role permissions
        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['Project Costs', 'Add Project Cost', 'Edit Project Cost', 'Delete Project Cost'])
            ->pluck('id');

        DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();

        // Remove permissions
        DB::table('permissions')->whereIn('name', [
            'Project Costs', 'Add Project Cost', 'Edit Project Cost', 'Delete Project Cost',
        ])->delete();
    }
};
