<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectScheduleActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_schedule_id',
        'activity_code',
        'name',
        'phase',
        'discipline',
        'start_date',
        'duration_days',
        'end_date',
        'predecessor_code',
        'assigned_to',
        'status',
        'started_at',
        'completed_at',
        'completed_by',
        'notes',
        'completion_notes',
        'attachment_path',
        'attachment_name',
        'sort_order',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_days' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the schedule
     */
    public function schedule()
    {
        return $this->belongsTo(ProjectSchedule::class, 'project_schedule_id');
    }

    /**
     * Get the assigned user
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who completed
     */
    public function completedByUser()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Get predecessor activity
     */
    public function predecessor()
    {
        if (!$this->predecessor_code) return null;

        return $this->schedule->activities()
            ->where('activity_code', $this->predecessor_code)
            ->first();
    }

    /**
     * Get dependent activities
     */
    public function dependents()
    {
        return $this->schedule->activities()
            ->where('predecessor_code', $this->activity_code)
            ->get();
    }

    /**
     * Mark as started
     */
    public function markAsStarted($userId = null)
    {
        $this->status = 'in_progress';
        $this->started_at = now();
        $this->save();
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted($userId = null)
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->completed_by = $userId ?? auth()->id();
        $this->save();
    }

    /**
     * Check if activity can start (predecessor completed)
     */
    public function canStart(): bool
    {
        if (!$this->predecessor_code) return true;

        $predecessor = $this->predecessor();
        return $predecessor && $predecessor->status === 'completed';
    }

    /**
     * Check if overdue
     */
    public function isOverdue(): bool
    {
        return $this->status !== 'completed' && $this->end_date->isPast();
    }

    /**
     * Scope for pending activities
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for in progress activities
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    /**
     * Scope for completed activities
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for activities assigned to user
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }
}
