<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteMaterialUsed extends Model
{
    use HasFactory;

    protected $table = 'site_materials_used';

    protected $fillable = [
        'site_daily_report_id',
        'material_name',
        'quantity',
        'unit'
    ];

    /**
     * Relationships
     */
    public function dailyReport()
    {
        return $this->belongsTo(SiteDailyReport::class, 'site_daily_report_id');
    }
}