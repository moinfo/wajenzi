<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoqTemplate extends Model
{
    use HasFactory;

    public $fillable = [
        'name',
        'description',
        'building_type_id',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function buildingType()
    {
        return $this->belongsTo(BuildingType::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function templateStages()
    {
        return $this->hasMany(BoqTemplateStage::class)->orderBy('sort_order');
    }

    public function stages()
    {
        return $this->belongsToMany(ConstructionStage::class, 'boq_template_stages', 'boq_template_id', 'construction_stage_id')
                    ->withPivot('sort_order')
                    ->orderBy('pivot_sort_order');
    }
}