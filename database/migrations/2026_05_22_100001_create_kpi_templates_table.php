<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per role-specific KPI form (Architect, Accountant, QS, ...). Each
 * template is the parent of sections (Section A "General" + Section B "Departmental")
 * which in turn own kpi_items.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('kpi_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();          // 'architect','accountant',...
            $table->string('name', 120);                   // 'Architect Performance Review'
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete(); // Spatie role this template applies to
            $table->enum('frequency', ['monthly', 'quarterly', 'biannual', 'annual'])->default('monthly');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_templates');
    }
};
