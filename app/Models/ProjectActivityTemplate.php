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
    ];

    protected $casts = [
        'duration_days' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
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
