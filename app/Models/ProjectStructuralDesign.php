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
        'submitted_at'          => 'datetime',
        'approved_at'           => 'datetime',
        'schedule_planned_start' => 'date',
        'schedule_planned_end'   => 'date',
        'schedule_submitted_at'  => 'datetime',
        'schedule_approved_at'   => 'datetime',
    ];

    // ── Schedule helpers ─────────────────────────────────────────────────────

    public function scheduleApproved(): bool
    {
        return $this->schedule_status === 'approved';
    }

    public function schedulePending(): bool
    {
        return $this->schedule_status === 'submitted';
    }

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

        $this->project?->update(['status' => 'structural_approved']);

        // Auto-create the service design and notify service engineers
        if (!\App\Models\ProjectServiceDesign::where('project_id', $this->project_id)->exists()) {
            $serviceDesign = \App\Models\ProjectServiceDesign::create([
                'project_id'                       => $this->project_id,
                'triggered_by_structural_design_id' => $this->id,
                'status'                            => 'pending',
                'created_by'                        => $this->created_by,
            ]);
            foreach (\App\Models\ProjectServiceDesignStage::defaultStages() as $stage) {
                \App\Models\ProjectServiceDesignStage::create(array_merge(
                    $stage,
                    ['service_design_id' => $serviceDesign->id, 'status' => 'pending']
                ));
            }
            $serviceEngineers = User::role('Service Engineer')->get();
            foreach ($serviceEngineers as $eng) {
                $eng->notify(new \App\Notifications\SystemActionNotification(
                    'Service Design Assigned',
                    "Structural design for {$this->document_number} has been approved. Please prepare the service design.",
                    "/service-design/{$serviceDesign->id}",
                    null, $serviceDesign->id
                ));
            }
        }

        $link    = "/structural-design/{$this->id}";
        $title   = 'Structural Design Approved';
        $message = "The structural design for {$this->document_number} has been approved and is ready for BOQ preparation.";

        // Notify all Quantity Surveyors
        $qsUsers = User::role('Quantity Surveyor (QS)')->get();
        foreach ($qsUsers as $qs) {
            $qs->notify(new \App\Notifications\SystemActionNotification($title, $message, $link, null, $this->id));
        }

        // Notify Sales team so they can share with the client
        $salesUsers = User::role(['Sales and Marketing', 'Business Development Manager'])->get();
        foreach ($salesUsers as $sales) {
            $sales->notify(new \App\Notifications\SystemActionNotification(
                'Structural Design Ready to Share',
                "Structural design {$this->document_number} has been approved. You may now share it with the client via the portal.",
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

    public function feedbacks(): HasMany
    {
        return $this->hasMany(\App\Models\StructuralDesignFeedback::class, 'structural_design_id')
            ->latest();
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
