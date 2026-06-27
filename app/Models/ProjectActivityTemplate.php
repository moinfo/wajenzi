<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectActivityTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_code',
        'name',
        'phase',
        'discipline',
        'duration_days',
        'predecessor_code',
        'sort_order',
        'is_active',
        'role_id',
        'requires_approval',
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'requires_approval' => 'boolean',
    ];

    /**
     * Get the predecessor template
     */
    public function predecessor()
    {
        return $this->belongsTo(ProjectActivityTemplate::class, 'predecessor_code', 'activity_code');
    }

    /**
     * Get templates that depend on this one
     */
    public function dependents()
    {
        return $this->hasMany(ProjectActivityTemplate::class, 'predecessor_code', 'activity_code');
    }

    /**
     * Get the role responsible for this activity type (primary role)
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * All roles responsible for this activity type (many-to-many).
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'project_activity_template_role', 'template_id', 'role_id');
    }

    /**
     * Role ids for this template — the pivot set, falling back to the primary role_id.
     */
    public function responsibleRoleIds(): array
    {
        $ids = $this->roles->pluck('id')->all();

        if (empty($ids) && $this->role_id) {
            $ids = [$this->role_id];
        }

        return $ids;
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get all templates in order
     */
    public static function getOrderedTemplates()
    {
        return static::active()->orderBy('sort_order')->get();
    }
}
