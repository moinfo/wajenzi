<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    public $fillable = [
        'construction_stage_id',
        'name',
        'description',
        'sort_order'
    ];

    public function constructionStage()
    {
        return $this->belongsTo(ConstructionStage::class);
    }

    public function subActivities()
    {
        return $this->hasMany(SubActivity::class)->orderBy('sort_order');
    }

    public function templateActivities()
    {
        return $this->hasMany(BoqTemplateActivity::class);
    }
}