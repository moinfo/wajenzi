<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class ProjectBoqPlan extends Model implements ApprovableModel
{
    use HasFactory, Approvable;

    protected $table = 'project_boq_plans';

    protected $fillable = [
        'project_id',
        'planned_start',
        'planned_end',
        'scope_description',
        'status',
        'created_by',
        'submitted_at',
        'approved_at',
    ];

    protected $casts = [
        'planned_start' => 'date',
        'planned_end'   => 'date',
        'submitted_at'  => 'datetime',
        'approved_at'   => 'datetime',
    ];

    // Label used by RingleSoft approval header partial
    public function getDocumentNumberAttribute(): string
    {
        return 'BOQ-PLAN-' . str_pad($this->id, 4, '0', STR_PAD_LEFT)
            . ' / ' . ($this->project->project_name ?? 'Project');
    }

    public function bypassApprovalProcess(): bool
    {
        return false;
    }

    public function onApprovalCompleted(ProcessApproval $approval): bool
    {
        $this->status      = 'approved';
        $this->approved_at = now();
        $this->save();

        // Notify the QS who created the plan
        if ($this->created_by && $this->created_by !== $approval->approver_id) {
            $qs = User::find($this->created_by);
            $qs?->notify(new \App\Notifications\SystemActionNotification(
                'BOQ Plan Approved',
                "Your BOQ preparation plan for {$this->project->project_name} has been approved. You may now prepare the Bill of Quantities.",
                "/project-boq-plans/{$this->id}",
                null,
                $this->id
            ));
        }

        return true;
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'submitted';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public static function isApprovedForProject(int $projectId): bool
    {
        return static::where('project_id', $projectId)
            ->where('status', 'approved')
            ->exists();
    }
}
