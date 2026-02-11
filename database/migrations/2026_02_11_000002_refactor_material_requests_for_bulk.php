<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Migrate existing item data into the new items table
        $requests = DB::table('project_material_requests')
            ->whereNotNull('boq_item_id')
            ->get();

        foreach ($requests as $req) {
            DB::table('project_material_request_items')->insert([
                'material_request_id' => $req->id,
                'boq_item_id' => $req->boq_item_id,
                'quantity_requested' => $req->quantity_requested ?? 0,
                'quantity_approved' => $req->quantity_approved,
                'unit' => $req->unit,
                'sort_order' => 0,
                'created_at' => $req->created_at,
                'updated_at' => $req->updated_at,
            ]);
        }

        // Step 2: Drop item-specific columns from parent table
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::table('project_material_requests', function (Blueprint $table) {
            if (Schema::hasColumn('project_material_requests', 'boq_item_id')) {
                $table->dropForeign(['boq_item_id']);
                $table->dropColumn('boq_item_id');
            }
            if (Schema::hasColumn('project_material_requests', 'construction_phase_id')) {
                $table->dropForeign(['construction_phase_id']);
                $table->dropColumn('construction_phase_id');
            }
        });

        Schema::table('project_material_requests', function (Blueprint $table) {
            $columns = ['quantity_requested', 'quantity_approved', 'unit'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('project_material_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Re-add columns
        Schema::table('project_material_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('boq_item_id')->nullable()->after('project_id');
            $table->unsignedBigInteger('construction_phase_id')->nullable()->after('boq_item_id');
            $table->decimal('quantity_requested', 10, 2)->default(0)->after('status');
            $table->decimal('quantity_approved', 10, 2)->nullable()->after('quantity_requested');
            $table->string('unit', 20)->nullable()->after('quantity_approved');

            $table->foreign('boq_item_id')->references('id')->on('project_boq_items')->nullOnDelete();
            $table->foreign('construction_phase_id')->references('id')->on('project_construction_phases')->nullOnDelete();
        });

        // Migrate data back from items to parent
        $items = DB::table('project_material_request_items')->get();
        foreach ($items as $item) {
            DB::table('project_material_requests')
                ->where('id', $item->material_request_id)
                ->update([
                    'boq_item_id' => $item->boq_item_id,
                    'quantity_requested' => $item->quantity_requested,
                    'quantity_approved' => $item->quantity_approved,
                    'unit' => $item->unit,
                ]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
