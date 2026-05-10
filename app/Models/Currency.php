<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'symbol', 'code', 'rate_to_usd', 'is_base', 'is_active'];

    protected $casts = [
        'rate_to_usd' => 'float',
        'is_active'   => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getDisplayNameAttribute(): string
    {
        $code = $this->code ?: $this->symbol;
        return "{$code} — {$this->name}";
    }
}