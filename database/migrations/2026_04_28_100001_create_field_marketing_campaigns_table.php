<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('campaign_number')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('campaign_type', ['outdoor', 'event', 'canvassing', 'demo', 'referral', 'other'])->default('outdoor');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('budget', 15, 2)->default(0);
            $table->integer('target_leads')->default(0);
            $table->foreignId('territory_id')->nullable()->constrained('field_territories')->nullOnDelete();
            $table->foreignId('lead_source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_marketing_campaigns');
    }
};
