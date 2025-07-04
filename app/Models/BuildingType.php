<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuildingType extends Model
{
    use HasFactory;

    public $fillable = [
        'name',
        'description',
        'parent_id',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function boqTemplates()
    {
        return $this->hasMany(BoqTemplate::class);
    }

    public function parent()
    {
        return $this->belongsTo(BuildingType::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(BuildingType::class, 'parent_id');
    }
}