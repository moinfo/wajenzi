<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoqTemplateSubActivity extends Model
{
    use HasFactory;

    public $fillable = [
        'boq_template_activity_id',
        'sub_activity_id',
        'sort_order'
    ];

    public function templateActivity()
    {
        return $this->belongsTo(BoqTemplateActivity::class, 'boq_template_activity_id');
    }

    public function subActivity()
    {
        return $this->belongsTo(SubActivity::class);
    }
}