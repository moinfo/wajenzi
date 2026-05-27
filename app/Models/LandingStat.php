<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingStat extends Model
{
    protected $fillable = [
        'value',
        'label',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'label' => 'array',
        'is_published' => 'boolean',
    ];

    public static function localize($value, ?string $lang = 'en'): ?string
    {
        return LandingProject::localize($value, $lang);
    }
}
