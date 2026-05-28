<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingProjectLike extends Model
{
    protected $fillable = [
        'landing_project_id',
        'device_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(LandingProject::class, 'landing_project_id');
    }
}
