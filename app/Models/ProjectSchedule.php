<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectSchedule extends Model
{
    use HasFactory;

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
     * Check if schedule is confirmed
     */
    public function isConfirmed(): bool
    {
        return in_array($this->status, ['confirmed', 'in_progress', 'completed']);
    }

    /**
     * Confirm the schedule
     */
    public function confirm($userId = null)
    {
        $this->status = 'confirmed';
        $this->confirmed_at = now();
        $this->confirmed_by = $userId ?? auth()->id();
        $this->save();
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
