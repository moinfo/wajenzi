<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldMarketingService extends Model
{
    protected $fillable = ['name', 'sort_order', 'status'];

    public function visits()
    {
        return $this->belongsToMany(FieldMarketingVisit::class, 'field_marketing_visit_services', 'service_id', 'visit_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
