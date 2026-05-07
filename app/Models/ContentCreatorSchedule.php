<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentCreatorSchedule extends Model
{
    protected $fillable = ['user_id', 'date', 'task_type', 'title'];

    protected $casts = ['date' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getColorAttribute(): string
    {
        return match($this->task_type) {
            'video_shoot'     => 'primary',
            'post_publish'    => 'success',
            'design_task'     => 'purple',
            'review_approval' => 'warning',
            'day_off'         => 'danger',
            default           => 'secondary',
        };
    }
}
