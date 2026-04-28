<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_contact_calls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->date('call_date');
            $table->string('outcome', 30);
            $table->date('next_followup_date')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->foreign('contact_id')->references('id')->on('whatsapp_contacts')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_contact_calls');
    }
};
