<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LaborPaymentPhase;
use App\Models\LaborContract;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LaborPaymentApiController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Get payment statistics
            $paymentsQuery = LaborPaymentPhase::query();
            $this->applyDueDateFilter($paymentsQuery, $startDate, $endDate);

            $totalPayments = (clone $paymentsQuery)->count();
            $paidPayments = (clone $paymentsQuery)->where('status', 'paid')->count();
            $pendingPayments = (clone $paymentsQuery)->where('status', 'pending')->count();
            $duePayments = (clone $paymentsQuery)->where('status', 'due')->count();
            $approvedPayments = (clone $paymentsQuery)->where('status', 'approved')->count();

            // Get total amounts
            $totalAmount = (clone $paymentsQuery)->sum('amount');
            $paidAmount = (clone $paymentsQuery)->where('status', 'paid')->sum('amount');

            // Recent payments
            $recentPayments = LaborPaymentPhase::with(['contract.project', 'contract.artisan', 'paidByUser'])
                ->when($startDate || $endDate, fn($query) => $this->applyDueDateFilter($query, $startDate, $endDate))
                ->where('status', 'paid')
                ->orderBy('paid_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn($payment) => $this->formatPayment($payment));

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => [
                        'total_payments' => $totalPayments,
                        'paid_payments' => $paidPayments,
                        'pending_payments' => $pendingPayments,
                        'due_payments' => $duePayments,
                        'approved_payments' => $approvedPayments,
                        'total_amount' => (float) $totalAmount,
                        'paid_amount' => (float) $paidAmount,
                        'pending_amount' => (float) ($totalAmount - $paidAmount),
                    ],
                    'recent_payments' => $recentPayments,
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborPayment dashboard error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $contractId = $request->input('contract_id');
            $status = $request->input('status');
            $perPage = $request->input('per_page', 20);

            $query = LaborPaymentPhase::with(['contract.project', 'contract.artisan', 'paidByUser'])
                ->orderByRaw("FIELD(status, 'due', 'approved', 'pending', 'held', 'paid')")
                ->orderBy('created_at', 'desc');

            $this->applyDueDateFilter($query, $startDate, $endDate);

            $query
                ->orderBy('due_date', 'desc');

            if ($contractId) {
                $query->where('labor_contract_id', $contractId);
            }
            if ($status) {
                $query->where('status', $status);
            }

            $payments = $query->paginate($perPage);

            $items = collect($payments->items())->map(fn($payment) => $this->formatPayment($payment));

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $items,
                    'meta' => [
                        'current_page' => $payments->currentPage(),
                        'last_page' => $payments->lastPage(),
                        'per_page' => $payments->perPage(),
                        'total' => $payments->total(),
                    ],
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'contract_id' => $contractId,
                        'status' => $status,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborPayment index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payments: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function referenceData(Request $request): JsonResponse
    {
        try {
            $contracts = LaborContract::with(['project', 'artisan'])
                ->whereIn('status', ['active', 'completed'])
                ->orderBy('contract_number')
                ->get()
                ->map(fn($contract) => [
                    'id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'project_name' => $contract->project?->project_name,
                    'artisan_name' => $contract->artisan?->name,
                ]);

            $users = User::select('id', 'name', 'email')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'contracts' => $contracts,
                    'users' => $users,
                    'statuses' => [
                        ['value' => 'pending', 'label' => 'Pending'],
                        ['value' => 'due', 'label' => 'Due'],
                        ['value' => 'approved', 'label' => 'Approved'],
                        ['value' => 'paid', 'label' => 'Paid'],
                        ['value' => 'held', 'label' => 'Held'],
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborPayment referenceData error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reference data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'labor_contract_id' => 'required|exists:labor_contracts,id',
                'phase_number' => 'required|integer|min:1',
                'phase_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'percentage' => 'required|numeric|min:0|max:100',
                'amount' => 'required|numeric|min:0',
                'due_date' => 'required|date',
                'milestone_description' => 'nullable|string',
                'status' => 'required|in:pending,due,approved,paid,held',
            ]);

            $payment = LaborPaymentPhase::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatPayment($payment),
                'message' => 'Payment phase created successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborPayment store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $payment = LaborPaymentPhase::with(['contract.project', 'contract.artisan', 'paidByUser'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatPayment($payment),
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborPayment show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment: ' . $e->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $payment = LaborPaymentPhase::findOrFail($id);

            $validated = $request->validate([
                'phase_number' => 'sometimes|required|integer|min:1',
                'phase_name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'percentage' => 'sometimes|required|numeric|min:0|max:100',
                'amount' => 'sometimes|required|numeric|min:0',
                'due_date' => 'sometimes|required|date',
                'milestone_description' => 'nullable|string',
                'status' => 'sometimes|required|in:pending,due,approved,paid,held',
            ]);

            $payment->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatPayment($payment),
                'message' => 'Payment phase updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborPayment update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $payment = LaborPaymentPhase::findOrFail($id);
            $payment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Payment phase deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('LaborPayment destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function approve(int $id): JsonResponse
    {
        try {
            $payment = LaborPaymentPhase::findOrFail($id);
            
            if (!$payment->isDue()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only due payments can be approved',
                ], 422);
            }

            if ($payment->approve()) {
                return response()->json([
                    'success' => true,
                    'data' => $this->formatPayment($payment),
                    'message' => 'Payment approved successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve payment',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('LaborPayment approve error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function processPayment(Request $request, int $id): JsonResponse
    {
        try {
            $payment = LaborPaymentPhase::findOrFail($id);

            $validated = $request->validate([
                'payment_reference' => 'required|string|max:255',
                'notes' => 'nullable|string',
            ]);

            if (!$payment->isApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved payments can be processed',
                ], 422);
            }

            if ($payment->processPayment($validated['payment_reference'], $validated['notes'])) {
                return response()->json([
                    'success' => true,
                    'data' => $this->formatPayment($payment),
                    'message' => 'Payment processed successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('LaborPayment processPayment error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function formatPayment($payment): array
    {
        return [
            'id' => $payment->id,
            'phase_number' => $payment->phase_number,
            'phase_name' => $payment->phase_name,
            'description' => $payment->description,
            'percentage' => (float) $payment->percentage,
            'amount' => (float) $payment->amount,
            'due_date' => $payment->due_date?->format('Y-m-d'),
            'milestone_description' => $payment->milestone_description,
            'status' => $payment->status,
            'status_badge_class' => $payment->status_badge_class,
            'paid_at' => $payment->paid_at?->format('Y-m-d H:i:s'),
            'paid_by' => $payment->paidByUser?->name,
            'payment_reference' => $payment->payment_reference,
            'notes' => $payment->notes,
            'contract' => [
                'id' => $payment->contract?->id,
                'contract_number' => $payment->contract?->contract_number,
                'project_name' => $payment->contract?->project?->project_name,
                'artisan_name' => $payment->contract?->artisan?->name,
            ],
            'can_approve' => $payment->canBeApproved(),
            'can_pay' => $payment->canBePaid(),
        ];
    }

    private function applyDueDateFilter($query, ?string $startDate, ?string $endDate): void
    {
        if ($startDate && $endDate) {
            $query->whereBetween('due_date', [$startDate, $endDate]);
            return;
        }

        if ($startDate) {
            $query->whereDate('due_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('due_date', '<=', $endDate);
        }
    }
}
