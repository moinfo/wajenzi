<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoqTemplateItem extends Model
{
    use HasFactory;

    public $fillable = [
        'name',
        'description',
        'unit',
        'base_price',
        'category_id'
    ];

    protected $casts = [
        'base_price' => 'decimal:2'
    ];

    public function category()
    {
        return $this->belongsTo(BoqItemCategory::class, 'category_id');
    }

    public function subActivityMaterials()
    {
        return $this->hasMany(SubActivityMaterial::class, 'boq_item_id');
    }

    public function subActivities()
    {
        return $this->belongsToMany(SubActivity::class, 'sub_activity_materials', 'boq_item_id', 'sub_activity_id')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }
}