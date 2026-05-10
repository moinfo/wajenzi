<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteVisitLocation extends Model
{
    protected $fillable = [
        'name', 'base_cost_tzs', 'preset_travel_tzs', 'preset_local_tzs',
        'preset_allowance_tzs', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'base_cost_tzs'       => 'float',
        'preset_travel_tzs'   => 'float',
        'preset_local_tzs'    => 'float',
        'preset_allowance_tzs'=> 'float',
        'is_active'           => 'boolean',
    ];

    public function scopeActive($query) { return $query->where('is_active', true); }
}
