<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteWorkActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_daily_report_id',
        'work_description',
        'order_number'
    ];

    /**
     * Relationships
     */
    public function dailyReport()
    {
        return $this->belongsTo(SiteDailyReport::class, 'site_daily_report_id');
    }
}