<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignServiceAddon extends Model
{
    protected $fillable = [
        'name', 'price_low_usd', 'price_high_usd', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'price_low_usd'  => 'float',
        'price_high_usd' => 'float',
        'is_active'      => 'boolean',
    ];

    public function scopeActive($query) { return $query->where('is_active', true); }
}
