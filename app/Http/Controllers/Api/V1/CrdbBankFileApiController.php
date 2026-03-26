<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PayrollNetSalary;
use App\Models\StaffBankDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrdbBankFileApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-t'));

        $payrollIds = PayrollNetSalary::getPayrollList($startDate, $endDate)
            ->where('status', 'APPROVED')
            ->pluck('id')
            ->toArray();

        $rows = StaffBankDetail::query()
            ->with(['staff', 'bank'])
            ->where('bank_id', 1)
            ->get()
            ->map(function (StaffBankDetail $detail) use ($payrollIds) {
                $amount = 0;
                foreach ($payrollIds as $payrollId) {
                    $amount += (float) PayrollNetSalary::getStaffNetPaid(
                        $detail->staff_id,
                        $payrollId
                    );
                }

                return [
                    'id' => $detail->id,
                    'staff_id' => $detail->staff_id,
                    'staff_name' => $detail->staff->name ?? null,
                    'account_number' => $detail->account_number,
                    'amount' => $amount,
                    'bank_code' => '3',
                    'branch_code' => '3',
                    'details' => 'SALARY',
                    'bank_name' => $detail->bank->name ?? 'CRDB',
                    'branch' => $detail->branch,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'payroll_ids' => $payrollIds,
                'rows' => $rows,
                'total_amount' => $rows->sum('amount'),
            ],
        ]);
    }
}
