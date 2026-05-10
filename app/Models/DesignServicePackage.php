<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignServicePackage extends Model
{
    protected $fillable = [
        'name', 'rise_type', 'price_usd', 'included_services', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'included_services' => 'array',
        'price_usd'         => 'float',
        'is_active'         => 'boolean',
    ];

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeLowRise($query) { return $query->where('rise_type', 'low'); }
    public function scopeHighRise($query) { return $query->where('rise_type', 'high'); }
}
