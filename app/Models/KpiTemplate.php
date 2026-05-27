<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'role_id', 'frequency', 'description', 'is_active',
    ];

    /**
     * The 14 shared "Section A — General Performance" items (30% total) that
     * apply to every role's template. Single source of truth for both the
     * KpiTemplateSeeder and the admin "New Template" create-flow.
     */
    public const COMMON_SECTION_A_ITEMS = [
        ['kpa' => 'Administration',                    'measure' => 'Ability to measure effectiveness in Planning',                          'target' => '90% of plans are realistic, timely, and successfully followed', 'weight' => 2],
        ['kpa' => 'Administration',                    'measure' => 'Ability to organize your duties / Works',                               'target' => 'Minimal missed deadlines, smooth task flow',                    'weight' => 2],
        ['kpa' => 'Attendance',                        'measure' => 'Attendance (HR-filled from attendance forms)',                          'target' => '0% unapproved absences (100% compliance)',                       'weight' => 5],
        ['kpa' => 'Attendance',                        'measure' => 'Punctuality — reports to duty/meetings on time',                        'target' => '95% on-time reporting',                                          'weight' => 5],
        ['kpa' => 'Communication',                     'measure' => 'Interpersonal Communication — use of good language with seniors/peers', 'target' => 'No complaints received from peers or managers',                  'weight' => 2],
        ['kpa' => 'Communication',                     'measure' => 'Written Communication Skills (letters, emails, other)',                 'target' => '98% accuracy and timely delivery of written communication',      'weight' => 2],
        ['kpa' => 'Flexibility',                       'measure' => 'Flexibility in Working Hours',                                          'target' => '95% positive attitude — works outside normal hours when needed', 'weight' => 1],
        ['kpa' => 'Flexibility',                       'measure' => 'Adaptability in Duties — additional tasks / support other teams',      'target' => 'Voluntarily supports other teams when necessary',                'weight' => 1],
        ['kpa' => 'Teamwork',                          'measure' => 'Teamwork & Cooperative Spirit — gets along with fellow employees',     'target' => 'Collaboration and respect',                                      'weight' => 2],
        ['kpa' => 'Teamwork',                          'measure' => 'Participation in Weekly Progressive Meetings',                          'target' => '98% attends and contributes actively',                           'weight' => 1],
        ['kpa' => 'Integrity & Accountability',        'measure' => 'Proper use of company property (equipment, tools, vehicles)',           'target' => 'No reports of misuse or loss',                                   'weight' => 2],
        ['kpa' => 'Integrity & Accountability',        'measure' => 'Honesty & Transparency — accepts responsibility for action',            'target' => 'Demonstrates honesty and transparency',                          'weight' => 1],
        ['kpa' => 'Decision Making & Problem Solving', 'measure' => 'Analyse problems and make practical decisions',                         'target' => '95% able to resolve issues with minimal escalation',             'weight' => 2],
        ['kpa' => 'Personal Appearance',               'measure' => 'Neatness and personal hygiene appropriate to position',                 'target' => '95% neatness and personal hygiene',                              'weight' => 2],
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(\Spatie\Permission\Models\Role::class, 'role_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(KpiTemplateSection::class)->orderBy('sort_order');
    }

    public function items(): HasMany
    {
        return $this->hasMany(KpiItem::class)->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(KpiReview::class);
    }

    /**
     * Find the template matching a given Spatie role id, falling back to a
     * code match if no row has role_id set. Returns null if nothing matches.
     */
    public static function forRoleId(?int $roleId): ?self
    {
        if (!$roleId) {
            return null;
        }
        return static::where('role_id', $roleId)->where('is_active', true)->first();
    }
}
