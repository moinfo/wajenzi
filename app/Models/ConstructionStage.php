<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConstructionStage extends Model
{
    use HasFactory;

    public $fillable = [
        'name',
        'description',
        'sort_order'
    ];

    public function activities()
    {
        return $this->hasMany(Activity::class)->orderBy('sort_order');
    }

    public function templateStages()
    {
        return $this->hasMany(BoqTemplateStage::class);
    }
}