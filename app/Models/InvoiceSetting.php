<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class InvoiceSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'label', 'description'];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        $setting = Cache::remember("invoice_setting_{$key}", 3600, function () use ($key) {
            return self::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget("invoice_setting_{$key}");
    }

    /**
     * Cast value based on type
     */
    protected static function castValue($value, string $type)
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            'float' => (float) $value,
            default => $value,
        };
    }

    /**
     * Get all payment terms settings
     */
    public static function getPaymentTerms(): array
    {
        return [
            'payment_due_days' => self::get('payment_due_days', 7),
            'deposit_percentage' => self::get('deposit_percentage', 50),
            'second_payment_percentage' => self::get('second_payment_percentage', 30),
            'final_payment_percentage' => self::get('final_payment_percentage', 20),
            'invoice_validity_days' => self::get('invoice_validity_days', 7),
            'architectural_hard_copies' => self::get('architectural_hard_copies', 3),
            'structural_hard_copies' => self::get('structural_hard_copies', 2),
        ];
    }

    /**
     * Get default terms & conditions HTML for new invoices
     */
    public static function getDefaultTermsHtml(): string
    {
        $t = self::getPaymentTerms();

        return '<p><strong>1. Payment Terms.</strong><br>'
            . '&nbsp;&nbsp;&nbsp;&bull; Payment is due within ' . $t['payment_due_days'] . ' days from the invoice date. Design work will commence once the ' . $t['deposit_percentage'] . '% deposit has been confirmed. A second payment of ' . $t['second_payment_percentage'] . '% will be made after the second draft submission, with the remaining ' . $t['final_payment_percentage'] . '% due upon finalization.</p>'
            . '<p><strong>2. Project Deliverables Changes &amp; Revisions.</strong><br>'
            . '&nbsp;&nbsp;&nbsp;<strong>I. The client will be issued 2D design.</strong><br>'
            . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; 2D 1st Draft - The client will review the 2D design and confirm their requirements. If changes are needed, should be submitted and rectified at this stage.<br>'
            . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; 2D Final Draft – All final changes must be identified and submitted. Any additional changes beyond this stage will incur extra charges.<br>'
            . '&nbsp;&nbsp;&nbsp;<strong>II. The client will be issued 3D design.</strong><br>'
            . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; 3D 1st Draft - The client will review the 3D design and confirm their requirements. If changes are needed, should be submitted and rectified at this stage.<br>'
            . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; 3D Final Draft – All final 3D changes must be identified and submitted. Any additional changes beyond this stage will incur extra charges.<br>'
            . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&bull; The Completed Design will be submitted after all revisions have been incorporated and will be provided as stamped hard copies in ' . $t['architectural_hard_copies'] . ' files for Architectural drawings and ' . $t['structural_hard_copies'] . ' files for Structural design drawings.</p>'
            . '<p><strong>3. Validity.</strong><br>'
            . '&nbsp;&nbsp;&nbsp;&bull; This invoice is valid for ' . $t['invoice_validity_days'] . ' days from the date of issue. After expiration, prices and terms may be subject to review.</p>'
            . '<p><strong>4. Taxes &amp; Statutory Deductions.</strong><br>'
            . '&nbsp;&nbsp;&nbsp;&bull; All prices are Tax inclusive.</p>'
            . '<p><strong>5. Ownership of Work.</strong><br>'
            . '&nbsp;&nbsp;&nbsp;&bull; All drawings, designs, BOQ documents and any other Document associated with this agreement remain the property of Wajenzi Professional Co. Ltd until payment is fully settled.</p>'
            . '<p><strong>6. Cancellation Policy.</strong><br>'
            . '&nbsp;&nbsp;&nbsp;&bull; After the work has started, if the client chooses to discontinue with the project there will be no refund.</p>'
            . '<p><strong>7. Dispute Resolution.</strong><br>'
            . '&nbsp;&nbsp;&nbsp;&bull; Any disputes related to this invoice or the services rendered shall be resolved amicably between both parties. If unresolved, the matter may be escalated as per applicable laws of Tanzania.</p>'
            . '<p><strong>8. Agreement Clause.</strong><br>'
            . '&nbsp;&nbsp;&nbsp;&bull; By making this payment, the client acknowledges and agrees to all the terms and conditions stated above.</p>';
    }

    /**
     * Get settings by group
     */
    public static function getByGroup(string $group)
    {
        return self::where('group', $group)->get();
    }
}
