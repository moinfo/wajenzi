<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class ProjectSchedule extends Model implements ApprovableModel
{
    use HasFactory, Approvable;

    protected $fillable = [
        'lead_id',
        'client_id',
        'start_date',
        'end_date',
        'status',
        'assigned_architect_id',
        'confirmed_at',
        'confirmed_by',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    /**
     * Get the lead
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the client
     */
    public function client()
    {
        return $this->belongsTo(ProjectClient::class, 'client_id');
    }

    /**
     * Get the assigned architect
     */
    public function assignedArchitect()
    {
        return $this->belongsTo(User::class, 'assigned_architect_id');
    }

    /**
     * Get the user who confirmed
     */
    public function confirmedByUser()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get schedule activities
     */
    public function activities()
    {
        return $this->hasMany(ProjectScheduleActivity::class)->orderBy('sort_order');
    }

    /**
     * Get assignments
     */
    public function assignments()
    {
        return $this->hasMany(ProjectAssignment::class);
    }

    /**
     * Always require CEO/MD approval — never bypass.
     */
    public function bypassApprovalProcess(): bool
    {
        return false;
    }

    /**
     * Called by RingleSoft when the MD/CEO approves the schedule.
     * Marks the schedule as confirmed and notifies the architect.
     */
    public function onApprovalCompleted(ProcessApproval $approval): bool
    {
        $this->status       = 'confirmed';
        $this->confirmed_at = now();
        $this->confirmed_by = $approval->approver_id ?? auth()->id();
        $this->save();

        // Notify the assigned architect
        if ($this->assigned_architect_id && $this->assigned_architect_id !== $approval->approver_id) {
            $architect = \App\Models\User::find($this->assigned_architect_id);
            if ($architect) {
                $leadNumber = $this->lead->lead_number ?? $this->lead->name ?? 'N/A';
                $architect->notify(new \App\Notifications\SystemActionNotification(
                    'Schedule Approved',
                    "Your project schedule for {$leadNumber} has been approved. You may now begin activities.",
                    "/project-schedules/{$this->id}",
                    null,
                    $this->id
                ));
            }
        }

        return true;
    }

    /**
     * Check if schedule is confirmed (approved and ready for work)
     */
    public function isConfirmed(): bool
    {
        return in_array($this->status, ['confirmed', 'in_progress', 'completed']);
    }

    /**
     * Check if schedule is awaiting MD/CEO approval
     */
    public function isPendingApproval(): bool
    {
        return $this->status === 'pending_confirmation';
    }

    /**
     * Get progress percentage
     */
    public function getProgressAttribute(): float
    {
        $total = $this->activities()->count();
        if ($total === 0) return 0;

        $completed = $this->activities()->where('status', 'completed')->count();
        return round(($completed / $total) * 100, 1);
    }

    /**
     * Get progress details
     */
    public function getProgressDetailsAttribute(): array
    {
        $activities = $this->activities;
        $total = $activities->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'pending' => 0,
                'overdue' => 0,
                'percentage' => 0,
            ];
        }

        $completed = $activities->where('status', 'completed')->count();
        $inProgress = $activities->where('status', 'in_progress')->count();
        $pending = $activities->where('status', 'pending')->count();
        $overdue = $activities->filter(fn($a) => $a->isOverdue())->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'pending' => $pending,
            'overdue' => $overdue,
            'percentage' => round(($completed / $total) * 100, 1),
        ];
    }

    /**
     * Get progress by phase
     */
    public function getProgressByPhaseAttribute(): array
    {
        $activities = $this->activities;
        $phases = $activities->groupBy('phase');

        $result = [];
        foreach ($phases as $phase => $phaseActivities) {
            $total = $phaseActivities->count();
            $completed = $phaseActivities->where('status', 'completed')->count();
            $result[$phase] = [
                'total' => $total,
                'completed' => $completed,
                'percentage' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            ];
        }

        return $result;
    }

    /**
     * Scope for active schedules (confirmed or in progress)
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['confirmed', 'in_progress']);
    }
}
