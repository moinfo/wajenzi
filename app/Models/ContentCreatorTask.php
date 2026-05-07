<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentCreatorTask extends Model
{
    protected $fillable = [
        'title', 'description', 'assigned_to', 'created_by',
        'deadline', 'deadline_time', 'priority', 'status', 'progress',
        'platform', 'task_type', 'attachments', 'instructions',
        'submitted_at', 'approved_at', 'approved_by',
    ];

    protected $casts = [
        'attachments' => 'array',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'deadline' => 'date',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ContentCreatorTaskComment::class, 'task_id')->with('user')->latest();
    }

    public function isOverdue(): bool
    {
        return $this->deadline && $this->deadline->isPast() && $this->status !== 'published';
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'high'   => 'danger',
            'medium' => 'warning',
            'low'    => 'success',
            default  => 'secondary',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'todo'        => 'secondary',
            'in_progress' => 'primary',
            'in_review'   => 'warning',
            'published'   => 'success',
            default       => 'secondary',
        };
    }

    public function getPlatformIconAttribute(): string
    {
        return match($this->platform) {
            'instagram' => 'fab fa-instagram',
            'tiktok'    => 'fab fa-tiktok',
            'facebook'  => 'fab fa-facebook',
            'linkedin'  => 'fab fa-linkedin',
            'youtube'   => 'fab fa-youtube',
            default     => 'fas fa-globe',
        };
    }

    public function getTaskTypeColorAttribute(): string
    {
        return match($this->task_type) {
            'video_shoot'     => 'primary',
            'post_publish'    => 'success',
            'design_task'     => 'purple',
            'review_approval' => 'warning',
            default           => 'secondary',
        };
    }
}
