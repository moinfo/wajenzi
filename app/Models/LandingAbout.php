<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingAbout extends Model
{
    protected $table = 'landing_about';

    protected $fillable = [
        'founded_year',
        'tagline',
        'story',
        'mission',
        'vision',
        'address',
        'phone',
        'email',
        'working_hours',
    ];

    protected $casts = [
        'tagline' => 'array',
        'story' => 'array',
        'mission' => 'array',
        'vision' => 'array',
        'working_hours' => 'array',
    ];

    /** Reuses the shared multilingual resolver. */
    public static function localize($value, ?string $lang = 'en'): ?string
    {
        return LandingProject::localize($value, $lang);
    }

    /**
     * The singleton About record. firstOrCreate persists an empty row on first
     * access so concurrent first-time saves update the same row instead of
     * inserting orphans (all columns are nullable, so an empty insert is valid).
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
