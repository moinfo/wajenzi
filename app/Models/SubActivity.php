<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubActivity extends Model
{
    use HasFactory;

    public $fillable = [
        'activity_id',
        'name',
        'description',
        'estimated_duration_hours',
        'duration_unit',
        'labor_requirement',
        'skill_level',
        'can_run_parallel',
        'weather_dependent',
        'sort_order'
    ];

    protected $casts = [
        'estimated_duration_hours' => 'decimal:2',
        'can_run_parallel' => 'boolean',
        'weather_dependent' => 'boolean'
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function materials()
    {
        return $this->hasMany(SubActivityMaterial::class);
    }

    public function boqItems()
    {
        return $this->belongsToMany(BoqTemplateItem::class, 'sub_activity_materials', 'sub_activity_id', 'boq_item_id')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    public function templateSubActivities()
    {
        return $this->hasMany(BoqTemplateSubActivity::class);
    }

    public function getDurationDisplayAttribute()
    {
        if (!$this->estimated_duration_hours) return null;
        
        switch ($this->duration_unit) {
            case 'hours':
                return $this->estimated_duration_hours . ' hours';
            case 'days':
                return ($this->estimated_duration_hours / 8) . ' days';
            case 'weeks':
                return ($this->estimated_duration_hours / 40) . ' weeks';
            default:
                return $this->estimated_duration_hours . ' hours';
        }
    }
}