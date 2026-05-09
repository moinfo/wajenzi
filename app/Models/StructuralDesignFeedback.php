<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StructuralDesignFeedback extends Model
{
    protected $fillable = ['structural_design_id', 'client_id', 'comment'];

    public function structuralDesign(): BelongsTo
    {
        return $this->belongsTo(ProjectStructuralDesign::class, 'structural_design_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ProjectClient::class, 'client_id');
    }
}
