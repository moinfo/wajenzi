<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteLaborNeeded extends Model
{
    use HasFactory;

    protected $table = 'site_labor_needed';

    protected $fillable = [
        'site_daily_report_id',
        'labor_type',
        'description'
    ];

    /**
     * Relationships
     */
    public function dailyReport()
    {
        return $this->belongsTo(SiteDailyReport::class, 'site_daily_report_id');
    }
}