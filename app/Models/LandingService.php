<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingService extends Model
{
    protected $fillable = [
        'title',
        'short_description',
        'full_description',
        'image',
        'features',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'title' => 'array',
        'short_description' => 'array',
        'full_description' => 'array',
        'features' => 'array',
        'is_published' => 'boolean',
    ];

    /** Reuses the shared multilingual resolver. */
    public static function localize($value, ?string $lang = 'en'): ?string
    {
        return LandingProject::localize($value, $lang);
    }
}
