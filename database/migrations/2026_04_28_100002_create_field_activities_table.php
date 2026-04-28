<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_activities', function (Blueprint $table) {
            $table->id();
            $table->string('activity_number')->unique();
            $table->foreignId('campaign_id')->nullable()->constrained('field_marketing_campaigns')->nullOnDelete();
            $table->foreignId('territory_id')->nullable()->constrained('field_territories')->nullOnDelete();
            $table->enum('activity_type', ['visit', 'event', 'demo', 'canvassing', 'referral', 'phone_call', 'other'])->default('visit');
            $table->foreignId('agent_id')->constrained('users');
            $table->date('activity_date');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->enum('outcome', ['interested', 'not_interested', 'follow_up', 'converted', 'no_contact', 'pending'])->default('pending');
            $table->integer('leads_count')->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['planned', 'completed', 'cancelled'])->default('planned');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_activities');
    }
};
