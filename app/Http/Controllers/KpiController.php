<?php

namespace App\Http\Controllers;

use App\Models\KpiItem;
use App\Models\KpiReview;
use App\Models\KpiReviewRating;
use App\Models\KpiTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Performance / KPI review workflow.
 *
 *   draft  (employee editing)
 *     → self_submitted        (POST submit → RingleSoft step 1 = personal supervisor)
 *     → supervisor_reviewed   (supervisor approves → step 2 = MD)
 *     → md_reviewed           (MD approves → step 3 = CEO)
 *     → completed             (CEO approves; onApprovalCompleted writes status)
 *
 * Index lists are scoped per logged-in user's role so each user sees only what
 * they're authorised to act on (own reviews, items awaiting their review, or all
 * reviews if they have the HR/Admin permission).
 */
class KpiController extends Controller
{
    /**
     * Rating band labels — translated from total_overall_score (0..100).
     */
    private const GRADE_BANDS = [
        ['min' => 90, 'label' => 'Excellent'],
        ['min' => 80, 'label' => 'Very Good'],
        ['min' => 70, 'label' => 'Good'],
        ['min' => 60, 'label' => 'Average'],
        ['min' => 50, 'label' => 'Poor'],
        ['min' => 0,  'label' => 'Ungraded'],
    ];

    public function index(Request $request)
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
                    abort(403);
                }
                break;
            case 'mine':
            default:
                $query->where('employee_id', $user->id);
        }

        $reviews = $query->paginate(20)->withQueryString();
        return view('pages.kpi.index', [
            'reviews'     => $reviews,
            'tab'         => $tab,
            'canSeeAll'   => $this->canSeeAllReviews($user),
            'awaitingCount' => $this->awaitingCountFor($user),
            'myOpenCount'   => KpiReview::where('employee_id', $user->id)->whereNotIn('status', ['completed', 'rejected'])->count(),
        ]);
    }

    /**
     * Show the create-review form. The template is auto-detected from the user's
     * Spatie role; if no template matches, the user is asked to pick one (HR only).
     */
    public function create(Request $request)
    {
        $user = $request->user();
        $autoTemplate = $this->resolveTemplateForUser($user);

        $templates = $this->canSeeAllReviews($user)
            ? KpiTemplate::where('is_active', true)->get()
            : collect($autoTemplate ? [$autoTemplate] : []);

        if ($templates->isEmpty()) {
            return back()->with('error', 'No KPI template is configured for your role. Please contact HR.');
        }

        return view('pages.kpi.create', [
            'templates'    => $templates,
            'autoTemplate' => $autoTemplate,
            'defaultPeriod'=> now()->format('F Y'),
        ]);
    }

    /**
     * Persist a new review and clone the template's items as ratings.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'kpi_template_id' => 'required|exists:kpi_templates,id',
            'period_label'    => 'required|string|max:60',
            'period_start'    => 'required|date',
            'period_end'      => 'required|date|after_or_equal:period_start',
        ]);

        // Enforce supervisor-must-exist rule before creating the review
        if (!$user->supervisor_id) {
            return back()->withInput()->with('error',
                'You have no supervisor assigned. Please contact HR to set your supervisor before starting a KPI review.');
        }

        // Prevent duplicates: one review per (employee, template, period_start)
        $existing = KpiReview::where('employee_id', $user->id)
            ->where('kpi_template_id', $data['kpi_template_id'])
            ->where('period_start', $data['period_start'])
            ->first();
        if ($existing) {
            return redirect()->route('performance.show', $existing)
                ->with('info', 'A review for this period already exists. Opened it for you.');
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

            // Clone every template item into a rating row (snapshot)
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

        return redirect()->route('performance.self', $review)
            ->with('success', "Review {$review->review_number} created. Fill in your self-assessment.");
    }

    public function show(KpiReview $performance)
    {
        $this->authorizeView($performance);
        $performance->load(['template.sections', 'ratings', 'employee', 'supervisor']);
        return view('pages.kpi.show', [
            'review' => $performance,
            'groupedRatings' => $this->groupRatingsBySection($performance),
        ]);
    }

    /**
     * Employee self-assessment form.
     */
    public function selfAssess(KpiReview $performance)
    {
        $this->authorizeSelfAssess($performance);
        $performance->load(['template.sections', 'ratings']);
        return view('pages.kpi.self_assess', [
            'review'         => $performance,
            'groupedRatings' => $this->groupRatingsBySection($performance),
        ]);
    }

    /**
     * Persist self-assessment rates and footer fields. Saves a draft; does NOT submit.
     */
    public function updateSelf(Request $request, KpiReview $performance)
    {
        $this->authorizeSelfAssess($performance);

        $data = $request->validate([
            'ratings'              => 'required|array',
            'ratings.*.self_rate'  => 'nullable|numeric|min:0|max:100',
            'ratings.*.comment'    => 'nullable|string|max:2000',
            'achievements'         => 'nullable|string|max:5000',
            'areas_of_improvement' => 'nullable|string|max:5000',
            'training_needs'       => 'nullable|string|max:5000',
            'employee_comments'    => 'nullable|string|max:5000',
        ]);

        DB::transaction(function () use ($performance, $data) {
            foreach ($data['ratings'] as $ratingId => $values) {
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

        if ($request->input('action') === 'submit') {
            return $this->submitForReview($request, $performance->refresh());
        }
        return back()->with('success', 'Self-assessment saved as draft.');
    }

    /**
     * Lock self-assessment and submit to the supervisor via RingleSoft.
     */
    public function submitForReview(Request $request, KpiReview $performance)
    {
        $this->authorizeSelfAssess($performance);

        if (!$performance->supervisor_id) {
            return back()->with('error',
                'You have no supervisor assigned. Please contact HR before submitting.');
        }

        try {
            // RingleSoft signature: submit(?Authenticatable $user = null) — NOT a comment string
            $performance->submit($request->user());
            $performance->update([
                'status'            => 'self_submitted',
                'self_submitted_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::error("KPI submit() failed for review {$performance->id}: " . $e->getMessage());
            return back()->with('error', 'Failed to submit for review: ' . $e->getMessage());
        }
        return redirect()->route('performance.show', $performance)
            ->with('success', "Submitted to your supervisor.");
    }

    /**
     * Supervisor / MD / CEO review form.
     */
    public function reviewerForm(KpiReview $performance)
    {
        $this->authorizeReviewer($performance);
        $performance->load(['template.sections', 'ratings']);
        return view('pages.kpi.reviewer', [
            'review'         => $performance,
            'groupedRatings' => $this->groupRatingsBySection($performance),
            'stage'          => $this->reviewerStageFor($performance),
        ]);
    }

    /**
     * Save reviewer-side ratings + comment, then either save-draft or approve to next step.
     */
    public function updateReviewer(Request $request, KpiReview $performance)
    {
        $this->authorizeReviewer($performance);
        $stage = $this->reviewerStageFor($performance);

        $data = $request->validate([
            'ratings'                  => 'required|array',
            'ratings.*.supervisor_rate'=> 'nullable|numeric|min:0|max:100',
            'ratings.*.overall_rate'   => 'nullable|numeric|min:0|max:100',
            'ratings.*.comment'        => 'nullable|string|max:2000',
            'supervisor_comments'      => 'nullable|string|max:5000',
            'md_comments'              => 'nullable|string|max:5000',
            'ceo_comments'             => 'nullable|string|max:5000',
            'action'                   => 'required|in:save,approve,reject,return',
            'rejection_reason'         => 'required_if:action,reject,return|nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($performance, $data, $stage) {
            foreach ($data['ratings'] as $ratingId => $values) {
                KpiReviewRating::where('id', $ratingId)
                    ->where('kpi_review_id', $performance->id)
                    ->update([
                        'supervisor_rate' => $values['supervisor_rate'] ?? null,
                        'overall_rate'    => $values['overall_rate']    ?? null,
                        'comment'         => $values['comment']         ?? null,
                    ]);
            }
            $performance->fill(array_filter([
                'supervisor_comments' => $data['supervisor_comments'] ?? null,
                'md_comments'         => $data['md_comments']         ?? null,
                'ceo_comments'        => $data['ceo_comments']        ?? null,
            ], fn ($v) => $v !== null))->save();
            $this->recalculateScores($performance->refresh());
        });

        // Advance the workflow if requested
        switch ($data['action']) {
            case 'approve':
                return $this->approveStage($performance->refresh(), $stage);
            case 'reject':
                return $this->rejectReview($performance->refresh(), $data['rejection_reason']);
            case 'return':
                return $this->returnReview($performance->refresh(), $data['rejection_reason']);
            default:
                return back()->with('success', 'Review saved.');
        }
    }

    /**
     * Approve current stage — advances RingleSoft and updates the convenience status.
     */
    protected function approveStage(KpiReview $performance, string $stage)
    {
        try {
            $performance->approve("Approved at {$stage} stage.", auth()->user());
        } catch (\Throwable $e) {
            return back()->with('error', 'Approval failed: ' . $e->getMessage());
        }

        $stamps = [
            'supervisor' => ['status' => 'supervisor_reviewed', 'col' => 'supervisor_reviewed_at'],
            'md'         => ['status' => 'md_reviewed',         'col' => 'md_reviewed_at'],
            'ceo'        => ['status' => 'completed',           'col' => 'completed_at'],
        ];
        if (isset($stamps[$stage])) {
            $performance->update([
                'status'           => $stamps[$stage]['status'],
                $stamps[$stage]['col'] => now(),
            ]);
        }
        return redirect()->route('performance.show', $performance)
            ->with('success', 'Approved.');
    }

    protected function rejectReview(KpiReview $performance, ?string $reason)
    {
        try {
            $performance->reject($reason ?? 'Rejected.', auth()->user());
        } catch (\Throwable $e) {
            return back()->with('error', 'Reject failed: ' . $e->getMessage());
        }
        $performance->update(['status' => 'rejected']);
        return redirect()->route('performance.show', $performance)
            ->with('success', 'Review rejected.');
    }

    protected function returnReview(KpiReview $performance, ?string $reason)
    {
        try {
            $performance->return($reason ?? 'Returned for changes.', auth()->user());
        } catch (\Throwable $e) {
            return back()->with('error', 'Return failed: ' . $e->getMessage());
        }
        $performance->update(['status' => 'returned']);
        return redirect()->route('performance.show', $performance)
            ->with('success', 'Returned to employee for changes.');
    }

    /**
     * Calculate weighted totals across all ratings and write them back to the review.
     * Each rating contributes (rate/100) × weight, summed across all items. Possible
     * total range is 0..(sum of weights) which equals 0..100 by template construction.
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

    /**
     * Group rating rows by their section_code_snapshot for the UI's section accordion.
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
     * Pick the KPI template that matches the user's Spatie roles. Returns null
     * if the user has no role or no template was seeded for their role.
     */
    protected function resolveTemplateForUser($user): ?KpiTemplate
    {
        foreach ($user->roles as $role) {
            $tpl = KpiTemplate::forRoleId($role->id);
            if ($tpl) return $tpl;
        }
        return null;
    }

    protected function authorizeView(KpiReview $performance): void
    {
        $user = auth()->user();
        $allowed = $performance->employee_id === $user->id
            || $performance->supervisor_id === $user->id
            || $this->canSeeAllReviews($user);
        if (!$allowed) {
            abort(403);
        }
    }

    protected function authorizeSelfAssess(KpiReview $performance): void
    {
        if ($performance->employee_id !== auth()->id()) {
            abort(403);
        }
        if (!in_array($performance->status, ['draft', 'returned'], true)) {
            abort(403, 'Self-assessment is locked at this stage.');
        }
    }

    protected function authorizeReviewer(KpiReview $performance): void
    {
        $user = auth()->user();
        $stage = $this->reviewerStageFor($performance);
        switch ($stage) {
            case 'supervisor':
                if ($performance->supervisor_id !== $user->id) abort(403);
                break;
            case 'md':
                if (!$user->hasRole('Managing Director')) abort(403);
                break;
            case 'ceo':
                if (!$user->hasAnyRole(['CEO', 'Chief Executive Officer'])) abort(403);
                break;
            default:
                abort(403, 'No reviewer action available at this stage.');
        }
    }
}
