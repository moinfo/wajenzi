<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_contact_labels', function (Blueprint $table) {
            $table->unsignedBigInteger('contact_id');
            $table->string('label', 30);
            $table->primary(['contact_id', 'label']);
            $table->foreign('contact_id')->references('id')->on('whatsapp_contacts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_contact_labels');
    }
};
