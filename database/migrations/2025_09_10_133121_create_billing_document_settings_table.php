<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingDocumentSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('billing_document_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->unique();
            $table->text('setting_value')->nullable();
            $table->string('setting_type')->default('text');
            $table->text('description')->nullable();
            $table->timestamps();
        });
        
        // Insert default settings
        \DB::table('billing_document_settings')->insert([
            [
                'setting_key' => 'invoice_prefix',
                'setting_value' => 'INV-',
                'setting_type' => 'text',
                'description' => 'Prefix for invoice numbers',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'quote_prefix',
                'setting_value' => 'QT-',
                'setting_type' => 'text',
                'description' => 'Prefix for quotation numbers',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'proforma_prefix',
                'setting_value' => 'PRO-',
                'setting_type' => 'text',
                'description' => 'Prefix for proforma invoice numbers',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'credit_note_prefix',
                'setting_value' => 'CN-',
                'setting_type' => 'text',
                'description' => 'Prefix for credit note numbers',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'receipt_prefix',
                'setting_value' => 'RCP-',
                'setting_type' => 'text',
                'description' => 'Prefix for receipt numbers',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'payment_prefix',
                'setting_value' => 'PAY-',
                'setting_type' => 'text',
                'description' => 'Prefix for payment numbers',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'number_format',
                'setting_value' => 'YYYY-00000',
                'setting_type' => 'text',
                'description' => 'Number format (YYYY for year, 00000 for sequence)',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'default_payment_terms',
                'setting_value' => 'net_30',
                'setting_type' => 'text',
                'description' => 'Default payment terms for new documents',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'default_currency',
                'setting_value' => 'TZS',
                'setting_type' => 'text',
                'description' => 'Default currency for new documents',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'default_tax_rate',
                'setting_value' => '18',
                'setting_type' => 'number',
                'description' => 'Default VAT/Tax rate percentage',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'invoice_terms',
                'setting_value' => 'Payment is due within the specified payment terms. Late payments may incur additional charges.',
                'setting_type' => 'textarea',
                'description' => 'Default terms and conditions for invoices',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'invoice_footer',
                'setting_value' => 'Thank you for your business!',
                'setting_type' => 'textarea',
                'description' => 'Default footer text for invoices',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('billing_document_settings');
    }
}