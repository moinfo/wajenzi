<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoqItemCategory extends Model
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

    public function parent()
    {
        return $this->belongsTo(BoqItemCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(BoqItemCategory::class, 'parent_id');
    }

    public function boqItems()
    {
        return $this->hasMany(BoqTemplateItem::class, 'category_id');
    }

    public static function getHierarchical()
    {
        return self::with('children')->whereNull('parent_id')->orderBy('sort_order')->get();
    }
}