<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoqTemplateActivity extends Model
{
    use HasFactory;

    public $fillable = [
        'boq_template_stage_id',
        'activity_id',
        'sort_order'
    ];

    public function templateStage()
    {
        return $this->belongsTo(BoqTemplateStage::class, 'boq_template_stage_id');
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function templateSubActivities()
    {
        return $this->hasMany(BoqTemplateSubActivity::class)->orderBy('sort_order');
    }
}