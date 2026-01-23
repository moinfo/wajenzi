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

    /**
     * Generate Google Calendar URL for this follow-up
     */
    public function getGoogleCalendarUrl(): string
    {
        $title = 'Follow-up: ' . ($this->lead->name ?? $this->lead_name ?? 'Lead');

        // Use followup_date, default to 9 AM - 10 AM
        $startDate = $this->followup_date->copy()->setTime(9, 0);
        $endDate = $this->followup_date->copy()->setTime(10, 0);

        // Format dates for Google Calendar (YYYYMMDDTHHmmSSZ)
        $dateFormat = 'Ymd\THis\Z';
        $dates = $startDate->utc()->format($dateFormat) . '/' . $endDate->utc()->format($dateFormat);

        // Build description
        $details = [];
        if ($this->next_step) {
            $details[] = "Action: {$this->next_step}";
        }
        if ($this->details_discussion) {
            $details[] = "Notes: {$this->details_discussion}";
        }
        if ($this->lead) {
            $details[] = "Lead: {$this->lead->name}";
            if ($this->lead->phone) {
                $details[] = "Phone: {$this->lead->phone}";
            }
            if ($this->lead->email) {
                $details[] = "Email: {$this->lead->email}";
            }
        }
        $description = implode("\n", $details);

        // Build URL
        $params = [
            'action' => 'TEMPLATE',
            'text' => $title,
            'dates' => $dates,
            'details' => $description,
        ];

        return 'https://calendar.google.com/calendar/render?' . http_build_query($params);
    }

    /**
     * Get Google Calendar URL attribute
     */
    public function getGoogleCalendarLinkAttribute(): string
    {
        return $this->getGoogleCalendarUrl();
    }
}
