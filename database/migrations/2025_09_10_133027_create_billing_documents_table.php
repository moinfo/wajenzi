<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('billing_documents', function (Blueprint $table) {
            $table->id();
            $table->enum('document_type', ['quote', 'proforma', 'invoice', 'credit_note', 'debit_note', 'purchase_order', 'delivery_note', 'receipt']);
            $table->string('document_number')->unique();
            $table->string('reference_number')->nullable();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->foreignId('parent_document_id')->nullable()->constrained('billing_documents')->onDelete('set null');
            $table->enum('status', ['draft', 'pending', 'sent', 'viewed', 'accepted', 'rejected', 'partial_paid', 'paid', 'overdue', 'cancelled', 'void'])->default('draft');
            $table->date('issue_date');
            $table->date('valid_until_date')->nullable();
            $table->date('due_date')->nullable();
            $table->enum('payment_terms', ['immediate', 'net_7', 'net_15', 'net_30', 'net_45', 'net_60', 'net_90', 'custom'])->default('net_30');
            $table->integer('custom_payment_days')->nullable();
            $table->string('currency_code', 3)->default('TZS');
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);
            $table->decimal('subtotal_amount', 20, 2)->default(0);
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('shipping_amount', 20, 2)->default(0);
            $table->decimal('total_amount', 20, 2)->default(0);
            $table->decimal('paid_amount', 20, 2)->default(0);
            $table->decimal('balance_amount', 20, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->text('footer_text')->nullable();
            $table->string('po_number')->nullable();
            $table->string('sales_person')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->index('document_type');
            $table->index('status');
            $table->index('client_id');
            $table->index('issue_date');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('billing_documents');
    }
}