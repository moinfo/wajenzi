<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillingClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('billing_clients', function (Blueprint $table) {
            $table->id();
            $table->enum('client_type', ['customer', 'supplier', 'both'])->default('customer');
            $table->string('client_code')->unique()->nullable();
            $table->string('company_name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->string('website')->nullable();
            $table->string('tax_identification_number')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('vat_number')->nullable();
            $table->text('billing_address_line1')->nullable();
            $table->text('billing_address_line2')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_postal_code')->nullable();
            $table->string('billing_country')->nullable();
            $table->text('shipping_address_line1')->nullable();
            $table->text('shipping_address_line2')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_postal_code')->nullable();
            $table->string('shipping_country')->nullable();
            $table->boolean('shipping_same_as_billing')->default(true);
            $table->decimal('credit_limit', 20, 2)->default(0);
            $table->enum('payment_terms', ['immediate', 'net_7', 'net_15', 'net_30', 'net_45', 'net_60', 'net_90', 'custom'])->default('net_30');
            $table->integer('custom_payment_days')->nullable();
            $table->string('preferred_currency', 3)->default('TZS');
            $table->decimal('opening_balance', 20, 2)->default(0);
            $table->decimal('current_balance', 20, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('client_type');
            $table->index('company_name');
            $table->index('email');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('billing_clients');
    }
}