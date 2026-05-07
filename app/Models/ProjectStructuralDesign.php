<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class ProjectStructuralDesign extends Model implements ApprovableModel
{
    use HasFactory, Approvable;

    protected $table = 'project_structural_designs';

    protected $fillable = [
        'project_id',
        'triggered_by_activity_id',
        'assigned_engineer_id',
        'status',
        'notes',
        'submitted_at',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at'  => 'datetime',
    ];

    // Used as the display label in approval UI partials
    public function getDocumentNumberAttribute(): string
    {
        return 'STR-' . str_pad($this->id, 4, '0', STR_PAD_LEFT)
            . ' / ' . ($this->project->project_name ?? 'Project');
    }

    public function onApprovalCompleted(ProcessApproval $approval): bool
    {
        $this->status      = 'approved';
        $this->approved_at = now();
        $this->save();

        // Update the project to reflect structural approval (unlocks BOQ)
        $this->project?->update(['status' => 'structural_approved']);

        return true;
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_engineer_id');
    }

    public function triggeringActivity(): BelongsTo
    {
        return $this->belongsTo(ProjectScheduleActivity::class, 'triggered_by_activity_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ProjectStructuralDesignStage::class, 'structural_design_id')
            ->orderBy('stage_order');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function allStagesCompleted(): bool
    {
        return $this->stages()->where('status', '!=', 'completed')->doesntExist();
    }

    /**
     * Gate check used by ProjectBoqController and the mobile BOQ API.
     */
    public static function isApprovedForProject(int $projectId): bool
    {
        return static::where('project_id', $projectId)
            ->where('status', 'approved')
            ->exists();
    }
}
