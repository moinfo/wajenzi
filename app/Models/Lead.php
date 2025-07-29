<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email', 
        'phone',
        'address',
        'client_source_id',
        'status',
        'created_by'
    ];

    /**
     * Relationships
     */
    public function clientSource()
    {
        return $this->belongsTo(ClientSource::class);
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
