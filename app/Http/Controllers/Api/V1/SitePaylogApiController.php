<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentChannel;
use App\Models\Project;
use App\Models\Site;
use App\Models\SitePaylog;
use App\Models\SitePaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Mobile API for the Site Paylog module.
 *
 * Mirrors web {@see \App\Http\Controllers\SitePaylogController}. Approvals run on
 * the RingleSoft Approvable trait (Procurement VERIFY -> Managing Director APPROVE),
 * then Finance records the payment (permission 'Process Site Payment') to mark PAID.
 *
 * NOTE: this touches SitePaylog / SitePaymentRequest ONLY. The unrelated
 * App\Models\SitePayment (SiteDailyReport feature) is never referenced here.
 */
class SitePaylogApiController extends Controller
{
    // ---------------------------------------------------------------------
    // Reference data
    // ---------------------------------------------------------------------

    /**
     * GET /api/v1/site-paylog/reference-data
     * Sites, projects, active channels, categories and request statuses for
     * the create form and filter dropdowns.
     */
    public function referenceData(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'sites' => Site::active()->orderBy('name')->get(['id', 'name'])->map(fn ($s) => [
                        'id'   => $s->id,
                        'name' => $s->name,
                    ]),
                    'projects' => Project::orderBy('project_name')->get(['id', 'project_name', 'document_number'])->map(fn ($p) => [
                        'id'              => $p->id,
                        'name'            => $p->project_name,
                        'document_number' => $p->document_number,
                    ]),
                    'channels' => PaymentChannel::active()->ordered()->get(['id', 'name', 'type'])->map(fn ($c) => [
                        'id'   => $c->id,
                        'name' => $c->name,
                        'type' => $c->type,
                    ]),
                    'categories' => collect(SitePaylog::categories())->map(fn ($label, $value) => [
                        'value' => $value,
                        'label' => $label,
                    ])->values(),
                    'statuses' => [
                        ['value' => SitePaymentRequest::STATUS_PENDING,  'label' => 'Pending'],
                        ['value' => SitePaymentRequest::STATUS_APPROVED, 'label' => 'Approved'],
                        ['value' => SitePaymentRequest::STATUS_PAID,     'label' => 'Paid'],
                        ['value' => SitePaymentRequest::STATUS_REJECTED, 'label' => 'Rejected'],
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError('reference data', $e);
        }
    }

    // ---------------------------------------------------------------------
    // Payment Channels (create + delete only; is_active forced true)
    // ---------------------------------------------------------------------

    /**
     * GET /api/v1/site-paylog/channels
     */
    public function channels(Request $request): JsonResponse
    {
        if (!$request->user()->can('Payment Channels')) {
            return $this->permissionDenied();
        }

        try {
            $channels = PaymentChannel::ordered()->get(['id', 'name', 'type', 'is_active', 'sort_order'])
                ->map(fn (PaymentChannel $c) => [
                    'id'        => $c->id,
                    'name'      => $c->name,
                    'type'      => $c->type,
                    'is_active' => (bool) $c->is_active,
                ]);

            return response()->json(['success' => true, 'data' => $channels]);
        } catch (\Throwable $e) {
            return $this->serverError('payment channels', $e);
        }
    }

    /**
     * POST /api/v1/site-paylog/channels
     */
    public function storeChannel(Request $request): JsonResponse
    {
        if (!$request->user()->can('Payment Channels')) {
            return $this->permissionDenied();
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|string|max:255',
            ]);

            $channel = PaymentChannel::create([
                'name'       => $validated['name'],
                'type'       => $validated['type'],
                'is_active'  => true,
                'sort_order' => (int) (PaymentChannel::max('sort_order') ?? 0) + 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment channel created.',
                'data'    => [
                    'id'        => $channel->id,
                    'name'      => $channel->name,
                    'type'      => $channel->type,
                    'is_active' => (bool) $channel->is_active,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e);
        } catch (\Throwable $e) {
            return $this->serverError('create payment channel', $e);
        }
    }

    /**
     * DELETE /api/v1/site-paylog/channels/{id}
     */
    public function destroyChannel(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->can('Payment Channels')) {
            return $this->permissionDenied();
        }

        try {
            $channel = PaymentChannel::findOrFail($id);
            $channel->delete();

            return response()->json(['success' => true, 'message' => 'Payment channel deleted.']);
        } catch (\Throwable $e) {
            return $this->serverError('delete payment channel', $e);
        }
    }

    // ---------------------------------------------------------------------
    // Reports (read SitePaylog LINE rows)
    // ---------------------------------------------------------------------

    /**
     * GET /api/v1/site-paylog/reports/daily?site_id=&date=
     * One-day report for a site: material vs labour line rows + totals.
     */
    public function dailyReport(Request $request): JsonResponse
    {
        if (!$request->user()->can('Daily Payment Report')) {
            return $this->permissionDenied();
        }

        try {
            $validated = $request->validate([
                'site_id' => 'required|exists:sites,id',
                'date'    => 'nullable|date',
            ]);

            $siteId = $validated['site_id'];
            $date   = $validated['date'] ?? date('Y-m-d');

            $site = Site::find($siteId);

            $payments = SitePaylog::with(['channel', 'creator'])
                ->forSite($siteId)
                ->forDate($date)
                ->orderBy('category')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'site'   => $site ? ['id' => $site->id, 'name' => $site->name] : null,
                    'date'   => $date,
                    'rows'   => $payments->map(fn (SitePaylog $p) => $this->transformLine($p, true))->values(),
                    'totals' => [
                        'material' => (float) $payments->where('category', 'material')->sum('amount'),
                        'labour'   => (float) $payments->where('category', 'labour')->sum('amount'),
                        'all'      => (float) $payments->sum('amount'),
                    ],
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e);
        } catch (\Throwable $e) {
            return $this->serverError('daily payment report', $e);
        }
    }

    /**
     * GET /api/v1/site-paylog/reports/monthly?site_id=&month=YYYY-MM
     * Monthly roll-up: per-category summary + full ledger + grand total.
     */
    public function monthlyReport(Request $request): JsonResponse
    {
        if (!$request->user()->can('Monthly Payment Report')) {
            return $this->permissionDenied();
        }

        try {
            $validated = $request->validate([
                'site_id' => 'required|exists:sites,id',
                'month'   => 'nullable|date_format:Y-m',
            ]);

            $siteId = $validated['site_id'];
            $month  = $validated['month'] ?? date('Y-m');
            [$year, $monthNum] = array_pad(explode('-', $month), 2, null);

            $site = Site::find($siteId);

            $payments = collect();
            if ($year && $monthNum) {
                $payments = SitePaylog::with('channel')
                    ->forSite($siteId)
                    ->forMonth($year, $monthNum)
                    ->orderBy('payment_date')
                    ->orderBy('id')
                    ->get();
            }

            $summary = $payments
                ->groupBy('category')
                ->map(fn ($rows, $category) => [
                    'category' => ucfirst((string) $category),
                    'count'    => $rows->count(),
                    'total'    => (float) $rows->sum('amount'),
                ])
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'site'        => $site ? ['id' => $site->id, 'name' => $site->name] : null,
                    'month'       => $month,
                    'summary'     => $summary,
                    'ledger'      => $payments->map(fn (SitePaylog $p) => $this->transformLine($p, true))->values(),
                    'grand_total' => (float) $payments->sum('amount'),
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e);
        } catch (\Throwable $e) {
            return $this->serverError('monthly payment report', $e);
        }
    }

    // ---------------------------------------------------------------------
    // Payment Requests
    // ---------------------------------------------------------------------

    /**
     * GET /api/v1/site-paylog/requests?site_id=&status=&per_page=
     * Paginated list of payment requests (Payment Requests screen).
     */
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->can('Payment Requests')) {
            return $this->permissionDenied();
        }

        try {
            $query = SitePaymentRequest::with(['site', 'project', 'creator', 'approvalStatus', 'lines'])
                ->orderByDesc('id');

            if ($request->filled('site_id')) {
                $query->forSite($request->input('site_id'));
            }
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            $perPage = (int) ($request->input('per_page', 25));
            $requests = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $requests->getCollection()
                        ->map(fn (SitePaymentRequest $r) => $this->transformRequest($r))
                        ->values(),
                    'meta' => [
                        'current_page' => $requests->currentPage(),
                        'last_page'    => $requests->lastPage(),
                        'per_page'     => $requests->perPage(),
                        'total'        => $requests->total(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError('payment requests', $e);
        }
    }

    /**
     * GET /api/v1/site-paylog/daily-summary?site_id=&date=
     * That day's payment requests for a site + material/labour/all totals
     * (Daily Payments screen). Not paginated.
     */
    public function dailySummary(Request $request): JsonResponse
    {
        if (!$request->user()->can('Daily Payments')) {
            return $this->permissionDenied();
        }

        try {
            $validated = $request->validate([
                'site_id' => 'required|exists:sites,id',
                'date'    => 'nullable|date',
            ]);

            $siteId = $validated['site_id'];
            $date   = $validated['date'] ?? date('Y-m-d');

            $site = Site::find($siteId);

            $requests = SitePaymentRequest::with(['lines.channel', 'creator', 'project', 'approvalStatus'])
                ->forSite($siteId)
                ->forDate($date)
                ->orderByDesc('id')
                ->get();

            $lines = $requests->flatMap->lines;

            return response()->json([
                'success' => true,
                'data' => [
                    'site'     => $site ? ['id' => $site->id, 'name' => $site->name] : null,
                    'date'     => $date,
                    'requests' => $requests->map(fn (SitePaymentRequest $r) => $this->transformRequest($r))->values(),
                    'totals'   => [
                        'material' => (float) $lines->where('category', 'material')->sum('amount'),
                        'labour'   => (float) $lines->where('category', 'labour')->sum('amount'),
                        'all'      => (float) $lines->sum('amount'),
                    ],
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e);
        } catch (\Throwable $e) {
            return $this->serverError('daily summary', $e);
        }
    }

    /**
     * GET /api/v1/site-paylog/requests/{id}
     * Header + lines + files + server-computed action flags.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->can('Payment Requests')) {
            return $this->permissionDenied();
        }

        try {
            $paymentRequest = SitePaymentRequest::with([
                'site', 'project', 'creator', 'payer',
                'lines.channel', 'files.uploader', 'approvalStatus',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data'    => $this->transformRequest($paymentRequest, true, $request->user()),
            ]);
        } catch (\Throwable $e) {
            return $this->serverError('payment request', $e);
        }
    }

    /**
     * POST /api/v1/site-paylog/requests  (multipart)
     * Create one payment request (header) + N lines + optional documents, then
     * submit it into the RingleSoft approval flow (-> Procurement VERIFY).
     */
    public function storeBulk(Request $request): JsonResponse
    {
        if (!$request->user()->can('Daily Payments')) {
            return $this->permissionDenied();
        }

        try {
            $validated = $request->validate([
                'site_id'                       => 'required|exists:sites,id',
                'project_id'                    => 'nullable|exists:projects,id',
                'payment_date'                  => 'required|date',
                'payments'                      => 'required|array|min:1',
                'payments.*.payee_name'         => 'required|string|max:255',
                'payments.*.reason'             => 'required|string|max:255',
                'payments.*.category'           => 'required|in:material,labour',
                'payments.*.payment_channel_id' => 'nullable|exists:payment_channels,id',
                'payments.*.account_name'       => 'nullable|string|max:255',
                'payments.*.amount'             => 'required|numeric|min:0.01',
                'documents'                     => 'nullable|array',
                'documents.*'                   => 'file|mimes:png,jpg,jpeg,pdf,doc,docx,xls,xlsx|max:4048',
            ]);

            $userId = $request->user()->id;

            $paymentRequest = DB::transaction(function () use ($request, $validated, $userId) {
                $total = collect($validated['payments'])
                    ->sum(fn ($row) => (float) str_replace(',', '', (string) $row['amount']));

                $paymentRequest = SitePaymentRequest::create([
                    'request_number' => SitePaymentRequest::generateRequestNumber(),
                    'site_id'        => $validated['site_id'],
                    'project_id'     => $validated['project_id'] ?? null,
                    'payment_date'   => $validated['payment_date'],
                    'total_amount'   => $total,
                    'status'         => SitePaymentRequest::STATUS_PENDING,
                    'created_by'     => $userId,
                ]);

                foreach ($validated['payments'] as $row) {
                    $paymentRequest->lines()->create([
                        'site_id'            => $validated['site_id'],
                        'project_id'         => $validated['project_id'] ?? null,
                        'payment_date'       => $validated['payment_date'],
                        'category'           => $row['category'],
                        'payee_name'         => $row['payee_name'],
                        'reason'             => $row['reason'],
                        'payment_channel_id' => $row['payment_channel_id'] ?? null,
                        'account_name'       => $row['account_name'] ?? null,
                        'amount'             => (float) str_replace(',', '', (string) $row['amount']),
                        'status'             => 'SUBMITTED',
                        'created_by'         => $userId,
                    ]);
                }

                foreach ($request->file('documents', []) as $file) {
                    $stored = $file->store('uploads/site_payment_requests', 'public');
                    $paymentRequest->files()->create([
                        'file_path'     => '/storage/' . $stored,
                        'original_name' => $file->getClientOriginalName(),
                        'uploaded_by'   => $userId,
                    ]);
                }

                return $paymentRequest;
            });

            // Enter the approval flow (fires ProcessSubmittedEvent -> notifies Procurement).
            $paymentRequest->submit();

            $paymentRequest->load([
                'site', 'project', 'creator', 'payer',
                'lines.channel', 'files.uploader', 'approvalStatus',
            ]);

            return response()->json([
                'success' => true,
                'message' => "Payment request {$paymentRequest->request_number} submitted for approval.",
                'data'    => $this->transformRequest($paymentRequest, true, $request->user()),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e);
        } catch (\Throwable $e) {
            return $this->serverError('create payment request', $e);
        }
    }

    /**
     * DELETE /api/v1/site-paylog/requests/{id}
     * Creator-only, and only while approval has not started.
     */
    public function destroyRequest(Request $request, int $id): JsonResponse
    {
        try {
            $paymentRequest = SitePaymentRequest::with('approvalStatus')->findOrFail($id);

            if ($paymentRequest->created_by !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the creator can delete this request.',
                ], 403);
            }

            if ($paymentRequest->isApprovalStarted() || $paymentRequest->isFullyApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request is already in the approval process and cannot be deleted.',
                ], 422);
            }

            $paymentRequest->forceDelete(); // cascades lines + files + approval rows

            return response()->json(['success' => true, 'message' => 'Payment request deleted.']);
        } catch (\Throwable $e) {
            return $this->serverError('delete payment request', $e);
        }
    }

    // ---------------------------------------------------------------------
    // Approval actions (RingleSoft; role-gated in-code)
    // ---------------------------------------------------------------------

    /**
     * POST /api/v1/site-paylog/requests/{id}/submit
     * Re-enter the flow (normally auto-fired on create; exposed for retries).
     */
    public function submit(Request $request, int $id): JsonResponse
    {
        try {
            $paymentRequest = SitePaymentRequest::with('approvalStatus')->findOrFail($id);

            if (!$paymentRequest->canBeSubmittedBy($request->user())) {
                return $this->permissionDenied();
            }

            $paymentRequest->submit();

            return $this->respondWithRequest($paymentRequest, $request->user(), 'Payment request submitted for approval.');
        } catch (\Throwable $e) {
            return $this->serverError('submit payment request', $e);
        }
    }

    /**
     * POST /api/v1/site-paylog/requests/{id}/approve
     * Advances the next pending step (VERIFY by Procurement, then APPROVE by MD).
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate(['comment' => 'nullable|string|max:1000']);

            $paymentRequest = SitePaymentRequest::with('approvalStatus')->findOrFail($id);

            if (!$paymentRequest->canBeApprovedBy($request->user())) {
                return $this->permissionDenied();
            }

            $paymentRequest->approve($request->input('comment'));

            return $this->respondWithRequest($paymentRequest, $request->user(), 'Payment request approved.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e);
        } catch (\Throwable $e) {
            return $this->serverError('approve payment request', $e);
        }
    }

    /**
     * POST /api/v1/site-paylog/requests/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate(['comment' => 'required|string|max:1000']);

            $paymentRequest = SitePaymentRequest::with('approvalStatus')->findOrFail($id);

            if (!$paymentRequest->canBeApprovedBy($request->user())) {
                return $this->permissionDenied();
            }

            $paymentRequest->reject($validated['comment']);

            return $this->respondWithRequest($paymentRequest, $request->user(), 'Payment request rejected.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e);
        } catch (\Throwable $e) {
            return $this->serverError('reject payment request', $e);
        }
    }

    /**
     * POST /api/v1/site-paylog/requests/{id}/return
     */
    public function returnRequest(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate(['comment' => 'required|string|max:1000']);

            $paymentRequest = SitePaymentRequest::with('approvalStatus')->findOrFail($id);

            if (!$paymentRequest->canBeApprovedBy($request->user())) {
                return $this->permissionDenied();
            }

            $paymentRequest->return($validated['comment']);

            return $this->respondWithRequest($paymentRequest, $request->user(), 'Payment request returned.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e);
        } catch (\Throwable $e) {
            return $this->serverError('return payment request', $e);
        }
    }

    /**
     * POST /api/v1/site-paylog/requests/{id}/discard
     */
    public function discard(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate(['comment' => 'required|string|max:1000']);

            $paymentRequest = SitePaymentRequest::with('approvalStatus')->findOrFail($id);

            if (!$paymentRequest->canBeApprovedBy($request->user())) {
                return $this->permissionDenied();
            }

            $paymentRequest->discard($validated['comment']);

            return $this->respondWithRequest($paymentRequest, $request->user(), 'Payment request discarded.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e);
        } catch (\Throwable $e) {
            return $this->serverError('discard payment request', $e);
        }
    }

    // ---------------------------------------------------------------------
    // Finance: record payment
    // ---------------------------------------------------------------------

    /**
     * POST /api/v1/site-paylog/requests/{id}/pay  (multipart)
     * Finance/Accountant records the actual payment against an approved request.
     */
    public function recordPayment(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->can('Process Site Payment')) {
            return $this->permissionDenied();
        }

        try {
            $paymentRequest = SitePaymentRequest::findOrFail($id);

            if (!$paymentRequest->isFullyApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request must be fully approved before payment can be recorded.',
                ], 422);
            }

            if ($paymentRequest->isPaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request has already been paid.',
                ], 422);
            }

            $validated = $request->validate([
                'payment_reference' => 'required|string|max:255',
                'payment_date'      => 'required|date',
                'payment_note'      => 'nullable|string|max:1000',
                'payment_slip'      => 'nullable|file|mimes:png,jpg,jpeg,pdf|max:4048',
            ]);

            $slipPath = $paymentRequest->payment_slip;
            if ($request->hasFile('payment_slip')) {
                $slipPath = '/storage/' . $request->file('payment_slip')->store('uploads/site_payment_slips', 'public');
            }

            $paymentRequest->update([
                'status'            => SitePaymentRequest::STATUS_PAID,
                'payment_reference' => $validated['payment_reference'],
                'payment_note'      => $validated['payment_note'] ?? null,
                'payment_slip'      => $slipPath,
                'paid_date'         => $validated['payment_date'],
                'paid_by'           => $request->user()->id,
            ]);

            // Close the loop: notify the initiator. Non-fatal if it fails.
            try {
                $creator = $paymentRequest->creator;
                if ($creator) {
                    $creator->notify(new \App\Notifications\ApprovalNotification([
                        'staff_id'         => $creator->id,
                        'link'             => "site-paylog/requests/{$paymentRequest->id}",
                        'title'            => 'Payment Issued',
                        'body'             => "Payment for request {$paymentRequest->request_number} ("
                            . number_format((float) $paymentRequest->total_amount) . ' TZS) has been processed by Finance'
                            . ($validated['payment_reference'] ? " (ref: {$validated['payment_reference']})" : '') . '.',
                        'document_id'      => (string) $paymentRequest->id,
                        'document_type_id' => '0',
                    ]));
                }
            } catch (\Throwable $e) {
                Log::warning('SitePaylog recordPayment notification failed: ' . $e->getMessage());
            }

            return $this->respondWithRequest($paymentRequest, $request->user(), "Payment recorded for {$paymentRequest->request_number}.");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e);
        } catch (\Throwable $e) {
            return $this->serverError('record payment', $e);
        }
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * Reload a request with its full graph and return the detail envelope.
     */
    private function respondWithRequest(SitePaymentRequest $paymentRequest, $user, string $message): JsonResponse
    {
        $paymentRequest->refresh()->load([
            'site', 'project', 'creator', 'payer',
            'lines.channel', 'files.uploader', 'approvalStatus',
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $this->transformRequest($paymentRequest, true, $user),
        ]);
    }

    /**
     * Shape a payment request header. $full adds lines, files and (when $user is
     * given) the server-computed action flags + next_step block.
     */
    private function transformRequest(SitePaymentRequest $r, bool $full = false, $user = null): array
    {
        $base = [
            'id'             => $r->id,
            'request_number' => $r->request_number,
            'site_id'        => $r->site_id,
            'site_name'      => $r->site?->name,
            'project_id'     => $r->project_id,
            'project_name'   => $r->project?->project_name,
            'payment_date'   => optional($r->payment_date)->format('Y-m-d'),
            'total_amount'   => (float) $r->total_amount,
            'status'         => $r->status,
            'display_status' => $r->displayStatus(),
            'status_color'   => $r->statusBadgeClass(),
            'created_by'     => $r->created_by,
            'creator_name'   => $r->creator?->name,
            'line_count'     => $r->relationLoaded('lines') ? $r->lines->count() : null,
            'created_at'     => optional($r->created_at)->toIso8601String(),
        ];

        if (!$full) {
            return $base;
        }

        $base['payer_name']        = $r->payer?->name;
        $base['payment_reference'] = $r->payment_reference;
        $base['payment_note']      = $r->payment_note;
        $base['payment_slip']      = $r->payment_slip;
        $base['paid_date']         = optional($r->paid_date)->format('Y-m-d');

        $base['lines'] = $r->relationLoaded('lines')
            ? $r->lines->map(fn (SitePaylog $l) => $this->transformLine($l))->values()
            : [];

        $base['files'] = $r->relationLoaded('files')
            ? $r->files->map(fn ($f) => [
                'id'            => $f->id,
                'file_path'     => $f->file_path,
                'original_name' => $f->original_name,
                'uploaded_by'   => $f->uploader?->name,
            ])->values()
            : [];

        // Next pending approval step (order / role / action: VERIFY|APPROVE).
        $nextStep = $r->nextApprovalStep();
        $base['next_step'] = $nextStep ? [
            'order'  => (int) $nextStep->order,
            'role'   => $nextStep->role?->name,
            'action' => $nextStep->action,
        ] : null;

        if ($user) {
            $base['can_submit'] = (bool) $r->canBeSubmittedBy($user);
            $base['can_approve'] = (bool) $r->canBeApprovedBy($user);
            $base['can_reject']  = (bool) $r->canBeApprovedBy($user);
            $base['can_record_payment'] = $r->isFullyApproved()
                && !$r->isPaid()
                && (bool) $user->can('Process Site Payment');
        } else {
            $base['can_submit'] = false;
            $base['can_approve'] = false;
            $base['can_reject'] = false;
            $base['can_record_payment'] = false;
        }

        return $base;
    }

    /**
     * Shape a single SitePaylog line row. $withMeta adds the logged-by name +
     * payment date (used by the report rows).
     */
    private function transformLine(SitePaylog $l, bool $withMeta = false): array
    {
        $row = [
            'id'                 => $l->id,
            'category'           => $l->category,
            'payee_name'         => $l->payee_name,
            'reason'             => $l->reason,
            'payment_channel_id' => $l->payment_channel_id,
            'channel_name'       => $l->channel?->name,
            'account_name'       => $l->account_name,
            'amount'             => (float) $l->amount,
        ];

        if ($withMeta) {
            $row['payment_date'] = optional($l->payment_date)->format('Y-m-d');
            $row['logged_by']    = $l->creator?->name;
        }

        return $row;
    }

    private function permissionDenied(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to perform this action.',
        ], 403);
    }

    private function validationError(\Illuminate\Validation\ValidationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors'  => $e->errors(),
        ], 422);
    }

    private function serverError(string $context, \Throwable $e): JsonResponse
    {
        Log::error("SitePaylog {$context} error: " . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());

        return response()->json([
            'success' => false,
            'message' => "Failed to fetch {$context}: " . $e->getMessage(),
        ], 500);
    }
}
