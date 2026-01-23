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
        'followup_date',
        'status',
        'attended_at',
        'attended_by',
    ];

    protected $casts = [
        'followup_date' => 'date',
        'attended_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_RESCHEDULED = 'rescheduled';

    /**
     * Check if follow-up is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if follow-up is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

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

    public function attendedByUser()
    {
        return $this->belongsTo(User::class, 'attended_by');
    }
}
