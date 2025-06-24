<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoqTemplateStage extends Model
{
    use HasFactory;

    public $fillable = [
        'boq_template_id',
        'construction_stage_id',
        'sort_order'
    ];

    public function boqTemplate()
    {
        return $this->belongsTo(BoqTemplate::class);
    }

    public function constructionStage()
    {
        return $this->belongsTo(ConstructionStage::class);
    }

    public function templateActivities()
    {
        return $this->hasMany(BoqTemplateActivity::class)->orderBy('sort_order');
    }
}