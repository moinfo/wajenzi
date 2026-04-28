<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldMarketingVisit extends Model
{
    protected $fillable = [
        'session_id', 'business_name', 'location', 'phone',
        'status', 'next_followup_date', 'lead_id', 'notes', 'created_by',
    ];

    protected $casts = ['next_followup_date' => 'date'];

    public function session()
    {
        return $this->belongsTo(FieldMarketingSession::class, 'session_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function services()
    {
        return $this->belongsToMany(FieldMarketingService::class, 'field_marketing_visit_services', 'visit_id', 'service_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'converted'      => 'success',
            'interested'     => 'info',
            'follow_up'      => 'warning',
            'not_interested' => 'danger',
            default          => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'converted'      => 'Converted',
            'interested'     => 'Interested',
            'follow_up'      => 'Follow Up',
            'not_interested' => 'Not Interested',
            default          => ucfirst($this->status),
        };
    }
}
