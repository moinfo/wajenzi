<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldTerritory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'region',
        'description',
        'assigned_user_id',
        'status',
        'created_by',
    ];

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function campaigns()
    {
        return $this->hasMany(FieldMarketingCampaign::class, 'territory_id');
    }

    public function activities()
    {
        return $this->hasMany(FieldActivity::class, 'territory_id');
    }

    public function leads()
    {
        return $this->hasManyThrough(Lead::class, FieldActivity::class, 'territory_id', 'field_activity_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
