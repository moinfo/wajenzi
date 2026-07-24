<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KpiItem;
use App\Models\KpiReview;
use App\Models\KpiReviewRating;
use App\Models\KpiTemplate;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDF;
use Spatie\Permission\Models\Role;

/**
 * Sanctum-authenticated KPI / Performance review API (PUBLIC-V1).
 *
 * This is a 1:1 mirror of App\Http\Controllers\KpiController (the web/portal
 * controller). It uses the SAME scoring, the SAME status transitions, the SAME
 * permission helpers, and CRUCIALLY the SAME RingleSoft approval calls — the
 * web PDF/approval queues depend on both the RingleSoft state AND the
 * denormalized status/*_at columns being kept in sync, so we do both here too.
 *
 * Workflow:
 *   draft → self_submitted (submit → RingleSoft step 1 = personal supervisor)
 *         → supervisor_reviewed (supervisor approve → step 2 = MD)
 *         → md_reviewed (MD approve → step 3 = CEO)
 *         → completed (CEO approve; onApprovalCompleted writes status)
 */
class KpiApiController extends Controller
{
    /**
     * Rating band labels — translated from total_overall_score (0..100).
     * Identical to KpiController::GRADE_BANDS.
     */
    private const GRADE_BANDS = [
        ['min' => 90, 'label' => 'Excellent'],
        ['min' => 80, 'label' => 'Very Good'],
        ['min' => 70, 'label' => 'Good'],
        ['min' => 60, 'label' => 'Average'],
        ['min' => 50, 'label' => 'Poor'],
        ['min' => 0,  'label' => 'Ungraded'],
    ];

    /**
     * Human-friendly status labels for the mobile client.
     */
    private const STATUS_LABELS = [
        'draft'               => 'Draft',
        'self_submitted'      => 'Awaiting Supervisor',
        'supervisor_reviewed' => 'Awaiting MD',
        'md_reviewed'         => 'Awaiting CEO',
        'completed'           => 'Completed',
        'rejected'            => 'Rejected',
        'returned'            => 'Returned for Changes',
    ];

    // =====================================================================
    // Endpoints
    // =====================================================================

    /**
     * GET performance?tab=mine|awaiting|all&page=
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tab  = $request->query('tab', 'mine');

        $query = KpiReview::with(['template', 'employee', 'supervisor'])
            ->orderByDesc('period_start')
            ->orderByDesc('id');

        switch ($tab) {
            case 'awaiting':
                $this->scopeAwaitingFor($query, $user);
                break;
            case 'all':
                if (!$this->canSeeAllReviews($user)) {
                    return $this->forbidden();
                }
                break;
            case 'mine':
            default:
                $tab = 'mine';
                $query->where('employee_id', $user->id);
        }

        $reviews = $query->paginate(20)->withQueryString();

        $rows = collect($reviews->items())->map(fn (KpiReview $r) => $this->mapReviewListRow($r, $user))->all();

        return response()->json([
            'success' => true,
            'data'    => [
                'reviews' => $rows,
                'counts'  => [
                    'mine_open' => KpiReview::where('employee_id', $user->id)
                        ->whereNotIn('status', ['completed', 'rejected'])->count(),
                    'awaiting'  => $this->awaitingCountFor($user),
                ],
                'can_see_all' => $this->canSeeAllReviews($user),
                'tab'         => $tab,
            ],
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page'    => $reviews->lastPage(),
                'total'        => $reviews->total(),
            ],
        ]);
    }

    /**
     * GET performance/create-info — info needed to render the "new review" form.
     */
    public function createInfo(Request $request): JsonResponse
    {
        $user         = $request->user();
        $autoTemplate = $this->resolveTemplateForUser($user);
        $canSeeAll    = $this->canSeeAllReviews($user);

        $templates = $canSeeAll
            ? KpiTemplate::where('is_active', true)->get()
            : collect($autoTemplate ? [$autoTemplate] : []);

        $hasSupervisor = (bool) $user->supervisor_id;
        $canCreate     = $hasSupervisor && $templates->isNotEmpty();

        $reason = null;
        if (!$hasSupervisor) {
            $reason = 'You have no supervisor assigned. Please contact HR to set your supervisor before starting a KPI review.';
        } elseif ($templates->isEmpty()) {
            $reason = 'No KPI template is configured for your role. Please contact HR.';
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'can_create'    => $canCreate,
                'has_supervisor'=> $hasSupervisor,
                'reason'        => $reason,
                'auto_template' => $autoTemplate
                    ? ['id' => $autoTemplate->id, 'name' => $autoTemplate->name]
                    : null,
                // Templates picker is only meaningful for HR/Admin who may choose.
                'templates' => $canSeeAll
                    ? $templates->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values()
                    : [],
                'default_period_label' => now()->format('F Y'),
                'default_period_start' => now()->startOfMonth()->toDateString(),
                'default_period_end'   => now()->endOfMonth()->toDateString(),
            ],
        ]);
    }

    /**
     * POST performance — create a review + clone template items into ratings.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'kpi_template_id' => 'required|exists:kpi_templates,id',
            'period_label'    => 'required|string|max:60',
            'period_start'    => 'required|date',
            'period_end'      => 'required|date|after_or_equal:period_start',
        ]);

        // Enforce supervisor-must-exist rule before creating the review.
        if (!$user->supervisor_id) {
            return response()->json([
                'success' => false,
                'message' => 'You have no supervisor assigned. Please contact HR to set your supervisor before starting a KPI review.',
            ], 422);
        }

        // Prevent duplicates: one review per (employee, template, period_start).
        $existing = KpiReview::where('employee_id', $user->id)
            ->where('kpi_template_id', $data['kpi_template_id'])
            ->where('period_start', $data['period_start'])
            ->first();
        if ($existing) {
            return response()->json([
                'success' => true,
                'data'    => ['id' => $existing->id],
                'message' => 'A review for this period already exists. Opened it for you.',
            ]);
        }

        $template = KpiTemplate::with('items.section')->findOrFail($data['kpi_template_id']);

        $review = DB::transaction(function () use ($user, $data, $template) {
            $review = KpiReview::create([
                'review_number'   => KpiReview::generateReviewNumber(Carbon::parse($data['period_start'])),
                'kpi_template_id' => $template->id,
                'employee_id'     => $user->id,
                'supervisor_id'   => $user->supervisor_id,
                'period_label'    => $data['period_label'],
                'period_start'    => $data['period_start'],
                'period_end'      => $data['period_end'],
                'status'          => 'draft',
                'created_by'      => $user->id,
            ]);

            // Clone every template item into a rating row (snapshot).
            $rows  = [];
            $order = 1;
            foreach ($template->items as $item) {
                $rows[] = [
                    'kpi_review_id'         => $review->id,
                    'kpi_item_id'           => $item->id,
                    'kpa_snapshot'          => $item->kpa,
                    'measure_snapshot'      => $item->measure,
                    'target_snapshot'       => $item->target,
                    'weight_snapshot'       => $item->weight,
                    'section_code_snapshot' => $item->section->code,
                    'sort_order'            => $order++,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ];
            }
            DB::table('kpi_review_ratings')->insert($rows);
            return $review;
        });

        return response()->json([
            'success' => true,
            'data'    => ['id' => $review->id],
            'message' => "Review {$review->review_number} created. Fill in your self-assessment.",
        ], 201);
    }

    /**
     * GET performance/{id} — full review detail.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $performance = KpiReview::findOrFail($id);
        if (!$this->userCanView($performance, $request->user())) {
            return $this->forbidden();
        }

        $performance->load(['template.sections', 'ratings', 'employee.department', 'supervisor']);

        return response()->json([
            'success' => true,
            'data'    => $this->mapReviewDetail($performance, $request->user()),
        ]);
    }

    /**
     * PATCH performance/{id}/self — save / submit the self-assessment.
     */
    public function updateSelf(Request $request, int $id): JsonResponse
    {
        $performance = KpiReview::findOrFail($id);
        if (!$this->canSelfAssess($performance, $request->user())) {
            return $this->forbidden('Self-assessment is locked at this stage.');
        }

        $data = $request->validate([
            'ratings'              => 'required|array',
            'ratings.*.id'         => 'required|integer',
            'ratings.*.self_rate'  => 'nullable|numeric|min:0|max:100',
            'ratings.*.comment'    => 'nullable|string|max:2000',
            'achievements'         => 'nullable|string|max:5000',
            'areas_of_improvement' => 'nullable|string|max:5000',
            'training_needs'       => 'nullable|string|max:5000',
            'employee_comments'    => 'nullable|string|max:5000',
            'action'               => 'required|in:save,submit',
        ]);

        // Index the incoming rows by rating id for easy lookup.
        $byId = collect($data['ratings'])->keyBy('id');

        // Block submitting a blank self-assessment — every KPI must be rated.
        // Save is exempt so partial work can still be kept.
        if ($data['action'] === 'submit') {
            $missing = [];
            foreach ($performance->ratings as $rating) {
                $value = $byId[$rating->id]['self_rate'] ?? null;
                if ($value === null || $value === '') {
                    $missing[] = $rating->kpa_snapshot;
                }
            }
            if (!empty($missing)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please rate every KPI (0–100) before submitting. ' . count($missing) . ' row(s) are still blank. You can Save instead.',
                ], 422);
            }
        }

        DB::transaction(function () use ($performance, $data, $byId) {
            foreach ($byId as $ratingId => $values) {
                KpiReviewRating::where('id', $ratingId)
                    ->where('kpi_review_id', $performance->id)
                    ->update([
                        'self_rate' => $values['self_rate'] ?? null,
                        'comment'   => $values['comment']   ?? null,
                    ]);
            }
            $performance->update([
                'achievements'         => $data['achievements']         ?? null,
                'areas_of_improvement' => $data['areas_of_improvement'] ?? null,
                'training_needs'       => $data['training_needs']       ?? null,
                'employee_comments'    => $data['employee_comments']    ?? null,
            ]);
            $this->recalculateScores($performance->refresh());
        });

        if ($data['action'] === 'submit') {
            $result = $this->runSubmit($performance->refresh(), $request->user());
            if ($result !== null) {
                return $result; // error response
            }
            $performance->refresh();
            return $this->scoresResponse($performance, 'Submitted to your supervisor.');
        }

        return $this->scoresResponse($performance->refresh(), 'Self-assessment saved as draft.');
    }

    /**
     * POST performance/{id}/submit — lock + submit to supervisor via RingleSoft.
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        $performance = KpiReview::findOrFail($id);
        if (!$this->canSelfAssess($performance, $request->user())) {
            return $this->forbidden('Self-assessment is locked at this stage.');
        }

        $result = $this->runSubmit($performance, $request->user());
        if ($result !== null) {
            return $result;
        }
        return response()->json([
            'success' => true,
            'message' => 'Submitted to your supervisor.',
        ]);
    }

    /**
     * POST performance/{id}/recall — employee pulls back before supervisor acts.
     */
    public function recall(Request $request, int $id): JsonResponse
    {
        $performance = KpiReview::findOrFail($id);

        if ($performance->employee_id !== $request->user()->id) {
            return $this->forbidden('Only the submitter can recall their own review.');
        }
        if ($performance->status !== 'self_submitted') {
            return response()->json([
                'success' => false,
                'message' => 'Only submissions awaiting the supervisor can be recalled.',
            ], 422);
        }
        if ($performance->supervisor_reviewed_at) {
            return response()->json([
                'success' => false,
                'message' => 'Your supervisor has already started reviewing. Ask them to "Return for Changes" instead.',
            ], 422);
        }

        // Walk RingleSoft's state back too so the approval engine matches our model.
        try {
            $performance->discard('Recalled by employee for correction.', $request->user());
        } catch (\Throwable $e) {
            Log::warning("KPI recall: RingleSoft discard failed for review {$performance->id}: " . $e->getMessage());
        }
        $performance->update(['status' => 'draft', 'self_submitted_at' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Submission recalled. You can edit and re-submit.',
        ]);
    }

    /**
     * PATCH performance/{id}/review — reviewer (supervisor/MD/CEO) action.
     */
    public function updateReviewer(Request $request, int $id): JsonResponse
    {
        $performance = KpiReview::findOrFail($id);
        if (!$this->canReview($performance, $request->user())) {
            return $this->forbidden('No reviewer action available at this stage.');
        }
        $stage = $this->reviewerStageFor($performance);

        $data = $request->validate([
            'ratings'                   => 'required|array',
            'ratings.*.id'              => 'required|integer',
            'ratings.*.supervisor_rate' => 'nullable|numeric|min:0|max:100',
            'ratings.*.overall_rate'    => 'nullable|numeric|min:0|max:100',
            'ratings.*.comment'         => 'nullable|string|max:2000',
            'stage_comment'             => 'nullable|string|max:5000',
            'action'                    => 'required|in:save,approve,reject,return',
            'reason'                    => 'required_if:action,reject,return|nullable|string|max:2000',
        ]);

        $byId = collect($data['ratings'])->keyBy('id');

        // When forwarding, require a rate on every row at the stage's owning column.
        // Save is exempt — the reviewer might leave and come back.
        if ($data['action'] === 'approve') {
            $missing = [];
            $columnName  = $stage === 'supervisor' ? 'supervisor_rate' : 'overall_rate';
            $columnLabel = $stage === 'supervisor' ? 'Supervisor' : 'Overall';
            foreach ($performance->ratings as $rating) {
                $value = $byId[$rating->id][$columnName] ?? null;
                if ($value === null || $value === '') {
                    $missing[] = $rating->kpa_snapshot;
                }
            }
            if (!empty($missing)) {
                return response()->json([
                    'success' => false,
                    'message' => "Please fill the {$columnLabel} rate on all KPIs before approving. " . count($missing) . ' row(s) are blank.',
                ], 422);
            }
        }

        // Map the single stage_comment into the correct column for this stage.
        $stageColumn = match ($stage) {
            'supervisor' => 'supervisor_comments',
            'md'         => 'md_comments',
            'ceo'        => 'ceo_comments',
            default      => null,
        };

        DB::transaction(function () use ($performance, $data, $byId, $stageColumn) {
            foreach ($byId as $ratingId => $values) {
                KpiReviewRating::where('id', $ratingId)
                    ->where('kpi_review_id', $performance->id)
                    ->update([
                        'supervisor_rate' => $values['supervisor_rate'] ?? null,
                        'overall_rate'    => $values['overall_rate']    ?? null,
                        'comment'         => $values['comment']         ?? null,
                    ]);
            }
            if ($stageColumn && array_key_exists('stage_comment', $data) && $data['stage_comment'] !== null) {
                $performance->fill([$stageColumn => $data['stage_comment']])->save();
            }
            $this->recalculateScores($performance->refresh());
        });

        // Advance the workflow if requested.
        switch ($data['action']) {
            case 'approve':
                $err = $this->approveStage($performance->refresh(), $stage, $request->user());
                $msg = 'Approved.';
                break;
            case 'reject':
                $err = $this->rejectReview($performance->refresh(), $data['reason'] ?? null, $request->user());
                $msg = 'Review rejected.';
                break;
            case 'return':
                $err = $this->returnReview($performance->refresh(), $data['reason'] ?? null, $request->user());
                $msg = 'Returned to employee for changes.';
                break;
            default:
                $err = null;
                $msg = 'Review saved.';
        }

        if ($err !== null) {
            return $err;
        }

        $performance->refresh();
        return $this->scoresResponse($performance, $msg, true);
    }

    // =====================================================================
    // Workflow helpers (mirror KpiController exactly: RingleSoft + status sync)
    // =====================================================================

    /**
     * Lock self-assessment and submit to the supervisor via RingleSoft.
     * Returns a JsonResponse on error, or null on success.
     */
    protected function runSubmit(KpiReview $performance, $user): ?JsonResponse
    {
        if (!$performance->supervisor_id) {
            return response()->json([
                'success' => false,
                'message' => 'You have no supervisor assigned. Please contact HR before submitting.',
            ], 422);
        }

        try {
            // RingleSoft signature: submit(?Authenticatable $user = null) — NOT a comment string.
            $performance->submit($user);
            $performance->update([
                'status'            => 'self_submitted',
                'self_submitted_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error("KPI submit() failed for review {$performance->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit for review: ' . $e->getMessage(),
            ], 422);
        }
        return null;
    }

    /**
     * Approve current stage — advances RingleSoft and updates the convenience status.
     * Returns a JsonResponse on error, or null on success.
     */
    protected function approveStage(KpiReview $performance, string $stage, $user): ?JsonResponse
    {
        try {
            $performance->approve("Approved at {$stage} stage.", $user);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Approval failed: ' . $e->getMessage(),
            ], 422);
        }

        $stamps = [
            'supervisor' => ['status' => 'supervisor_reviewed', 'col' => 'supervisor_reviewed_at'],
            'md'         => ['status' => 'md_reviewed',         'col' => 'md_reviewed_at'],
            'ceo'        => ['status' => 'completed',           'col' => 'completed_at'],
        ];
        if (isset($stamps[$stage])) {
            $performance->update([
                'status'               => $stamps[$stage]['status'],
                $stamps[$stage]['col'] => now(),
            ]);
        }
        return null;
    }

    protected function rejectReview(KpiReview $performance, ?string $reason, $user): ?JsonResponse
    {
        try {
            $performance->reject($reason ?? 'Rejected.', $user);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reject failed: ' . $e->getMessage(),
            ], 422);
        }
        $performance->update(['status' => 'rejected']);
        return null;
    }

    protected function returnReview(KpiReview $performance, ?string $reason, $user): ?JsonResponse
    {
        try {
            $performance->return($reason ?? 'Returned for changes.', $user);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Return failed: ' . $e->getMessage(),
            ], 422);
        }
        $performance->update(['status' => 'returned']);
        return null;
    }

    /**
     * Calculate weighted totals across all ratings and write them back.
     * Each rating contributes (rate/100) × weight_snapshot, summed.
     * Identical to KpiController::recalculateScores.
     */
    protected function recalculateScores(KpiReview $performance): void
    {
        $totals = ['self' => 0, 'supervisor' => 0, 'overall' => 0];
        foreach ($performance->ratings as $r) {
            $w = (float) $r->weight_snapshot;
            $totals['self']       += $r->self_rate       !== null ? ((float) $r->self_rate / 100)       * $w : 0;
            $totals['supervisor'] += $r->supervisor_rate !== null ? ((float) $r->supervisor_rate / 100) * $w : 0;
            $totals['overall']    += $r->overall_rate    !== null ? ((float) $r->overall_rate / 100)    * $w : 0;
        }

        $grade = null;
        foreach (self::GRADE_BANDS as $band) {
            if ($totals['overall'] >= $band['min']) {
                $grade = $band['label'];
                break;
            }
        }

        $performance->update([
            'total_self_score'       => round($totals['self'], 2),
            'total_supervisor_score' => round($totals['supervisor'], 2),
            'total_overall_score'    => round($totals['overall'], 2),
            'grade_label'            => $grade,
        ]);
    }

    // =====================================================================
    // Permission / scope helpers (ported 1:1 from KpiController)
    // =====================================================================

    /**
     * Translate the current review state to the reviewer-stage string.
     */
    protected function reviewerStageFor(KpiReview $performance): string
    {
        return match ($performance->status) {
            'self_submitted'      => 'supervisor',
            'supervisor_reviewed' => 'md',
            'md_reviewed'         => 'ceo',
            default               => 'unknown',
        };
    }

    /**
     * Scope to "awaiting MY action" depending on which role the user has.
     */
    protected function scopeAwaitingFor($query, $user): void
    {
        $query->where(function ($q) use ($user) {
            // Supervisor step
            $q->where(function ($q2) use ($user) {
                $q2->where('status', 'self_submitted')->where('supervisor_id', $user->id);
            });
            // MD step
            if ($user->hasRole('Managing Director')) {
                $q->orWhere('status', 'supervisor_reviewed');
            }
            // CEO step
            if ($user->hasAnyRole(['CEO', 'Chief Executive Officer'])) {
                $q->orWhere('status', 'md_reviewed');
            }
        });
    }

    protected function awaitingCountFor($user): int
    {
        $q = KpiReview::query();
        $this->scopeAwaitingFor($q, $user);
        return $q->count();
    }

    protected function canSeeAllReviews($user): bool
    {
        return $user->hasAnyRole([
            'System Administrator', 'Managing Director', 'CEO', 'Chief Executive Officer', 'HR Generalist',
        ]);
    }

    /**
     * Pick the KPI template that matches the user's Spatie roles.
     */
    protected function resolveTemplateForUser($user): ?KpiTemplate
    {
        foreach ($user->roles as $role) {
            $tpl = KpiTemplate::forRoleId($role->id);
            if ($tpl) return $tpl;
        }
        return null;
    }

    /**
     * authorizeView — mirror of KpiController::authorizeView (boolean form).
     */
    protected function userCanView(KpiReview $performance, $user): bool
    {
        return $performance->employee_id === $user->id
            || $performance->supervisor_id === $user->id
            || $this->canSeeAllReviews($user);
    }

    /**
     * authorizeSelfAssess — mirror of KpiController::authorizeSelfAssess (boolean form).
     */
    protected function canSelfAssess(KpiReview $performance, $user): bool
    {
        return $performance->employee_id === $user->id
            && in_array($performance->status, ['draft', 'returned'], true);
    }

    /**
     * authorizeReviewer — mirror of KpiController::authorizeReviewer (boolean form).
     */
    protected function canReview(KpiReview $performance, $user): bool
    {
        switch ($this->reviewerStageFor($performance)) {
            case 'supervisor':
                return $performance->supervisor_id === $user->id;
            case 'md':
                return $user->hasRole('Managing Director');
            case 'ceo':
                return $user->hasAnyRole(['CEO', 'Chief Executive Officer']);
            default:
                return false;
        }
    }

    /**
     * Can the employee recall? (employee, self_submitted, supervisor not yet started)
     */
    protected function canRecall(KpiReview $performance, $user): bool
    {
        return $performance->employee_id === $user->id
            && $performance->status === 'self_submitted'
            && $performance->supervisor_reviewed_at === null;
    }

    // =====================================================================
    // Mappers (FROZEN JSON CONTRACT)
    // =====================================================================

    protected function mapReviewListRow(KpiReview $r, $user): array
    {
        return [
            'id'                     => $r->id,
            'review_number'          => $r->review_number,
            'employee'               => [
                'id'   => $r->employee->id ?? null,
                'name' => $r->employee->name ?? null,
            ],
            'template' => [
                'id'   => $r->template->id ?? null,
                'name' => $r->template->name ?? null,
            ],
            'period_label'           => $r->period_label,
            'status'                 => $r->status,
            'status_label'           => self::STATUS_LABELS[$r->status] ?? ucfirst((string) $r->status),
            'total_self_score'       => $this->num($r->total_self_score),
            'total_supervisor_score' => $this->num($r->total_supervisor_score),
            'total_overall_score'    => $this->num($r->total_overall_score),
            'grade_label'            => $r->grade_label,
            'can_fill'               => $this->canSelfAssess($r, $user),
            'can_review'             => $this->canReview($r, $user),
            'can_recall'             => $this->canRecall($r, $user),
        ];
    }

    protected function mapReviewDetail(KpiReview $performance, $user): array
    {
        $reviewStage = $this->reviewerStageFor($performance);
        $canReview   = $this->canReview($performance, $user);

        return [
            'id'            => $performance->id,
            'review_number' => $performance->review_number,
            'status'        => $performance->status,
            'status_label'  => self::STATUS_LABELS[$performance->status] ?? ucfirst((string) $performance->status),
            'period_label'  => $performance->period_label,
            'period_start'  => optional($performance->period_start)->toDateString(),
            'period_end'    => optional($performance->period_end)->toDateString(),
            'employee'      => [
                'id'         => $performance->employee->id ?? null,
                'name'       => $performance->employee->name ?? null,
                'department' => $performance->employee->department->name ?? null,
            ],
            'supervisor' => $performance->supervisor
                ? ['id' => $performance->supervisor->id, 'name' => $performance->supervisor->name]
                : null,
            'template' => [
                'id'   => $performance->template->id ?? null,
                'name' => $performance->template->name ?? null,
            ],
            'total_self_score'       => $this->num($performance->total_self_score),
            'total_supervisor_score' => $this->num($performance->total_supervisor_score),
            'total_overall_score'    => $this->num($performance->total_overall_score),
            'grade_label'            => $performance->grade_label,
            'footer' => [
                'achievements'         => $performance->achievements,
                'areas_of_improvement' => $performance->areas_of_improvement,
                'training_needs'       => $performance->training_needs,
                'employee_comments'    => $performance->employee_comments,
                'supervisor_comments'  => $performance->supervisor_comments,
                'md_comments'          => $performance->md_comments,
                'ceo_comments'         => $performance->ceo_comments,
            ],
            'timestamps' => [
                'self_submitted_at'      => optional($performance->self_submitted_at)->toIso8601String(),
                'supervisor_reviewed_at' => optional($performance->supervisor_reviewed_at)->toIso8601String(),
                'md_reviewed_at'         => optional($performance->md_reviewed_at)->toIso8601String(),
                'completed_at'           => optional($performance->completed_at)->toIso8601String(),
            ],
            'sections'    => $this->mapSections($performance),
            'permissions' => [
                'can_fill'     => $this->canSelfAssess($performance, $user),
                'can_recall'   => $this->canRecall($performance, $user),
                'can_review'   => $canReview,
                'review_stage' => ($canReview && in_array($reviewStage, ['supervisor', 'md', 'ceo'], true))
                    ? $reviewStage
                    : null,
            ],
            // Portal web route URL for the printable PDF.
            'pdf_url' => url("/performance/{$performance->id}/pdf"),
        ];
    }

    /**
     * Group rating rows by their section_code_snapshot, mirroring
     * KpiController::groupRatingsBySection (driven by template sections order).
     */
    protected function mapSections(KpiReview $performance): array
    {
        $sections = [];
        foreach ($performance->template->sections as $section) {
            $ratings = $performance->ratings
                ->where('section_code_snapshot', $section->code)
                ->values()
                ->map(fn (KpiReviewRating $r) => $this->mapRating($r))
                ->all();

            $sections[] = [
                'code'         => $section->code,
                'title'        => $section->title,
                'weight_total' => $this->num($section->weight_total),
                'ratings'      => $ratings,
            ];
        }
        return $sections;
    }

    protected function mapRating(KpiReviewRating $r): array
    {
        return [
            'id'              => $r->id,
            'kpa'             => $r->kpa_snapshot,
            'measure'         => $r->measure_snapshot,
            'target'          => $r->target_snapshot,
            'weight'          => $this->num($r->weight_snapshot),
            'section_code'    => $r->section_code_snapshot,
            'actual_achieved' => $r->actual_achieved,
            'self_rate'       => $this->num($r->self_rate),
            'supervisor_rate' => $this->num($r->supervisor_rate),
            'overall_rate'    => $this->num($r->overall_rate),
            'comment'         => $r->comment,
            'sort_order'      => $r->sort_order,
        ];
    }

    // =====================================================================
    // Small response helpers
    // =====================================================================

    /**
     * Standard scores payload used after save/submit/review.
     */
    protected function scoresResponse(KpiReview $performance, string $message, bool $withStatus = false): JsonResponse
    {
        $data = [
            'total_self_score'       => $this->num($performance->total_self_score),
            'total_supervisor_score' => $this->num($performance->total_supervisor_score),
            'total_overall_score'    => $this->num($performance->total_overall_score),
            'grade_label'            => $performance->grade_label,
        ];
        if ($withStatus) {
            $data['status'] = $performance->status;
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ]);
    }

    protected function forbidden(string $message = 'This action is unauthorized.'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], 403);
    }

    /**
     * Cast decimal/string DB values to JSON numbers (or null).
     */
    protected function num($value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    // =====================================================================
    // Review lifecycle holes (destroy + pdf) — mirror KpiController
    // =====================================================================

    /**
     * DELETE performance/{id} — destroy a KPI review.
     *
     * Mirrors KpiController::destroy. The ONLY true Spatie permission check in
     * the module: can('Delete Performance Reviews'). Completed reviews are an
     * audit record and require ?force=1 (or force:true body) to remove.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $performance = KpiReview::findOrFail($id);

        if (!$request->user()->can('Delete Performance Reviews')) {
            return $this->forbidden('You do not have permission to delete performance reviews.');
        }

        if ($performance->status === 'completed' && !$request->boolean('force')) {
            return response()->json([
                'success'       => false,
                'requires_force'=> true,
                'message'       => 'This review is already completed and serves as an audit record. Tick "Force delete completed review" to remove it.',
            ], 422);
        }

        $label = $performance->review_number . ' (' . ($performance->employee->name ?? 'unknown') . ')';
        // Cascade FKs on kpi_review_ratings/attachments wipe children automatically.
        $performance->delete();

        return response()->json([
            'success' => true,
            'message' => "Review {$label} deleted.",
        ]);
    }

    /**
     * GET performance/{id}/pdf — return the printable review PDF as base64.
     *
     * Mirrors KpiController::pdf (same view + grouping), but returns
     * {pdf_base64, filename} JSON instead of streaming so the mobile client
     * can save/share it. Gated by the same authorizeView rule.
     */
    public function pdf(Request $request, int $id): JsonResponse
    {
        $performance = KpiReview::findOrFail($id);
        if (!$this->userCanView($performance, $request->user())) {
            return $this->forbidden();
        }

        $performance->load([
            'template.sections.items', 'employee.department', 'supervisor', 'ratings',
            'approvals.user', 'approvals.processApprovalFlowStep',
        ]);

        $grouped = $this->groupRatingsBySection($performance);

        $pdf = PDF::loadView('pages.kpi.pdf', [
            'review'         => $performance,
            'groupedRatings' => $grouped,
        ])->setPaper('a4', 'portrait');

        $filename = "KPI-{$performance->employee->name}-{$performance->period_label}.pdf";
        $filename = str_replace([' ', '/'], ['_', '-'], $filename);

        return response()->json([
            'success' => true,
            'data'    => [
                'filename'   => $filename,
                'pdf_base64' => base64_encode($pdf->output()),
            ],
        ]);
    }

    /**
     * Group rating rows by their section_code_snapshot for the PDF view.
     * Mirrors KpiController::groupRatingsBySection exactly.
     */
    protected function groupRatingsBySection(KpiReview $performance): array
    {
        $grouped = [];
        foreach ($performance->template->sections as $section) {
            $grouped[$section->code] = [
                'section' => $section,
                'ratings' => $performance->ratings->where('section_code_snapshot', $section->code)->values(),
            ];
        }
        return $grouped;
    }

    // =====================================================================
    // KPI Template administration (mirror KpiController template methods)
    // Every method is gated by authorizeTemplates() — role-based, 403 JSON.
    // =====================================================================

    /**
     * GET performance/templates — list templates + roles without a template yet.
     * Mirrors KpiController::templatesIndex.
     */
    public function templatesIndex(Request $request): JsonResponse
    {
        if ($resp = $this->authorizeTemplates($request->user())) {
            return $resp;
        }

        $templates = KpiTemplate::with(['role', 'sections.items'])->get()->map(function ($t) {
            $items = $t->sections->flatMap->items;
            return [
                'id'           => $t->id,
                'code'         => $t->code,
                'name'         => $t->name,
                'role'         => $t->role->name ?? '—',
                'frequency'    => $t->frequency,
                'is_active'    => (bool) $t->is_active,
                'item_count'   => $items->count(),
                'total_weight' => (float) $items->sum('weight'),
            ];
        })->values();

        // Roles that don't yet own a template — the only valid targets for a new one.
        $usedRoleIds    = KpiTemplate::whereNotNull('role_id')->pluck('role_id');
        $availableRoles = Role::whereNotIn('id', $usedRoleIds)->orderBy('name')->get()
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'templates'       => $templates,
                'available_roles' => $availableRoles,
            ],
        ]);
    }

    /**
     * POST performance/templates — create a template with a seeded Section A
     * (30%, 14 common items) + empty Section B (70%). Mirrors KpiController::templateStore.
     */
    public function templateStore(Request $request): JsonResponse
    {
        if ($resp = $this->authorizeTemplates($request->user())) {
            return $resp;
        }

        $data = $request->validate([
            'name'      => 'required|string|max:120',
            // One template per role — block roles that already own one.
            'role_id'   => 'required|exists:roles,id|unique:kpi_templates,role_id',
            'frequency' => 'required|in:monthly,quarterly,biannual,annual',
        ]);

        $code = $this->makeTemplateCode($data['name']);

        $template = DB::transaction(function () use ($data, $code) {
            $template = KpiTemplate::create([
                'code'      => $code,
                'name'      => $data['name'],
                'role_id'   => $data['role_id'],
                'frequency' => $data['frequency'],
                'is_active' => true,
            ]);

            // Section A — shared "General Performance" (30%), pre-filled with the 14 common items.
            $sectionA = $template->sections()->create([
                'code' => 'A', 'title' => 'General Performance',
                'weight_total' => 30, 'sort_order' => 1, 'is_common' => true,
            ]);
            $order = 1;
            foreach (KpiTemplate::COMMON_SECTION_A_ITEMS as $i) {
                $sectionA->items()->create([
                    'kpi_template_id' => $template->id,
                    'kpa'             => $i['kpa'],
                    'measure'         => $i['measure'],
                    'target'          => $i['target'],
                    'weight'          => $i['weight'],
                    'sort_order'      => $order++,
                    'is_active'       => true,
                ]);
            }

            // Section B — role-specific "Departmental Objectives" (70%), left empty.
            $template->sections()->create([
                'code' => 'B', 'title' => 'Departmental Objectives',
                'weight_total' => 70, 'sort_order' => 2, 'is_common' => false,
            ]);

            return $template;
        });

        return response()->json([
            'success' => true,
            'data'    => ['id' => $template->id],
            'message' => 'Template created. Section A is pre-filled — now add the departmental KPIs in Section B (should total 70%).',
        ], 201);
    }

    /**
     * GET performance/templates/{id} — template with sections[].items[].
     * Mirrors KpiController::templateShow.
     */
    public function templateShow(Request $request, int $id): JsonResponse
    {
        if ($resp = $this->authorizeTemplates($request->user())) {
            return $resp;
        }

        $template = KpiTemplate::with(['role', 'sections.items'])->findOrFail($id);
        $allItems = $template->sections->flatMap->items;

        return response()->json([
            'success' => true,
            'data'    => [
                'id'           => $template->id,
                'code'         => $template->code,
                'name'         => $template->name,
                'role'         => $template->role->name ?? '—',
                'role_id'      => $template->role_id,
                'frequency'    => $template->frequency,
                'description'  => $template->description,
                'is_active'    => (bool) $template->is_active,
                'item_count'   => $allItems->count(),
                'total_weight' => (float) $allItems->sum('weight'),
                'sections'     => $template->sections->map(function ($s) {
                    return [
                        'id'           => $s->id,
                        'code'         => $s->code,
                        'title'        => $s->title,
                        'weight_total' => (float) $s->weight_total,
                        'is_common'    => (bool) $s->is_common,
                        'sort_order'   => $s->sort_order,
                        'items'        => $s->items->map(function ($it) {
                            return [
                                'id'                 => $it->id,
                                'kpa'                => $it->kpa,
                                'measure'            => $it->measure,
                                'target'             => $it->target,
                                'weight'             => (float) $it->weight,
                                'measurement_method' => $it->measurement_method,
                                'sort_order'         => $it->sort_order,
                            ];
                        })->values(),
                        'items_weight' => (float) $s->items->sum('weight'),
                    ];
                })->values(),
            ],
        ]);
    }

    /**
     * PATCH performance/templates/{id} — update editable metadata (name,
     * frequency, description, is_active). NOT code/role_id.
     * Mirrors KpiController::templateUpdate.
     */
    public function templateUpdate(Request $request, int $id): JsonResponse
    {
        if ($resp = $this->authorizeTemplates($request->user())) {
            return $resp;
        }

        $template = KpiTemplate::findOrFail($id);
        $data = $request->validate([
            'name'        => 'required|string|max:120',
            'frequency'   => 'required|in:monthly,quarterly,biannual,annual',
            'description' => 'nullable|string|max:2000',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $template->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Template details updated.',
        ]);
    }

    /**
     * POST performance/templates/{id}/items — add a KPI item to a section.
     * Mirrors KpiController::templateStoreItem.
     */
    public function templateStoreItem(Request $request, int $id): JsonResponse
    {
        if ($resp = $this->authorizeTemplates($request->user())) {
            return $resp;
        }

        $template = KpiTemplate::findOrFail($id);
        $data = $request->validate([
            'kpi_template_section_id' => 'required|exists:kpi_template_sections,id',
            'kpa'                     => 'required|string|max:255',
            'measure'                 => 'required|string|max:2000',
            'target'                  => 'nullable|string|max:1000',
            'weight'                  => 'required|numeric|min:0|max:100',
            'measurement_method'      => 'nullable|string|max:255',
        ]);
        $data['kpi_template_id'] = $template->id;
        $data['sort_order']      = ($template->items()->max('sort_order') ?? 0) + 1;
        $item = KpiItem::create($data);

        return response()->json([
            'success' => true,
            'data'    => ['id' => $item->id],
            'message' => 'KPI item added.',
        ], 201);
    }

    /**
     * PATCH performance/templates/{id}/items — bulk-save edited rows.
     * The ownership filter on kpi_template_id ignores rows that don't belong
     * to this template. Mirrors KpiController::templateUpdateItems.
     */
    public function templateUpdateItems(Request $request, int $id): JsonResponse
    {
        if ($resp = $this->authorizeTemplates($request->user())) {
            return $resp;
        }

        $template = KpiTemplate::findOrFail($id);
        $data = $request->validate([
            'items'           => 'required|array',
            'items.*.kpa'     => 'required|string|max:255',
            'items.*.measure' => 'required|string|max:2000',
            'items.*.target'  => 'nullable|string|max:1000',
            'items.*.weight'  => 'required|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($template, $data) {
            foreach ($data['items'] as $itemId => $values) {
                KpiItem::where('id', $itemId)
                    ->where('kpi_template_id', $template->id)
                    ->update([
                        'kpa'     => $values['kpa'],
                        'measure' => $values['measure'],
                        'target'  => $values['target'] ?? null,
                        'weight'  => $values['weight'],
                    ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Section saved — ' . count($data['items']) . ' item(s) updated.',
        ]);
    }

    /**
     * DELETE performance/templates/{id}/items/{itemId} — remove a KPI item.
     * 404 if the item doesn't belong to this template.
     * Mirrors KpiController::templateDeleteItem.
     */
    public function templateDeleteItem(Request $request, int $id, int $itemId): JsonResponse
    {
        if ($resp = $this->authorizeTemplates($request->user())) {
            return $resp;
        }

        $template = KpiTemplate::findOrFail($id);
        $item     = KpiItem::findOrFail($itemId);
        if ($item->kpi_template_id !== $template->id) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found on this template.',
            ], 404);
        }
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'KPI item deleted.',
        ]);
    }

    /**
     * Build a unique, code-safe identifier for a new template from its name.
     * Mirrors KpiController::makeTemplateCode.
     */
    protected function makeTemplateCode(string $name): string
    {
        $base = Str::slug($name) ?: 'template';
        $base = substr($base, 0, 55);
        $code = $base;
        $n = 2;
        while (KpiTemplate::where('code', $code)->exists()) {
            $code = $base . '-' . $n++;
        }
        return $code;
    }

    /**
     * Template management gate — role-based (mirrors KpiController::authorizeTemplates).
     * Returns a 403 JsonResponse when unauthorized, or null when allowed.
     */
    protected function authorizeTemplates($user): ?JsonResponse
    {
        if (!$user->hasAnyRole([
            'System Administrator', 'HR Generalist', 'Managing Director', 'CEO', 'Chief Executive Officer',
        ])) {
            return $this->forbidden('You do not have permission to manage KPI templates.');
        }
        return null;
    }
}
