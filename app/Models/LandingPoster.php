<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPoster extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'link_url',
        'youtube_url',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'title' => 'array',
        'subtitle' => 'array',
        'is_published' => 'boolean',
    ];

    public static function localize($value, ?string $lang = 'en'): ?string
    {
        return LandingProject::localize($value, $lang);
    }
}
