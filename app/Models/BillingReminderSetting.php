<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingReminderSetting extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'auto_reminders_enabled',
        'reminder_intervals',
        'late_fees_enabled',
        'late_fee_percentage',
        'late_fee_reminders_enabled',
        'late_fee_reminder_interval',
    ];
    
    protected $casts = [
        'auto_reminders_enabled' => 'boolean',
        'reminder_intervals' => 'array',
        'late_fees_enabled' => 'boolean',
        'late_fee_percentage' => 'decimal:2',
        'late_fee_reminders_enabled' => 'boolean',
        'late_fee_reminder_interval' => 'integer',
    ];
    
    public static function getSettings()
    {
        return static::first() ?? static::create([
            'auto_reminders_enabled' => true,
            'reminder_intervals' => [28, 21, 14, 7, 3, 1],
            'late_fees_enabled' => true,
            'late_fee_percentage' => 10.00,
            'late_fee_reminders_enabled' => true,
            'late_fee_reminder_interval' => 7,
        ]);
    }
}
