<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingTeamMember extends Model
{
    protected $table = 'landing_team_members';

    protected $fillable = [
        'name',
        'role',
        'bio',
        'image',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'role' => 'array',
        'bio' => 'array',
        'is_published' => 'boolean',
    ];

    /** Reuses the shared multilingual resolver. */
    public static function localize($value, ?string $lang = 'en'): ?string
    {
        return LandingProject::localize($value, $lang);
    }
}
