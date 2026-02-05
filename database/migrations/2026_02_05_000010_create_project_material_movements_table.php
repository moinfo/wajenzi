<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        Schema::create('project_material_movements', function (Blueprint $table) {
            $table->id();
            $table->string('movement_number', 50);
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('boq_item_id')->nullable();
            $table->unsignedBigInteger('inventory_id')->nullable();

            // Movement type and quantity
            $table->enum('movement_type', ['received', 'issued', 'adjustment', 'returned', 'transfer']);
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 20)->nullable();

            // Reference to source document (polymorphic)
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            // Movement details
            $table->date('movement_date');
            $table->text('notes')->nullable();
            $table->string('location')->nullable();

            // Personnel
            $table->unsignedBigInteger('performed_by');
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();

            // Running balance after this movement
            $table->decimal('balance_after', 10, 2)->nullable();

            $table->timestamps();

            // Indexes
            $table->index('movement_number');
            $table->index('movement_type');
            $table->index(['project_id', 'boq_item_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('movement_date');
        });

        Schema::table('project_material_movements', function (Blueprint $table) {
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('boq_item_id')->references('id')->on('project_boq_items')->nullOnDelete();
            $table->foreign('inventory_id')->references('id')->on('project_material_inventory')->nullOnDelete();
            $table->foreign('performed_by')->references('id')->on('users');
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_material_movements');
    }
};
