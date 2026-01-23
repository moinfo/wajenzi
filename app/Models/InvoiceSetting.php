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
     * Get settings by group
     */
    public static function getByGroup(string $group)
    {
        return self::where('group', $group)->get();
    }
}
