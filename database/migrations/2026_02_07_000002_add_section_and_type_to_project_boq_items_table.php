<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_boq_items', function (Blueprint $table) {
            $table->foreignId('section_id')->nullable()->after('boq_id')
                ->constrained('project_boq_sections')->onDelete('set null');
            $table->enum('item_type', ['material', 'labour'])->default('material')->after('description');
            $table->integer('sort_order')->default(0)->after('total_price');
        });
    }

    public function down(): void
    {
        Schema::table('project_boq_items', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
            $table->dropColumn(['section_id', 'item_type', 'sort_order']);
        });
    }
};
