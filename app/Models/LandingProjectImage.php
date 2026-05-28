<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingProjectImage extends Model
{
    protected $fillable = [
        'landing_project_id',
        'file',
        'file_name',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(LandingProject::class, 'landing_project_id');
    }
}
