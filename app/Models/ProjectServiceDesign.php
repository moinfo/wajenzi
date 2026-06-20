<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;
use App\Models\Concerns\CascadesApprovalRecords;

class ProjectServiceDesign extends Model implements ApprovableModel
{
    use HasFactory, Approvable, CascadesApprovalRecords;

    protected $table = 'project_service_designs';

    protected $fillable = [
        'project_id',
        'triggered_by_structural_design_id',
        'assigned_engineer_id',
        'status',
        'notes',
        'submitted_at',
        'approved_at',
        'created_by',
        'schedule_description',
        'schedule_planned_start',
        'schedule_planned_end',
        'schedule_status',
        'schedule_submitted_at',
        'schedule_approved_at',
        'schedule_approved_by',
        'schedule_rejection_notes',
    ];

    protected $casts = [
        'submitted_at'           => 'datetime',
        'approved_at'            => 'datetime',
        'schedule_planned_start' => 'date',
        'schedule_planned_end'   => 'date',
        'schedule_submitted_at'  => 'datetime',
        'schedule_approved_at'   => 'datetime',
    ];

    // ── Schedule helpers ─────────────────────────────────────────────────────

    public function scheduleApproved(): bool { return $this->schedule_status === 'approved'; }
    public function schedulePending(): bool  { return $this->schedule_status === 'submitted'; }
    public function isSubmitted(): bool      { return $this->status === 'submitted'; }

    public function getDocumentNumberAttribute(): string
    {
        return 'SVC-' . str_pad($this->id, 4, '0', STR_PAD_LEFT)
            . ' / ' . ($this->project->project_name ?? 'Project');
    }

    public function onApprovalCompleted(ProcessApproval $approval): bool
    {
        $this->status      = 'approved';
        $this->approved_at = now();
        $this->save();

        $this->project?->update(['status' => 'service_approved']);

        $link    = "/service-design/{$this->id}";
        $title   = 'Service Design Approved';
        $message = "The service design for {$this->document_number} has been approved and is ready for BOQ preparation.";

        $qsUsers = User::whereHas('roles', fn ($q) => $q->where('name', 'Quantity Surveyor (QS)'))->get();
        foreach ($qsUsers as $qs) {
            $qs->notify(new \App\Notifications\SystemActionNotification($title, $message, $link, null, $this->id));
        }

        $salesUsers = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Sales and Marketing', 'Business Development Manager']))->get();
        foreach ($salesUsers as $sales) {
            $sales->notify(new \App\Notifications\SystemActionNotification(
                'Service Design Ready to Share',
                "Service design {$this->document_number} has been approved. You may now share it with the client.",
                $link, null, $this->id
            ));
        }

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

    public function triggeringStructuralDesign(): BelongsTo
    {
        return $this->belongsTo(ProjectStructuralDesign::class, 'triggered_by_structural_design_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ProjectServiceDesignStage::class, 'service_design_id')
            ->orderBy('stage_order');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(ServiceDesignFeedback::class, 'service_design_id')->latest();
    }

    public static function isApprovedForProject(int $projectId): bool
    {
        return static::where('project_id', $projectId)->where('status', 'approved')->exists();
    }
}
