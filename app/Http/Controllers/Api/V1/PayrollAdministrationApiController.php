<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\PayrollNetSalary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollAdministrationApiController extends Controller
{
    public function index(): JsonResponse
    {
        $payrolls = Payroll::with('user:id,name')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payrolls->map(fn (Payroll $payroll) => $this->transformPayroll($payroll))->values(),
            'meta' => [
                'total' => $payrolls->count(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'statuses' => [
                    ['name' => 'CREATED'],
                    ['name' => 'SUBMITTED'],
                    ['name' => 'APPROVED'],
                    ['name' => 'REJECTED'],
                    ['name' => 'CLOSED'],
                    ['name' => 'PAID'],
                ],
                'months' => collect(range(1, 12))->map(fn (int $month) => [
                    'id' => $month,
                    'name' => date('F', strtotime(sprintf('2000-%02d-01', $month))),
                ])->values(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $payroll = Payroll::with('user:id,name')->find($id);

        if (!$payroll) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformPayroll($payroll),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_number' => 'required|string|max:255|unique:payrolls,document_number',
            'payroll_number' => 'required|string|max:255|unique:payrolls,payroll_number',
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'status' => 'required|in:CREATED,SUBMITTED,APPROVED,REJECTED,CLOSED,PAID',
            'submitted_date' => 'required|date',
        ]);

        $exists = Payroll::where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll for the selected month and year already exists',
            ], 422);
        }

        $payroll = new Payroll();
        $payroll->document_number = $validated['document_number'];
        $payroll->payroll_number = $validated['payroll_number'];
        $payroll->year = $validated['year'];
        $payroll->month = $validated['month'];
        $payroll->status = $validated['status'];
        $payroll->submitted_date = $validated['submitted_date'];
        $payroll->created_by_id = $request->user()?->id ?? 1;
        $payroll->save();
        $payroll->load('user:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Payroll created successfully',
            'data' => $this->transformPayroll($payroll),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $payroll = Payroll::with('user:id,name')->find($id);

        if (!$payroll) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll not found',
            ], 404);
        }

        $validated = $request->validate([
            'document_number' => 'required|string|max:255|unique:payrolls,document_number,' . $id,
            'payroll_number' => 'required|string|max:255|unique:payrolls,payroll_number,' . $id,
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'status' => 'required|in:CREATED,SUBMITTED,APPROVED,REJECTED,CLOSED,PAID',
            'submitted_date' => 'required|date',
        ]);

        $exists = Payroll::where('year', $validated['year'])
            ->where('month', $validated['month'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll for the selected month and year already exists',
            ], 422);
        }

        $payroll->document_number = $validated['document_number'];
        $payroll->payroll_number = $validated['payroll_number'];
        $payroll->year = $validated['year'];
        $payroll->month = $validated['month'];
        $payroll->status = $validated['status'];
        $payroll->submitted_date = $validated['submitted_date'];
        $payroll->save();
        $payroll->load('user:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Payroll updated successfully',
            'data' => $this->transformPayroll($payroll),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $payroll = Payroll::find($id);

        if (!$payroll) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll not found',
            ], 404);
        }

        if ($payroll->status === 'APPROVED') {
            return response()->json([
                'success' => false,
                'message' => 'Approved payroll cannot be deleted',
            ], 422);
        }

        $payroll->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payroll deleted successfully',
        ]);
    }

    private function transformPayroll(Payroll $payroll): array
    {
        return [
            'id' => $payroll->id,
            'document_number' => $payroll->document_number,
            'payroll_number' => $payroll->payroll_number,
            'year' => $payroll->year,
            'month' => $payroll->month,
            'month_name' => date('F', strtotime(sprintf('2000-%02d-01', $payroll->month))),
            'status' => $payroll->approvalStatus?->status ?? $payroll->status,
            'submitted_date' => $payroll->submitted_date,
            'created_by_id' => $payroll->created_by_id,
            'created_by_name' => $payroll->user?->name,
            'payroll_amount' => (float) PayrollNetSalary::getTotalNetSalaryByPayroll($payroll->id),
            'created_at' => $payroll->created_at?->toISOString(),
            'updated_at' => $payroll->updated_at?->toISOString(),
        ];
    }
}
