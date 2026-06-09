<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Site Payment Request — the approvable header that groups a site/day's
     * payment lines into one document flowing through:
     *   Site Supervisor/Engineer (initiates) → Procurement (verify)
     *   → Managing Director (approve) → Finance/Accountant (record payment).
     *
     * RingleSoft drives the Procurement→MD approval chain; the Finance step is a
     * post-approval "record payment" action (mirrors the Purchase-order flow).
     */
    public function up(): void
    {
        Schema::create('site_payment_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();          // PAY-YYYY-0001
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->date('payment_date');
            $table->decimal('total_amount', 16, 2)->default(0);   // cached sum of line items
            $table->string('status')->default('PENDING');        // PENDING | APPROVED | PAID | REJECTED
            $table->string('payment_reference')->nullable();     // Finance: bank/mobile ref
            $table->string('payment_slip')->nullable();          // Finance: proof of payment
            $table->text('payment_note')->nullable();
            $table->date('paid_date')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['site_id', 'payment_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_payment_requests');
    }
};
