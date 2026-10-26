<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelProcessApproval\Models\ProcessApprovalFlowStep;

/**
 * Approval of the document types that are approved on the web only
 * (payroll, project BOQ, imprest, petty cash, advance salary, loans,
 * statutory payments, VAT payments, sales, expenses, project schedules).
 *
 * The details shown come from the same web controller method that builds the
 * web approval page, and approve / reject / submit go through the same
 * RingleSoft trait the web uses, so the step-role rules are the web's.
 *
 *   GET  document-approvals/pending
 *   GET  document-approvals/{type}/{id}
 *   POST document-approvals/{type}/{id}/submit|approve|reject
 */
class DocumentApprovalApiController extends Controller
{
    /** short model name => [label, web controller, method that builds the approval page] */
    private const TYPES = [
        'Sale' => ['Sale', \App\Http\Controllers\SaleController::class, 'sale'],
        'VatPayment' => ['VAT Payment', \App\Http\Controllers\VatPaymentController::class, 'vat_payment'],
        'Expense' => ['Expense', \App\Http\Controllers\ExpenseController::class, 'expense'],
        'StatutoryPayment' => ['Statutory Payment', \App\Http\Controllers\StatutoryPaymentController::class, 'statutory_payment'],
        'Payroll' => ['Payroll', \App\Http\Controllers\PayrollController::class, 'payroll_view'],
        'AdvanceSalary' => ['Advance Salary', \App\Http\Controllers\SettingsController::class, 'advance_salary'],
        'Loan' => ['Staff Loan', \App\Http\Controllers\SettingsController::class, 'staff_loan'],
        'PettyCashRefillRequest' => ['Petty Cash Refill Request', \App\Http\Controllers\SettingsController::class, 'petty_cash_refill_request'],
        'ImprestRequest' => ['Imprest Request', \App\Http\Controllers\SettingsController::class, 'imprest_request'],
        'ProjectBoq' => ['Project BOQ', \App\Http\Controllers\ProjectBoqController::class, 'boq'],
        'ProjectSchedule' => ['Project Schedule', null, null],
    ];

    public function pending(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = [];

        foreach (self::TYPES as $short => [$label]) {
            $class = "App\\Models\\{$short}";
            if (!class_exists($class)) {
                continue;
            }

            $statuses = DB::table('process_approval_statuses')
                ->where('approvable_type', $class)
                ->whereIn('status', ['Submitted', 'Pending'])
                ->orderByDesc('updated_at')
                ->limit(100)
                ->get(['approvable_id', 'creator_id', 'updated_at']);
            if ($statuses->isEmpty()) {
                continue;
            }

            $models = $class::whereIn('id', $statuses->pluck('approvable_id'))->get()->keyBy('id');
            $creators = User::whereIn('id', $statuses->pluck('creator_id')->filter())->pluck('name', 'id');

            foreach ($statuses as $status) {
                $model = $models->get($status->approvable_id);
                if (!$model || !method_exists($model, 'canBeApprovedBy') || !$model->canBeApprovedBy($user)) {
                    continue;
                }

                $items[] = [
                    'id' => (int) $model->id,
                    'type' => $short,
                    'label' => $label,
                    'title' => $this->title($model, $label),
                    'submitted_by' => $creators[$status->creator_id] ?? null,
                    'submitted_at' => $status->updated_at ? date('c', strtotime($status->updated_at)) : null,
                ];
            }
        }

        usort($items, fn ($a, $b) => strcmp((string) $b['submitted_at'], (string) $a['submitted_at']));

        return response()->json(['success' => true, 'data' => $items, 'meta' => ['total' => count($items)]]);
    }

    public function show(Request $request, string $type, int $id): JsonResponse
    {
        [$model, $label] = $this->resolve($type, $id);
        if (!$model) {
            return response()->json(['success' => false, 'message' => 'Item not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (int) $model->id,
                'type' => $type,
                'label' => $label,
                'title' => $this->title($model, $label),
                'details' => $this->details($type, $model, $request->user()),
                'lines' => $this->lines($type, $model),
                'approval_flow' => $this->flow($model, $request->user()),
            ],
        ]);
    }

    public function submit(Request $request, string $type, int $id): JsonResponse
    {
        [$model] = $this->resolve($type, $id);
        if (!$model) {
            return response()->json(['success' => false, 'message' => 'Item not found.'], 404);
        }
        if (!$model->approvalStatus) {
            return response()->json(['success' => false, 'message' => 'This document has no approval workflow attached.'], 422);
        }
        if ($model->isSubmitted()) {
            return response()->json(['success' => false, 'message' => 'This document has already been submitted for approval.'], 422);
        }
        if (!$model->canBeSubmittedBy($request->user())) {
            return response()->json(['success' => false, 'message' => 'Only the creator can submit this document for approval.'], 403);
        }

        $model->submit($request->user());

        return response()->json(['success' => true, 'message' => 'Submitted for approval.']);
    }

    public function approve(Request $request, string $type, int $id): JsonResponse
    {
        return $this->decide($request, $type, $id, true);
    }

    public function reject(Request $request, string $type, int $id): JsonResponse
    {
        return $this->decide($request, $type, $id, false);
    }

    // ── helpers ────────────────────────────────────────────────────────────

    private function decide(Request $request, string $type, int $id, bool $approve): JsonResponse
    {
        [$model] = $this->resolve($type, $id);
        if (!$model) {
            return response()->json(['success' => false, 'message' => 'Item not found.'], 404);
        }

        $verb = $approve ? 'approve' : 'reject';
        if (!$model->canBeApprovedBy($request->user())) {
            return response()->json([
                'success' => false,
                'message' => "You are not allowed to {$verb} this document at the current step.",
            ], 403);
        }

        $comment = $request->input('comment') ?? $request->input('reason');
        $ok = $approve
            ? $model->approve($comment, $request->user())
            : $model->reject($comment, $request->user());

        if ($ok === false) {
            return response()->json([
                'success' => false,
                'message' => "You are not allowed to {$verb} this document at the current step.",
            ], 403);
        }

        return response()->json(['success' => true, 'message' => $approve ? 'Approved.' : 'Rejected.']);
    }

    /** @return array{0: ?\Illuminate\Database\Eloquent\Model, 1: string} */
    private function resolve(string $type, int $id): array
    {
        if (!isset(self::TYPES[$type])) {
            return [null, ''];
        }
        $class = "App\\Models\\{$type}";

        return [class_exists($class) ? $class::find($id) : null, self::TYPES[$type][0]];
    }

    /** Label => value pairs, as the web approval page shows them. */
    private function details(string $type, $model, $user): array
    {
        [, $controller, $method] = self::TYPES[$type];

        if ($controller) {
            try {
                \Illuminate\Support\Facades\Auth::guard('web')->setUser($user);
                $view = app($controller)->{$method}($model->id, 0);
                if ($view instanceof \Illuminate\View\View) {
                    $details = $view->getData()['details'] ?? null;
                    if (is_array($details) && $details) {
                        return $this->cleanDetails($details);
                    }
                }
            } catch (\Throwable $e) {
                // fall through to the generic fields
            }
        }

        return $this->genericDetails($model);
    }

    private function cleanDetails(array $details): array
    {
        $out = [];
        foreach ($details as $label => $value) {
            if (is_array($value) || is_object($value)) {
                continue;
            }
            $out[] = ['label' => (string) $label, 'value' => $value === null || $value === '' ? '-' : trim((string) $value)];
        }

        return $out;
    }

    private function genericDetails($model): array
    {
        $out = [];
        foreach ($model->getAttributes() as $key => $value) {
            if (in_array($key, ['id', 'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token'], true) || is_array($value)) {
                continue;
            }
            $label = ucwords(str_replace('_', ' ', preg_replace('/_id$/', '', $key)));
            if (str_ends_with($key, '_id')) {
                $relation = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', substr($key, 0, -3)))));
                try {
                    $related = method_exists($model, $relation) ? $model->{$relation} : null;
                    $value = $related->name ?? $related->title ?? $value;
                } catch (\Throwable $e) {
                }
            }
            $out[] = ['label' => $label, 'value' => $value === null || $value === '' ? '-' : (string) $value];
        }

        return $out;
    }

    /** Extra rows for documents whose web page is mainly a table (payroll). */
    private function lines(string $type, $model): array
    {
        if ($type !== 'Payroll') {
            return [];
        }

        return DB::table('payroll_net_salaries')
            ->join('users', 'users.id', '=', 'payroll_net_salaries.staff_id')
            ->where('payroll_net_salaries.payroll_id', $model->id)
            ->orderBy('users.name')
            ->get(['users.name', 'payroll_net_salaries.amount'])
            ->map(fn ($r) => ['label' => $r->name, 'value' => number_format((float) $r->amount, 2)])
            ->all();
    }

    private function flow($model, $user): array
    {
        $statusRow = $model->approvalStatus;
        $steps = collect($statusRow?->steps ?? [])->map(function ($step) use ($model) {
            $flowStep = ProcessApprovalFlowStep::with('role')->find($step['id'] ?? null);
            $approval = null;
            if (!empty($step['process_approval_id'])) {
                $approval = $model->approvals()->with('user')->find($step['process_approval_id']);
            }

            return [
                'step_id' => $step['id'] ?? null,
                'role_name' => $flowStep?->role?->name ?? ('Step ' . ($step['id'] ?? '')),
                'action' => $step['process_approval_action'] ?? 'Pending',
                'approver_name' => $approval?->user?->name ?? $approval?->approver_name ?? null,
                'date' => $approval?->created_at?->format('d F, Y') ?? null,
                'comment' => $approval?->comment ?? null,
            ];
        })->values();

        $completed = $model->isApprovalCompleted();

        return [
            'status_label' => $completed ? 'Approval completed!' : ($model->isSubmitted() ? 'In Progress' : 'Not Submitted'),
            'next_role_name' => $model->nextApprovalStep()?->role?->name,
            'is_submitted' => $model->isSubmitted(),
            'is_completed' => $completed,
            'can_be_submitted' => (bool) $model->canBeSubmittedBy($user),
            'can_be_approved' => (bool) $model->canBeApprovedBy($user),
            'steps' => $steps,
        ];
    }

    private function title($model, string $label): string
    {
        foreach (['document_number', 'name', 'title', 'reference', 'description'] as $field) {
            $value = $model->{$field} ?? null;
            $value = is_scalar($value) ? trim(preg_replace('/\s+/u', ' ', (string) $value)) : '';
            if ($value !== '') {
                return $label . ' - ' . mb_strimwidth($value, 0, 60, '…');
            }
        }

        return $label . ' #' . $model->id;
    }
}
