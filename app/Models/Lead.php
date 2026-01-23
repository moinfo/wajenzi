<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'lead_number',
        'lead_date',
        'name',
        'email',
        'phone',
        'address',
        'lead_source_id',
        'service_interested_id',
        'site_location',
        'city',
        'estimated_value',
        'lead_status_id',
        'salesperson_id',
        'notes',
        'client_source_id',
        'status',
        'created_by'
    ];

    protected $casts = [
        'lead_date' => 'date',
        'estimated_value' => 'decimal:2',
    ];

    /**
     * Boot method to auto-generate lead_number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lead) {
            if (empty($lead->lead_number)) {
                $lead->lead_number = self::generateLeadNumber();
            }
            if (empty($lead->lead_date)) {
                $lead->lead_date = now();
            }
        });
    }

    /**
     * Generate lead number in format LEAD-YYYYMM-###
     */
    public static function generateLeadNumber()
    {
        $prefix = 'LEAD-' . now()->format('Ym') . '-';

        $lastLead = self::where('lead_number', 'like', $prefix . '%')
            ->orderBy('lead_number', 'desc')
            ->first();

        if ($lastLead) {
            $lastNumber = (int) substr($lastLead->lead_number, -3);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Relationships
     */
    public function client()
    {
        return $this->belongsTo(ProjectClient::class, 'client_id');
    }

    public function clientSource()
    {
        return $this->belongsTo(ClientSource::class);
    }

    public function leadSource()
    {
        return $this->belongsTo(LeadSource::class);
    }

    public function serviceInterested()
    {
        return $this->belongsTo(ServiceInterested::class);
    }

    public function leadStatus()
    {
        return $this->belongsTo(LeadStatus::class);
    }

    public function salesperson()
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function leadFollowups()
    {
        return $this->hasMany(SalesLeadFollowup::class, 'lead_id');
    }

    /**
     * Get the latest followup
     */
    public function latestFollowup()
    {
        return $this->hasOne(SalesLeadFollowup::class, 'lead_id')->latestOfMany();
    }

    /**
     * Billing Documents Relationships
     */
    public function billingDocuments()
    {
        return $this->hasMany(BillingDocument::class, 'lead_id');
    }

    public function quotations()
    {
        return $this->hasMany(BillingDocument::class, 'lead_id')->where('document_type', 'quote');
    }

    public function proformas()
    {
        return $this->hasMany(BillingDocument::class, 'lead_id')->where('document_type', 'proforma');
    }

    public function invoices()
    {
        return $this->hasMany(BillingDocument::class, 'lead_id')->where('document_type', 'invoice');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }
}
