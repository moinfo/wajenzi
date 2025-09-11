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
        Schema::create('billing_document_emails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->string('document_type');
            $table->string('recipient_email');
            $table->text('cc_emails')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->boolean('has_attachment')->default(true);
            $table->string('attachment_filename')->nullable();
            $table->enum('status', ['sent', 'failed'])->default('sent');
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('sent_by');
            $table->timestamp('sent_at');
            $table->timestamps();
            
            $table->index(['document_id', 'document_type']);
            $table->index('recipient_email');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_document_emails');
    }
};
