<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignSpecialStructure extends Model
{
    protected $fillable = [
        'name', 'rate_tzs_per_sqm', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'rate_tzs_per_sqm' => 'float',
        'is_active'        => 'boolean',
    ];

    public function scopeActive($query) { return $query->where('is_active', true); }
}
