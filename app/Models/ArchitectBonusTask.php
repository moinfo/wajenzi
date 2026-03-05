<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ArchitectBonusTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_number', 'project_name', 'architect_id', 'project_budget',
        'lead_id', 'project_schedule_id', 'start_date', 'scheduled_completion_date',
        'actual_completion_date', 'max_units', 'design_quality_score', 'client_revisions',
        'schedule_performance', 'client_approval_efficiency', 'performance_score',
        'final_units', 'bonus_amount', 'status', 'notes', 'created_by',
        'scored_by', 'scored_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'scheduled_completion_date' => 'date',
        'actual_completion_date' => 'date',
        'scored_at' => 'datetime',
        'project_budget' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
    ];

    public function architect()
    {
        return $this->belongsTo(User::class, 'architect_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function projectSchedule()
    {
        return $this->belongsTo(ProjectSchedule::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scorer()
    {
        return $this->belongsTo(User::class, 'scored_by');
    }

    /**
     * Get scheduled duration in working days.
     */
    public function getScheduledDaysAttribute(): int
    {
        return max(1, $this->start_date->diffInWeekdays($this->scheduled_completion_date));
    }

    /**
     * Get actual duration in working days (null if not completed).
     */
    public function getActualDaysAttribute(): ?int
    {
        if (!$this->actual_completion_date) {
            return null;
        }
        return max(1, $this->start_date->diffInWeekdays($this->actual_completion_date));
    }

    /**
     * Check if delay exceeds 50% of scheduled duration (no bonus rule).
     */
    public function isExcessiveDelay(): bool
    {
        if (!$this->actual_completion_date) {
            return false;
        }
        $delay = $this->actual_days - $this->scheduled_days;
        return $delay > ($this->scheduled_days * 0.5);
    }

    /**
     * Generate next task number.
     */
    public static function generateTaskNumber(): string
    {
        $lastTask = static::orderBy('id', 'desc')->first();
        $nextId = $lastTask ? $lastTask->id + 1 : 1;
        return 'BNS-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }
}
