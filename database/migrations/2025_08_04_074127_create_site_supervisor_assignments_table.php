<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('site_supervisor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->date('assigned_from');
            $table->date('assigned_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('assigned_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['site_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
            $table->index(['assigned_from', 'assigned_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_supervisor_assignments');
    }
};