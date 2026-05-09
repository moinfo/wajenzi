<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('service_design_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_design_id')->constrained('project_service_designs')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('project_clients')->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_design_feedbacks');
    }
};
