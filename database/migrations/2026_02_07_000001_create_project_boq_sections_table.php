<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_boq_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boq_id')->constrained('project_boqs')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('project_boq_sections')->onDelete('set null');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('boq_id');
            $table->index('parent_id');
            $table->index(['boq_id', 'parent_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_boq_sections');
    }
};
