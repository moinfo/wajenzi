<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingValue extends Model
{
    protected $table = 'landing_values';

    protected $fillable = [
        'title',
        'description',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'title' => 'array',
        'description' => 'array',
        'is_published' => 'boolean',
    ];

    /** Reuses the shared multilingual resolver. */
    public static function localize($value, ?string $lang = 'en'): ?string
    {
        return LandingProject::localize($value, $lang);
    }
}
