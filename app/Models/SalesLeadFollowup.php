<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesLeadFollowup extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_daily_report_id',
        'lead_name',
        'client_id',
        'lead_id',
        'client_source_id',
        'details_discussion',
        'outcome',
        'next_step',
        'followup_date'
    ];

    protected $casts = [
        'followup_date' => 'date',
    ];

    /**
     * Relationships
     */
    public function salesDailyReport()
    {
        return $this->belongsTo(SalesDailyReport::class);
    }

    public function clientSource()
    {
        return $this->belongsTo(ClientSource::class);
    }

    public function client()
    {
        return $this->belongsTo(ProjectClient::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}