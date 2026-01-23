<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string');
            $table->string('group')->default('general');
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default payment terms settings
        $settings = [
            [
                'key' => 'payment_due_days',
                'value' => '7',
                'type' => 'integer',
                'group' => 'payment_terms',
                'label' => 'Payment Due Days',
                'description' => 'Number of days payment is due from invoice date',
            ],
            [
                'key' => 'deposit_percentage',
                'value' => '50',
                'type' => 'integer',
                'group' => 'payment_terms',
                'label' => 'Deposit Percentage',
                'description' => 'Initial deposit percentage before work commences',
            ],
            [
                'key' => 'second_payment_percentage',
                'value' => '30',
                'type' => 'integer',
                'group' => 'payment_terms',
                'label' => 'Second Payment Percentage',
                'description' => 'Second payment percentage after second draft submission',
            ],
            [
                'key' => 'final_payment_percentage',
                'value' => '20',
                'type' => 'integer',
                'group' => 'payment_terms',
                'label' => 'Final Payment Percentage',
                'description' => 'Final payment percentage due upon finalization',
            ],
            [
                'key' => 'invoice_validity_days',
                'value' => '7',
                'type' => 'integer',
                'group' => 'payment_terms',
                'label' => 'Invoice Validity Days',
                'description' => 'Number of days the invoice is valid from issue date',
            ],
            [
                'key' => 'architectural_hard_copies',
                'value' => '3',
                'type' => 'integer',
                'group' => 'delivery',
                'label' => 'Architectural Hard Copies',
                'description' => 'Number of stamped hard copy files for architectural drawings',
            ],
            [
                'key' => 'structural_hard_copies',
                'value' => '2',
                'type' => 'integer',
                'group' => 'delivery',
                'label' => 'Structural Hard Copies',
                'description' => 'Number of hard copy files for structural design drawings',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('invoice_settings')->insert(array_merge($setting, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_settings');
    }
};
