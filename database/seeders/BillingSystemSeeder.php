<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BillingClient;
use App\Models\BillingProduct;
use App\Models\BillingTaxRate;
use App\Models\BillingDocument;
use App\Models\BillingDocumentItem;
use App\Models\BillingPayment;

class BillingSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create Tax Rates
        $taxRates = [
            [
                'name' => 'VAT 18%',
                'code' => 'VAT18',
                'rate' => 18.00,
                'type' => 'percentage',
                'description' => 'Standard VAT rate in Tanzania',
                'is_default' => true,
                'is_active' => true
            ],
            [
                'name' => 'VAT 0%',
                'code' => 'VAT0',
                'rate' => 0.00,
                'type' => 'percentage',
                'description' => 'Zero-rated VAT',
                'is_default' => false,
                'is_active' => true
            ]
        ];

        foreach ($taxRates as $taxRate) {
            BillingTaxRate::firstOrCreate(['code' => $taxRate['code']], $taxRate);
        }

        // Create Sample Clients
        BillingClient::firstOrCreate(['company_name' => 'Mkombozi Bank Limited'], [
            'client_type' => 'customer',
            'company_name' => 'Mkombozi Bank Limited',
            'contact_person' => 'John Mwalimu',
            'email' => 'john.mwalimu@mkombozi.co.tz',
            'phone' => '+255 22 211 8800',
            'tax_identification_number' => '101-234-567',
            'billing_address_line1' => 'Samora Avenue',
            'billing_city' => 'Dar es Salaam',
            'billing_country' => 'Tanzania',
            'credit_limit' => 5000000.00,
            'payment_terms' => 'net_30',
            'preferred_currency' => 'TZS',
            'is_active' => true
        ]);

        BillingClient::firstOrCreate(['company_name' => 'Tanzania Cement Company'], [
            'client_type' => 'customer',
            'company_name' => 'Tanzania Cement Company',
            'contact_person' => 'Sarah Hassan',
            'email' => 'sarah@cement.co.tz',
            'phone' => '+255 23 262 0041',
            'tax_identification_number' => '102-345-678',
            'billing_address_line1' => 'Industrial Area',
            'billing_city' => 'Dar es Salaam',
            'billing_country' => 'Tanzania',
            'credit_limit' => 10000000.00,
            'payment_terms' => 'net_15',
            'preferred_currency' => 'TZS',
            'is_active' => true
        ]);

        // Create Sample Products/Services
        BillingProduct::firstOrCreate(['code' => 'CONST-001'], [
            'type' => 'service',
            'code' => 'CONST-001',
            'name' => 'Building Construction Services',
            'description' => 'Complete building construction services',
            'category' => 'Construction Services',
            'unit_of_measure' => 'sqm',
            'unit_price' => 150000.00,
            'purchase_price' => 120000.00,
            'tax_rate_id' => 1,
            'track_inventory' => false,
            'is_active' => true
        ]);

        BillingProduct::firstOrCreate(['code' => 'CEMENT-001'], [
            'type' => 'product',
            'code' => 'CEMENT-001',
            'name' => 'Portland Cement',
            'description' => '50kg bags of Portland cement',
            'category' => 'Building Materials',
            'unit_of_measure' => 'bags',
            'unit_price' => 18000.00,
            'purchase_price' => 15000.00,
            'tax_rate_id' => 1,
            'track_inventory' => true,
            'current_stock' => 500,
            'minimum_stock' => 50,
            'is_active' => true
        ]);

        // Create Sample Invoice
        $client = BillingClient::first();
        $product = BillingProduct::first();
        
        $invoice = BillingDocument::firstOrCreate(['document_number' => 'INV-2025-00001'], [
            'document_type' => 'invoice',
            'document_number' => 'INV-2025-00001',
            'client_id' => $client->id,
            'status' => 'paid',
            'issue_date' => now()->subDays(30),
            'due_date' => now()->subDays(15),
            'payment_terms' => 'net_15',
            'currency_code' => 'TZS',
            'exchange_rate' => 1.0000,
            'notes' => 'Sample construction invoice',
            'terms_conditions' => 'Payment due within 15 days',
            'footer_text' => 'Thank you for your business!',
            'sales_person' => 'Engineer Mwalimu',
            'created_by' => 1,
            'sent_at' => now()->subDays(29),
            'paid_at' => now()->subDays(20)
        ]);

        // Add item to invoice if not already added
        if ($invoice->items()->count() === 0) {
            $invoice->items()->create([
                'item_type' => 'service',
                'product_service_id' => $product->id,
                'item_name' => 'Building Construction Services',
                'description' => 'Foundation and ground floor construction',
                'quantity' => 250.00,
                'unit_of_measure' => 'sqm',
                'unit_price' => 150000.00,
                'tax_percentage' => 18.00,
                'sort_order' => 1
            ]);

            $invoice->calculateTotals();

            // Add payment
            BillingPayment::create([
                'document_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'payment_date' => now()->subDays(20),
                'amount' => $invoice->total_amount,
                'payment_method' => 'bank_transfer',
                'reference_number' => 'TRF-2025-001',
                'notes' => 'Full payment received',
                'status' => 'completed',
                'received_by' => 1
            ]);
        }

        // Create a quote
        BillingDocument::firstOrCreate(['document_number' => 'QT-2025-00001'], [
            'document_type' => 'quote',
            'document_number' => 'QT-2025-00001',
            'client_id' => $client->id,
            'status' => 'sent',
            'issue_date' => now()->subDays(5),
            'valid_until_date' => now()->addDays(25),
            'payment_terms' => 'net_30',
            'currency_code' => 'TZS',
            'exchange_rate' => 1.0000,
            'notes' => 'Quote for new construction project',
            'terms_conditions' => 'This quote is valid for 30 days',
            'footer_text' => 'We look forward to working with you!',
            'sales_person' => 'Sales Manager',
            'created_by' => 1,
            'sent_at' => now()->subDays(4)
        ]);
    }
}