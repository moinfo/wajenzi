<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesClientConcern extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_daily_report_id',
        'client_name',
        'client_id', 
        'issue_concern',
        'action_taken'
    ];

    /**
     * Relationships
     */
    public function salesDailyReport()
    {
        return $this->belongsTo(SalesDailyReport::class);
    }

    public function client()
    {
        return $this->belongsTo(ProjectClient::class);
    }
}