<?php

namespace App\Models;

use App\Services\StructuralHandoffService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;
use App\Models\Concerns\CascadesApprovalRecords;

class ProjectScheduleActivity extends Model implements ApprovableModel
{
    use HasFactory, Approvable, CascadesApprovalRecords;

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
        'role_id',
        'status',
        'started_at',
        'completed_at',
        'completed_by',
        'notes',
        'completion_notes',
        'sort_order',
        'requires_approval',
        'approval_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_days' => 'integer',
        'sort_order' => 'integer',
        'requires_approval' => 'boolean',
    ];

    /**
     * When an activity transitions to "completed" — by any path (controller, model
     * method, or RingleSoft approval) — sync the parent schedule's bonus task.
     *
     * NOTE: This hook fires on Eloquent model events ($activity->save() or
     * $activity->update([...])). Query-builder mass updates such as
     * `ProjectScheduleActivity::where(...)->update(['status' => 'completed'])`
     * BYPASS Eloquent events and will NOT trigger the bonus sync. If you need
     * to flip status in bulk, iterate the models and save individually.
     */
    protected static function booted(): void
    {
        static::updated(function (ProjectScheduleActivity $activity) {
            if ($activity->wasChanged('status') && $activity->status === 'completed') {
                app(\App\Services\BonusScheduleSyncService::class)->syncFromActivity($activity);
            }
        });
    }

    /**
     * Skip the approval engine for activities that don't require CEO/MD sign-off.
     */
    public function bypassApprovalProcess(): bool
    {
        return !$this->requires_approval;
    }

    /**
     * Called by RingleSoft when all approval steps are completed.
     * Marks the activity as completed and unlocks dependent activities.
     */
    public function onApprovalCompleted(ProcessApproval $approval): bool
    {
        $this->status       = 'completed';
        $this->completed_at = now();
        $this->completed_by = $approval->approver_id ?? auth()->id();
        $this->save();

        // B7 (3D Final Draft) approval triggers the structural design handoff
        if ($this->activity_code === 'B7') {
            StructuralHandoffService::triggerFromActivity($this);
        }

        return true;
    }

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
     * Get the role responsible for this activity
     * Uses base Model to avoid Spatie guard mismatch issues
     */
    public function role()
    {
        return $this->belongsTo(\App\Models\Role::class);
    }

    /**
     * Get the user who completed
     */
    public function completedByUser()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Get the attachments uploaded for this activity
     */
    public function attachments()
    {
        return $this->hasMany(ProjectScheduleActivityAttachment::class, 'activity_id')->latest('id');
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
        return $this->status !== 'completed'
            && $this->end_date !== null
            && $this->end_date->isPast();
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
