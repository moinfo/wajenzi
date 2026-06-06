<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;
use App\Models\Concerns\CascadesApprovalRecords;

/**
 * A performance review for one employee for one period (month/quarter/...).
 *
 * Workflow: employee fills self-assessment → submits → supervisor reviews → MD → CEO.
 * The first approver is the employee's personal supervisor (a specific user id),
 * not a role — handled by overriding getNextApprovers() below. Step 2+ are role-based
 * (Managing Director, Chief Executive Officer).
 */
class KpiReview extends Model implements ApprovableModel
{
    use HasFactory, Approvable, CascadesApprovalRecords;

    // 'status' is intentionally NOT fillable — the state machine in
    // KpiController (approve/reject/return + submit) owns transitions and writes
    // via direct attribute assignment so a stray mass-assignment elsewhere can't
    // bypass it.
    protected $fillable = [
        'review_number', 'kpi_template_id', 'employee_id', 'supervisor_id',
        'period_label', 'period_start', 'period_end',
        'achievements', 'areas_of_improvement', 'training_needs',
        'employee_comments', 'supervisor_comments', 'md_comments', 'ceo_comments',
        'total_self_score', 'total_supervisor_score', 'total_overall_score', 'grade_label',
        'self_submitted_at', 'supervisor_reviewed_at', 'md_reviewed_at', 'completed_at',
        'created_by',
    ];

    protected $casts = [
        'period_start'              => 'date',
        'period_end'                => 'date',
        'self_submitted_at'         => 'datetime',
        'supervisor_reviewed_at'    => 'datetime',
        'md_reviewed_at'            => 'datetime',
        'completed_at'              => 'datetime',
        'total_self_score'          => 'decimal:2',
        'total_supervisor_score'    => 'decimal:2',
        'total_overall_score'       => 'decimal:2',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(KpiTemplate::class, 'kpi_template_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(KpiReviewRating::class)->orderBy('sort_order');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(KpiReviewAttachment::class);
    }

    /**
     * KPI reviews always require approval — never bypass.
     */
    public function bypassApprovalProcess(): bool
    {
        return false;
    }

    /**
     * Resolve the next approvers. Step 1 is the employee's *personal* supervisor;
     * later steps are by Spatie role (Managing Director, CEO).
     *
     * @return Collection<int, User>
     */
    public function getNextApprovers(): Collection
    {
        $step = $this->nextApprovalStep();
        if (!$step) {
            return User::query()->whereKey([])->get();
        }

        // Step 1 (supervisor) — the personal line manager set on the employee
        if ($step->order === 1) {
            if (!$this->supervisor_id) {
                return User::query()->whereKey([])->get();
            }
            return User::whereKey([$this->supervisor_id])->get();
        }

        // Step 2+ — by Spatie role (resolved through approval_flow_steps.role_id)
        if ($step->role_id) {
            return User::whereHas('roles', function ($q) use ($step) {
                $q->where('id', $step->role_id);
            })->get();
        }

        return User::query()->whereKey([])->get();
    }

    /**
     * Mark the review as completed when the final approver (CEO) signs off.
     */
    public function onApprovalCompleted(ProcessApproval $approval): bool
    {
        $this->status       = 'completed';
        $this->completed_at = now();
        $this->save();
        return true;
    }

    /**
     * Generate the next sequential review number, e.g. KPI-2026-05-0001.
     */
    public static function generateReviewNumber(?\Carbon\Carbon $period = null): string
    {
        $period = $period ?? now();
        $prefix = 'KPI-' . $period->format('Y-m') . '-';
        $last   = static::where('review_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->value('review_number');
        $next = $last ? ((int) substr($last, -4)) + 1 : 1;
        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Convenience guards so views/controllers don't reach into status strings.
     */
    public function isEditableByEmployee(): bool
    {
        return $this->status === 'draft' && $this->employee_id === auth()->id();
    }

    public function canBeReviewedBySupervisor(): bool
    {
        return $this->status === 'self_submitted' && $this->supervisor_id === auth()->id();
    }

    public function isFinalised(): bool
    {
        return in_array($this->status, ['completed', 'rejected'], true);
    }
}
