<?php

namespace App\Http\Controllers;

use App\Models\KpiItem;
use App\Models\KpiReview;
use App\Models\KpiReviewRating;
use App\Models\KpiTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
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

        // Build the "Top Performers" board for the All tab (HR/Admin view only).
        // Picks completed reviews in the current period_start month and ranks by
        // total_overall_score. Empty if no reviews are finalised yet this month.
        $topPerformers = collect();
        if ($tab === 'all' && $this->canSeeAllReviews($user)) {
            $topPerformers = $this->topPerformersForCurrentMonth();
        }

        return view('pages.kpi.index', [
            'reviews'       => $reviews,
            'tab'           => $tab,
            'canSeeAll'     => $this->canSeeAllReviews($user),
            'awaitingCount' => $this->awaitingCountFor($user),
            'myOpenCount'   => KpiReview::where('employee_id', $user->id)->whereNotIn('status', ['completed', 'rejected'])->count(),
            'topPerformers' => $topPerformers,
        ]);
    }

    /**
     * Top 5 finalised reviews for the current calendar month, ranked by overall score.
     */
    protected function topPerformersForCurrentMonth(): \Illuminate\Support\Collection
    {
        return KpiReview::with(['employee', 'template'])
            ->whereNotNull('total_overall_score')
            ->where('status', 'completed')
            ->whereBetween('period_start', [now()->startOfMonth(), now()->endOfMonth()])
            ->orderByDesc('total_overall_score')
            ->limit(5)
            ->get();
    }

    /**
     * Stream the review as a printable PDF in the same layout as the company's
     * Word-doc KPI form. Visible to the employee, their supervisor, and HR/Admin.
     */
    public function pdf(KpiReview $performance)
    {
        $this->authorizeView($performance);
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
        return $pdf->stream($filename);
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
                'created_by'      => $user->id,
            ]);
            // status is intentionally not mass-assigned — see KpiReview::$fillable.
            // DB default is 'draft'.

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

        // Per-row cap: the rate is the weighted contribution, so it can't exceed the row's weight.
        if ($err = $this->enforceRateCap($performance, $data['ratings'], ['self_rate'])) {
            return back()->withInput()->with('error', $err);
        }

        // Block submitting a blank self-assessment — every KPI must be rated.
        // Save Draft is exempt, so partial work can still be kept.
        if ($request->input('action') === 'submit') {
            $missing = [];
            foreach ($performance->ratings as $rating) {
                $value = $data['ratings'][$rating->id]['self_rate'] ?? null;
                if ($value === null || $value === '') {
                    $missing[] = $rating->kpa_snapshot;
                }
            }
            if (!empty($missing)) {
                return back()->withInput()->with('error',
                    'Please rate every KPI before submitting. ' . count($missing) . ' row(s) are still blank. You can Save Draft instead.');
            }
        }

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
            DB::transaction(function () use ($performance, $request) {
                $performance->submit($request->user());
                $performance->status            = 'self_submitted';
                $performance->self_submitted_at = now();
                $performance->save();
            });
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

        // Per-row cap: the rate is the weighted contribution, so it can't exceed the row's weight.
        if ($err = $this->enforceRateCap($performance, $data['ratings'], ['supervisor_rate', 'overall_rate'])) {
            return back()->withInput()->with('error', $err);
        }

        // When forwarding, require a rate on every row at the stage's owning column.
        // Save Draft is exempt — the reviewer might leave and come back.
        if ($data['action'] === 'approve') {
            $stage = $this->reviewerStageFor($performance);
            $columnName  = $stage === 'supervisor' ? 'supervisor_rate' : 'overall_rate';
            $columnLabel = $stage === 'supervisor' ? 'Supervisor' : 'Overall';
            $missing = [];
            foreach ($performance->ratings as $rating) {
                $value = $data['ratings'][$rating->id][$columnName] ?? null;
                if ($value === null || $value === '') {
                    $missing[] = $rating->kpa_snapshot;
                }
            }
            if (!empty($missing)) {
                return back()
                    ->withInput()
                    ->with('error', "Please fill the {$columnLabel} rate on all KPIs before approving. " . count($missing) . ' row(s) are blank.');
            }
        }

        try {
            $result = DB::transaction(function () use ($performance, $data) {
                // Row-level lock: prevents double-approve race when two reviewers
                // (or one impatient double-clicker) hit Approve concurrently.
                $locked = KpiReview::whereKey($performance->id)->lockForUpdate()->firstOrFail();
                $this->authorizeReviewer($locked);
                $stage = $this->reviewerStageFor($locked);

                foreach ($data['ratings'] as $ratingId => $values) {
                    KpiReviewRating::where('id', $ratingId)
                        ->where('kpi_review_id', $locked->id)
                        ->update([
                            'supervisor_rate' => $values['supervisor_rate'] ?? null,
                            'overall_rate'    => $values['overall_rate']    ?? null,
                            'comment'         => $values['comment']         ?? null,
                        ]);
                }
                $locked->fill(array_filter([
                    'supervisor_comments' => $data['supervisor_comments'] ?? null,
                    'md_comments'         => $data['md_comments']         ?? null,
                    'ceo_comments'        => $data['ceo_comments']        ?? null,
                ], fn ($v) => $v !== null))->save();

                // Only finalise totals when the review actually advances. A draft
                // save with half-blank rows would otherwise persist artificially
                // low totals + a misleading grade onto the index/Top Performers.
                if ($data['action'] !== 'save') {
                    $this->recalculateScores($locked->refresh());
                }

                return match ($data['action']) {
                    'approve' => $this->approveStage($locked->refresh(), $stage),
                    'reject'  => $this->rejectReview($locked->refresh(), $data['rejection_reason']),
                    'return'  => $this->returnReview($locked->refresh(), $data['rejection_reason']),
                    default   => null,
                };
            });
        } catch (\Throwable $e) {
            \Log::error("KPI updateReviewer failed for review {$performance->id}: " . $e->getMessage());
            return back()->withInput()->with('error', 'Save failed: ' . $e->getMessage());
        }

        return $result ?? back()->with('success', 'Review saved.');
    }

    /**
     * Approve current stage — advances RingleSoft and updates the convenience status.
     * Must be called inside a DB::transaction so the RingleSoft write and the
     * status/timestamp stamp commit (or roll back) together.
     */
    protected function approveStage(KpiReview $performance, string $stage)
    {
        $performance->approve("Approved at {$stage} stage.", auth()->user());

        $stamps = [
            'supervisor' => ['status' => 'supervisor_reviewed', 'col' => 'supervisor_reviewed_at'],
            'md'         => ['status' => 'md_reviewed',         'col' => 'md_reviewed_at'],
            'ceo'        => ['status' => 'completed',           'col' => 'completed_at'],
        ];
        if (isset($stamps[$stage])) {
            $performance->status                   = $stamps[$stage]['status'];
            $performance->{$stamps[$stage]['col']} = now();
            $performance->save();
        }
        return redirect()->route('performance.show', $performance)
            ->with('success', 'Approved.');
    }

    protected function rejectReview(KpiReview $performance, ?string $reason)
    {
        $performance->reject($reason ?? 'Rejected.', auth()->user());
        $performance->status = 'rejected';
        $performance->save();
        return redirect()->route('performance.show', $performance)
            ->with('success', 'Review rejected.');
    }

    protected function returnReview(KpiReview $performance, ?string $reason)
    {
        $performance->return($reason ?? 'Returned for changes.', auth()->user());
        $performance->status = 'returned';
        $performance->save();
        return redirect()->route('performance.show', $performance)
            ->with('success', 'Returned to employee for changes.');
    }

    /**
     * Calculate totals across all ratings and write them back to the review.
     *
     * Each rate is the row's *weighted contribution* — capped at the row's
     * weight_snapshot — so totals are a straight sum and naturally land on a
     * 0..(sum of weights) scale (≈ 0..100 when the template weights sum to 100).
     * Grade bands are looked up against that same 0..100 scale.
     */
    protected function recalculateScores(KpiReview $performance): void
    {
        $totals = ['self' => 0, 'supervisor' => 0, 'overall' => 0];
        foreach ($performance->ratings as $r) {
            $totals['self']       += (float) ($r->self_rate       ?? 0);
            $totals['supervisor'] += (float) ($r->supervisor_rate ?? 0);
            $totals['overall']    += (float) ($r->overall_rate    ?? 0);
        }

        $grade = null;
        foreach (self::GRADE_BANDS as $band) {
            if ($totals['overall'] >= $band['min']) {
                $grade = $band['label'];
                break;
            }
        }

        $performance->total_self_score       = round($totals['self'], 2);
        $performance->total_supervisor_score = round($totals['supervisor'], 2);
        $performance->total_overall_score    = round($totals['overall'], 2);
        $performance->grade_label            = $grade;
        $performance->save();
    }

    /**
     * Verify every submitted rate is within its row's weight_snapshot. Returns
     * a human-readable error string when something exceeds the cap, otherwise null.
     */
    protected function enforceRateCap(KpiReview $performance, array $ratings, array $columns): ?string
    {
        $caps = $performance->ratings->keyBy('id');
        foreach ($ratings as $ratingId => $values) {
            $rating = $caps[$ratingId] ?? null;
            if (!$rating) continue;
            $max = (float) $rating->weight_snapshot;
            foreach ($columns as $col) {
                $val = $values[$col] ?? null;
                if ($val === null || $val === '') continue;
                if ((float) $val > $max + 0.0001) {
                    $maxFmt = rtrim(rtrim(number_format($max, 2), '0'), '.');
                    return "Rate for \"{$rating->kpa_snapshot}\" is {$val} but cannot exceed its weight ({$maxFmt}).";
                }
            }
        }
        return null;
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
     *
     * Separation of duties: a user never sees their own review in this list, and
     * an MD/CEO who happened to act as the supervisor on a given review can't
     * approve it again at the next stage.
     */
    protected function scopeAwaitingFor($query, $user): void
    {
        $query->where(function ($q) use ($user) {
            // Supervisor step — assigned supervisor only
            $q->where(function ($q2) use ($user) {
                $q2->where('status', 'self_submitted')
                   ->where('supervisor_id', $user->id);
            });
            // MD step
            if ($user->hasRole('Managing Director')) {
                $q->orWhere(function ($q2) use ($user) {
                    $q2->where('status', 'supervisor_reviewed')
                       ->where('supervisor_id', '!=', $user->id)
                       ->where('employee_id',   '!=', $user->id);
                });
            }
            // CEO step
            if ($user->hasAnyRole(['CEO', 'Chief Executive Officer'])) {
                $q->orWhere(function ($q2) use ($user) {
                    $q2->where('status', 'md_reviewed')
                       ->where('supervisor_id', '!=', $user->id)
                       ->where('employee_id',   '!=', $user->id);
                });
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
        // 'rejected' is editable too — without this, a rejected review is a dead
        // end and the employee has no path back into the flow short of HR cloning.
        if (!in_array($performance->status, ['draft', 'returned', 'rejected'], true)) {
            abort(403, 'Self-assessment is locked at this stage.');
        }
    }

    /**
     * Employee recalls their own submission BEFORE the supervisor starts.
     *
     * Models the real-world "oops, I clicked submit too fast and noticed a
     * mistake" case. Reverts status to draft so the self-assessment form
     * unlocks again. Only allowed:
     *   - by the employee themselves (not anyone else)
     *   - when status is self_submitted (not after the supervisor has rated)
     *   - when no supervisor_reviewed_at exists yet (supervisor hasn't acted)
     *
     * The supervisor's "Return for Changes" still exists for cases where the
     * supervisor has already started reviewing.
     */
    public function recall(Request $request, KpiReview $performance)
    {
        if ($performance->employee_id !== $request->user()->id) {
            abort(403, 'Only the submitter can recall their own review.');
        }
        if ($performance->status !== 'self_submitted') {
            return back()->with('error', 'Only submissions awaiting the supervisor can be recalled.');
        }
        if ($performance->supervisor_reviewed_at) {
            return back()->with('error',
                'Your supervisor has already started reviewing. Ask them to "Return for Changes" instead.');
        }

        // Walk RingleSoft's state back too so the approval engine matches our model
        try {
            $performance->discard('Recalled by employee for correction.', $request->user());
        } catch (\Throwable $e) {
            \Log::warning("KPI recall: RingleSoft discard failed for review {$performance->id}: " . $e->getMessage());
        }
        $performance->update(['status' => 'draft', 'self_submitted_at' => null]);

        return redirect()->route('performance.self', $performance)
            ->with('success', 'Submission recalled. You can edit and re-submit.');
    }

    /**
     * Destroy a KPI review. Permission-gated and confirms via the UI.
     *
     * Cascade FKs on kpi_review_ratings and kpi_review_attachments mean
     * deleting the parent automatically wipes both — no manual loop needed.
     *
     * Safety policy: completed (= signed off by CEO) reviews are an audit
     * record and shouldn't be silently removed; force=1 is required to
     * delete them, surfaced as a separate "confirm twice" UX in the view.
     */
    public function destroy(Request $request, KpiReview $performance)
    {
        if (!$request->user()->can('Delete Performance Reviews')) {
            abort(403, 'You do not have permission to delete performance reviews.');
        }

        if ($performance->status === 'completed' && !$request->boolean('force')) {
            return back()->with('error',
                'This review is already completed and serves as an audit record. ' .
                'Tick "Force delete completed review" to remove it.');
        }

        $label = $performance->review_number . ' (' . ($performance->employee->name ?? 'unknown') . ')';
        $performance->delete();

        return redirect()->route('performance.index', ['tab' => $request->query('back_tab', 'all')])
            ->with('success', "Review {$label} deleted.");
    }

    // ---------------------------------------------------------------------
    // Template administration (System Admin only)
    // ---------------------------------------------------------------------

    public function templatesIndex()
    {
        $this->authorizeTemplates();
        $templates = KpiTemplate::with(['role', 'sections.items'])->get()->map(function ($t) {
            $items = $t->sections->flatMap->items;
            return (object) [
                'id'          => $t->id,
                'code'        => $t->code,
                'name'        => $t->name,
                'role'        => $t->role->name ?? '—',
                'frequency'   => $t->frequency,
                'is_active'   => $t->is_active,
                'item_count'  => $items->count(),
                'total_weight'=> (float) $items->sum('weight'),
            ];
        });

        // Roles that don't yet own a template — the only valid targets for a new
        // one, since KpiTemplate::forRoleId() matches a single template per role.
        $usedRoleIds   = KpiTemplate::whereNotNull('role_id')->pluck('role_id');
        $availableRoles = Role::whereNotIn('id', $usedRoleIds)->orderBy('name')->get();

        return view('pages.kpi.templates_index', compact('templates', 'availableRoles'));
    }

    /**
     * Create a KPI template for a department/role that doesn't have one yet.
     * Mirrors the seeder's shape: a shared Section A (30%, pre-filled with the
     * common items) plus an empty Section B (70%) for departmental KPIs.
     */
    public function templateStore(Request $request)
    {
        $this->authorizeTemplates();

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

            // Section B — role-specific "Departmental Objectives" (70%), left empty to fill in.
            $template->sections()->create([
                'code' => 'B', 'title' => 'Departmental Objectives',
                'weight_total' => 70, 'sort_order' => 2, 'is_common' => false,
            ]);

            return $template;
        });

        return redirect()->route('performance.templates.show', $template->id)
            ->with('success', 'Template created. Section A is pre-filled — now add the departmental KPIs in Section B (should total 70%).');
    }

    /**
     * Build a unique, URL/code-safe identifier for a new template from its name.
     *
     * The `code` column is `string(60) unique` and is used as a stable, human-
     * readable key (e.g. 'architect', 'quantity-surveyor'). This is YOUR call:
     * decide how to slugify the name and how to guarantee uniqueness against
     * existing rows. See the insight in chat for the trade-offs to weigh.
     */
    protected function makeTemplateCode(string $name): string
    {
        $base = \Illuminate\Support\Str::slug($name) ?: 'template';
        $base = substr($base, 0, 55);              // leave room for a '-NN' suffix
        $code = $base;
        $n = 2;
        while (KpiTemplate::where('code', $code)->exists()) {
            $code = $base . '-' . $n++;
        }
        return $code;
    }

    public function templateShow(KpiTemplate $template)
    {
        $this->authorizeTemplates();
        $template->load(['role', 'sections.items']);
        return view('pages.kpi.templates_show', compact('template'));
    }

    /**
     * Update a template's editable metadata (name, frequency, description, active
     * flag). The `code` and `role_id` are intentionally NOT editable here — code
     * is the stable seeder/lookup key, and reassigning a role is a heavier
     * operation with its own one-template-per-role guard.
     */
    public function templateUpdate(Request $request, KpiTemplate $template)
    {
        $this->authorizeTemplates();
        $data = $request->validate([
            'name'        => 'required|string|max:120',
            'frequency'   => 'required|in:monthly,quarterly,biannual,annual',
            'description' => 'nullable|string|max:2000',
        ]);
        // Unchecked checkbox sends nothing, so coerce explicitly rather than rely on the payload.
        $data['is_active'] = $request->boolean('is_active');
        $template->update($data);
        return back()->with('success', 'Template details updated.');
    }

    public function templateStoreItem(Request $request, KpiTemplate $template)
    {
        $this->authorizeTemplates();
        $data = $request->validate([
            'kpi_template_section_id' => 'required|exists:kpi_template_sections,id',
            'kpa'                     => 'required|string|max:255',
            'measure'                 => 'required|string|max:2000',
            'target'                  => 'nullable|string|max:1000',
            'weight'                  => 'required|numeric|min:0|max:100',
            'measurement_method'      => 'nullable|string|max:255',
        ]);
        $data['kpi_template_id'] = $template->id;
        $data['sort_order'] = ($template->items()->max('sort_order') ?? 0) + 1;
        KpiItem::create($data);
        return back()->with('success', 'KPI item added.');
    }

    /**
     * Save every edited row in one section at once. Each row is posted as
     * items[<itemId>][kpa|measure|target|weight]; the ownership filter on
     * kpi_template_id ignores any id that doesn't belong to this template.
     */
    public function templateUpdateItems(Request $request, KpiTemplate $template)
    {
        $this->authorizeTemplates();
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

        return back()->with('success', 'Section saved — ' . count($data['items']) . ' item(s) updated.');
    }

    public function templateDeleteItem(KpiTemplate $template, KpiItem $item)
    {
        $this->authorizeTemplates();
        if ($item->kpi_template_id !== $template->id) {
            abort(404);
        }
        $item->delete();
        return back()->with('success', 'KPI item deleted.');
    }

    protected function authorizeTemplates(): void
    {
        // Mirror canSeeAllReviews() — HR/MD/CEO are pointed at templates by the
        // create-review error message, so they need write access too.
        if (!auth()->user()->hasAnyRole([
            'System Administrator', 'HR Generalist', 'Managing Director', 'CEO', 'Chief Executive Officer',
        ])) {
            abort(403, 'You do not have permission to manage KPI templates.');
        }
    }

    /**
     * Separation of duties at each reviewer stage:
     *   - Nobody approves their own review (employee_id check).
     *   - An MD/CEO who already acted as the personal supervisor of a review
     *     cannot also approve it at the later MD/CEO stage.
     */
    protected function authorizeReviewer(KpiReview $performance): void
    {
        $user = auth()->user();
        $stage = $this->reviewerStageFor($performance);
        if ($performance->employee_id === $user->id) {
            abort(403, 'You cannot review your own performance.');
        }
        switch ($stage) {
            case 'supervisor':
                if ($performance->supervisor_id !== $user->id) abort(403);
                break;
            case 'md':
                if (!$user->hasRole('Managing Director')) abort(403);
                if ($performance->supervisor_id === $user->id) {
                    abort(403, 'You already acted as supervisor on this review; another MD must approve at this stage.');
                }
                break;
            case 'ceo':
                if (!$user->hasAnyRole(['CEO', 'Chief Executive Officer'])) abort(403);
                if ($performance->supervisor_id === $user->id) {
                    abort(403, 'You already acted as supervisor on this review; another CEO must approve.');
                }
                break;
            default:
                abort(403, 'No reviewer action available at this stage.');
        }
    }
}
