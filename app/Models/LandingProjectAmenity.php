<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingProjectAmenity extends Model
{
    protected $fillable = [
        'landing_project_id',
        'label',
        'sort_order',
    ];

    protected $casts = [
        'label' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(LandingProject::class, 'landing_project_id');
    }
}
