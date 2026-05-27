<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingAward extends Model
{
    protected $fillable = [
        'year',
        'title',
        'subtitle',
        'organization',
        'description',
        'image',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'title' => 'array',
        'subtitle' => 'array',
        'organization' => 'array',
        'description' => 'array',
        'is_published' => 'boolean',
    ];

    /** Reuses the shared multilingual resolver. */
    public static function localize($value, ?string $lang = 'en'): ?string
    {
        return LandingProject::localize($value, $lang);
    }
}
