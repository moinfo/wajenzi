<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('budget', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('whatsapp_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('stage', 30)->default('lead');
            $table->string('source', 30)->default('whatsapp_ad');
            $table->foreignId('campaign_id')->nullable()->constrained('whatsapp_ad_campaigns')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('project_clients')->nullOnDelete();
            $table->date('next_followup_date')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_important')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('whatsapp_contact_services', function (Blueprint $table) {
            $table->foreignId('whatsapp_contact_id')->constrained('whatsapp_contacts')->cascadeOnDelete();
            $table->foreignId('field_marketing_service_id')->constrained('field_marketing_services')->cascadeOnDelete();
            $table->primary(['whatsapp_contact_id', 'field_marketing_service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_contact_services');
        Schema::dropIfExists('whatsapp_contacts');
        Schema::dropIfExists('whatsapp_ad_campaigns');
    }
};
