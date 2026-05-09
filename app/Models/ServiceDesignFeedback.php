<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceDesignFeedback extends Model
{
    protected $table = 'service_design_feedbacks';

    protected $fillable = ['service_design_id', 'client_id', 'comment'];

    public function serviceDesign(): BelongsTo
    {
        return $this->belongsTo(ProjectServiceDesign::class, 'service_design_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(ProjectClient::class, 'client_id');
    }
}
