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
        Schema::create('billing_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->enum('reminder_type', ['before_due', 'overdue', 'late_fee']);
            $table->integer('days_before_due')->nullable()->comment('For before_due reminders');
            $table->integer('days_overdue')->nullable()->comment('For overdue reminders');
            $table->string('recipient_email');
            $table->string('cc_emails')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->enum('status', ['sent', 'failed']);
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();
            
            $table->index(['document_id', 'reminder_type']);
            $table->index(['sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_reminder_logs');
    }
};
