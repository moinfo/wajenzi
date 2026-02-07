<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Main template table
        Schema::create('project_boq_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['material', 'labour', 'combined'])->default('combined');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->unsignedBigInteger('source_boq_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('source_boq_id')->references('id')->on('project_boqs')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        // Template sections (mirrors project_boq_sections)
        Schema::create('project_boq_template_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('project_boq_templates')->cascadeOnDelete();
            $table->foreign('parent_id')->references('id')->on('project_boq_template_sections')->nullOnDelete();
            $table->index(['template_id', 'parent_id', 'sort_order'], 'tpl_sections_tpl_parent_sort_idx');
        });

        // Template items (mirrors project_boq_items, without procurement fields)
        Schema::create('project_boq_template_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('section_id')->nullable();
            $table->string('description');
            $table->enum('item_type', ['material', 'labour'])->default('material');
            $table->string('specification')->nullable();
            $table->decimal('quantity', 15, 2)->default(0);
            $table->string('unit', 50)->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('project_boq_templates')->cascadeOnDelete();
            $table->foreign('section_id')->references('id')->on('project_boq_template_sections')->nullOnDelete();
            $table->index(['template_id', 'section_id'], 'tpl_items_tpl_section_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_boq_template_items');
        Schema::dropIfExists('project_boq_template_sections');
        Schema::dropIfExists('project_boq_templates');
    }
};