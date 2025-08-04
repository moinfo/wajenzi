<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SitePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_daily_report_id',
        'payment_description',
        'amount',
        'payment_to'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function dailyReport()
    {
        return $this->belongsTo(SiteDailyReport::class, 'site_daily_report_id');
    }
}