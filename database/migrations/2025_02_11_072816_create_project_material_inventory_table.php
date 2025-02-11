<?php

// create_project_material_inventory_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectMaterialInventoryTable extends Migration
{
    public function up()
    {
        Schema::create('project_material_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->onDelete('cascade');
            $table->foreignId('material_id')->constrained('project_materials');
            $table->decimal('quantity', 10, 2);
            $table->timestamp('last_updated')->useCurrent();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_material_inventory');
    }
}

