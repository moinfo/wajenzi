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
