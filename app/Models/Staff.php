<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Staff extends User
{


    public static function getStaffAllowanceSubscribed($staff_id, $allowance_id)
    {
        return AllowanceSubscription::where('staff_id', $staff_id)->where('allowance_id', $allowance_id)->get()->first()['amount'];
    }

    public function allowanceSubscriptions()
    {
        return $this->hasMany(AllowanceSubscription::class, 'staff_id');
    }

    public static function staffSummary()
    {
        $summary = [
            'staff_board',
            'payroll_types',
            'staff_gender' => ['female', 'male'],
        ];

        $q_board = DB::connection()->select("SELECT COUNT(id) as members, department_id as department_id,
                        (SELECT departments.name from departments WHERE departments.id = u.department_id)  as department_name
                        FROM users u WHERE UPPER(u.status)='ACTIVE' AND u.type ='STAFF' AND department_id IS NOT NULL
                        GROUP BY u.department_id ORDER BY department_name");
        $data_board = $q_board;
        $data_board = json_decode(json_encode($data_board), true);;

        $staff_board = is_array($data_board) ? $data_board : [];

        $q_payroll = DB::connection()->select("SELECT COUNT(id) as members, payroll_type_id as payroll_type_id,
                        (SELECT payroll_types.name from payroll_types WHERE payroll_types.id = u.payroll_type_id)  as payroll_name
                        FROM users u WHERE UPPER(u.status)='ACTIVE' AND u.type ='STAFF' AND payroll_type_id IS NOT NULL
                        GROUP BY u.payroll_type_id ORDER BY payroll_name");
        $data_payroll = $q_payroll;
        $data_payroll = json_decode(json_encode($data_payroll), true);;

        $staff_payroll = is_array($data_payroll) ? $data_payroll : [];

        $staff = DB::connection()->select("SELECT count(id) as staff_count, UPPER(gender) as staff_gender  FROM users
                                        WHERE UPPER (status) = 'ACTIVE' AND type='STAFF' AND gender IS NOT NULL GROUP BY gender ORDER BY gender asc");

        $staff = json_decode(json_encode($staff), true);


        $summary['staff_board'] = $staff_board;
        $summary['payroll_types'] = $staff_payroll;
        $summary['staff_gender']['male'] = is_array($staff) && count($staff) > 0 ? $staff[0]['staff_count'] : 0;
        $summary['staff_gender']['female'] = is_array($staff) && count($staff) > 1 ? $staff[1]['staff_count'] : 0;

        return $summary;
    }

    public static function staffBoard()
    {
        $date = date('Y-m-d');
        $staff_location = ['On Leave' => 1, 'Out of Office' => 2, 'In Office Premises' => 8, 'On the field' => 0];
        $staff_with_updates = [
            '1' => [
                'name' => 'Semkae Kilonzo',
                'location' => 'In Office Premises',
                'date' => '',
                'planning_status' => '',
                'plan' => [
                    [
                        'activity' => 'Donor communications and report reviews',
                        'start_time_planned' => '',
                        'end_time_planned' => '',
                        'activity_status' => '',
                        'start_time_actual' => '',
                        'end_time_actual' => ''
                    ], [
                        'activity' => 'Donor communications and report reviews',
                        'start_time_planned' => '',
                        'end_time_planned' => '',
                        'activity_status' => '',
                        'start_time_actual' => '',
                        'end_time_actual' => ''
                    ], [
                        'activity' => 'Donor communications and report reviews',
                        'start_time_planned' => '',
                        'end_time_planned' => '',
                        'activity_status' => '',
                        'start_time_actual' => '',
                        'end_time_actual' => ''
                    ]
                ]
            ]
        ];
        return [
            'staff_with_updates' => $staff_with_updates,
            'staff_location' => $staff_location
        ];
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'device_user_id', 'id');
    }

    public static function getStaffAllowances($staff_id, $month)
    {
        $allowance_subscriptions = AllowanceSubscription::Where('staff_id', $staff_id)->get();
        $total = 0;
        foreach ($allowance_subscriptions as $index => $allowance_subscription) {
            $allowance_type = $allowance_subscription->allowance->allowance_type;
            $allowance_amount_first = $allowance_subscription->amount;
            $allowance_amount = \App\Models\Allowance::getAllowanceAmountPerType($allowance_type, $allowance_amount_first, $month);
            $total += $allowance_amount;
        }
        return $total;
    }


    public static function getAllStaffSalaryPaid($payroll_id, $payroll_type_id)
    {
        return PayrollSalary::join('users', 'users.id', '=', 'payroll_salaries.staff_id')->where('users.payroll_type_id', $payroll_type_id)->Where('payroll_salaries.payroll_id', $payroll_id)->get();
    }

    public static function getAllStaffSalaryWithPayrollType($payroll_type_id)
    {
        return User::where('status', 'ACTIVE')->where('type', 'STAFF')->where('payroll_type_id', $payroll_type_id)->get();
    }

    public static function getStaffAdvanceSalaryPaid($staff_id, $payroll_id)
    {
        return PayrollAdvanceSalary::Where('staff_id', $staff_id)->Where('payroll_id', $payroll_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;

    }

    public static function getStaffGrossPayPaid($staff_id, $payroll_id)
    {
        return PayrollGrossPay::Where('staff_id', $staff_id)->Where('payroll_id', $payroll_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;

    }

    public static function getStaffLoanBalancePaid($staff_id, $payroll_id)
    {
        return PayrollLoanBalance::Where('staff_id', $staff_id)->Where('payroll_id', $payroll_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;

    }

    public static function getStaffLoanDeductionPaid($staff_id, $payroll_id)
    {
        return PayrollLoanDeduction::Where('staff_id', $staff_id)->Where('payroll_id', $payroll_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;

    }

    public static function getStaffNetPaid($staff_id, $payroll_id)
    {
        return PayrollNetSalary::Where('staff_id', $staff_id)->Where('payroll_id', $payroll_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;

    }

    public static function getStaffAllowancePaid($staff_id, $payroll_id)
    {
        return PayrollAllowance::Where('staff_id', $staff_id)->Where('payroll_id', $payroll_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }

    public static function getStaffAdjustmentPaid($staff_id, $payroll_id)
    {
        return PayrollAdjustment::Where('staff_id', $staff_id)->Where('payroll_id', $payroll_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }

    public static function getAllStaffAllowancePaid($payroll_id, $start_date, $end_date)
    {
        return PayrollAllowance::join('payrolls', 'payrolls.id', '=', 'payroll_allowances.payroll_id')->Where('payrolls.status', 'APPROVED')->WhereBetween('payrolls.submitted_date', [$start_date, $end_date])->Where('payroll_id', $payroll_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }

    public static function getAllStaffAllowancePaidAllTime($start_date, $end_date)
    {
        return PayrollAllowance::join('payrolls', 'payrolls.id', '=', 'payroll_allowances.payroll_id')->Where('payrolls.status', 'APPROVED')->WhereBetween('payrolls.submitted_date', [$start_date, $end_date])->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }


    public static function getAllStaffNetPaid($payroll_id, $start_date, $end_date)
    {
        return PayrollNetSalary::join('payrolls', 'payrolls.id', '=', 'payroll_net_salaries.payroll_id')->Where('payrolls.status', 'APPROVED')->Where('payroll_id', $payroll_id)->WhereBetween('payrolls.submitted_date', [$start_date, $end_date])->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }

    public static function getAllStaffNetPaidAllTime($start_date, $end_date)
    {
        return PayrollNetSalary::join('payrolls', 'payrolls.id', '=', 'payroll_net_salaries.payroll_id')->Where('payrolls.status', 'APPROVED')->WhereBetween('payrolls.submitted_date', [$start_date, $end_date])->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }

    public static function getStaffDeductionPaid($staff_id, $payroll_id, $deduction_id, $type)
    {
        return PayrollDeduction::Where('staff_id', $staff_id)->Where('deduction_id', $deduction_id)->Where('payroll_id', $payroll_id)->select([DB::raw("SUM($type) as total_amount")])->get()->first()['total_amount'] ?? 0;

    }

    public static function getStaffMembershipNumber($staff_id, $deduction_id)
    {
        return DeductionSubscription::Where('staff_id', $staff_id)->Where('deduction_id', $deduction_id)->select([DB::raw("membership_number as membership_number")])->get()->first()['membership_number'] ?? 0;

    }

    public static function getStaffLoanPaid($staff_id, $payroll_id)
    {
        return PayrollLoan::Where('staff_id', $staff_id)->Where('payroll_id', $payroll_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }

    public static function getStaffTaxablePaid($staff_id, $payroll_id)
    {
        return PayrollTaxable::Where('staff_id', $staff_id)->Where('payroll_id', $payroll_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }

    public static function getAllStaffNet(bool $date)
    {
    }


    public function allowance_subscriptions()
    {
        return $this->hasMany(AllowanceSubscription::class, 'staff_id', 'id');
    }

    public function deductionSubscriptions()
    {
        return $this->hasMany(DeductionSubscription::class, 'staff_id', 'id');
    }

    public function staff_salaries()
    {
        return $this->hasMany(StaffSalary::class, 'staff_id', 'id')
            ->orderBy('created_at', 'desc');
    }

    public static function getList()
    {
        return User::with('department', 'position')->get();
    }

    /**
     * @return mixed
     * this return all user expect MAILI MOJA SYSTEM
     */
    public static function onlyStaffs()
    {
        return User::where('status', 'ACTIVE')->where('type', 'STAFF')->get();
    }

    public static function onlyStaffsWithBreakfastOrLunch()
    {
        return User::where('status', 'ACTIVE')
            ->where('type', 'STAFF')
            ->whereHas('allowanceSubscriptions', function ($query) {
                $query->whereHas('allowance', function ($query) {
                    $query->whereIn('name', ['breakfast', 'lunch']); // Filter for breakfast or lunch allowances
                });
            })
            ->get();
    }

    public static function getStaffBankDetails($staff_id)
    {
        return StaffBankDetail::where('staff_id', $staff_id)->get()->first();
    }

    public static function onlyMailimojaStaffs()
    {
        return User::where('system_id', '!=', 7)->where('system_id', '!=', 2)->where('status', 'ACTIVE')->get();
    }

    public static function getStaffSalary($staff_id)
    {
        return StaffSalary::Where('staff_id', $staff_id)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('staff_id')->get()->first()['total_amount'] ?? 0;
    }

    public static function getStaffSalaryId($staff_id)
    {
        return StaffSalary::Where('staff_id', $staff_id)->get()->first()['id'] ?? 0;
    }

    public static function getStaffAdvanceSalary($staff_id, $start_date, $end_date)
    {
        return AdvanceSalary::Where('status', 'APPROVED')->Where('staff_id', $staff_id)->WhereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }

    public static function getStaffAdjustment($staff_id, $start_date, $end_date)
    {
        return Adjustment::Where('staff_id', $staff_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }

    public static function getStaffAllowance($staff_id, $month)
    {
        $allowance_subscriptions = AllowanceSubscription::Where('staff_id', $staff_id)->get();
        $total = 0;
        foreach ($allowance_subscriptions as $index => $allowance_subscription) {
            $allowance_type = $allowance_subscription->allowance->allowance_type;
            $allowance_amount_first = $allowance_subscription->amount;
            $allowance_amount = \App\Models\Allowance::getAllowanceAmountPerType($allowance_type, $allowance_amount_first, $month);
            $total += $allowance_amount;
        }
        return $total;
    }

    public static function getAllStaffAllowance($month)
    {
        $staffs = Staff::where('users.status', 'ACTIVE')->with('allowance_subscriptions.allowance')->get();
        $total = 0;
        foreach ($staffs as $index => $staffs) {
            foreach ($staffs->allowance_subscriptions as $allowance_subscription) {
                $allowance_type = $allowance_subscription->allowance->allowance_type;
                $allowance_amount_first = $allowance_subscription->amount;
                $allowance_amount = \App\Models\Allowance::getAllowanceAmountPerType($allowance_type, $allowance_amount_first, $month);
                $total += $allowance_amount;
            }
        }
        return $total;
    }

    public static function getStaffGrossPay($staff_id, $month)
    {
        return User::getStaffSalary($staff_id);
//        return User::getStaffAllowance($staff_id,$month) + (new Staff)->getStaffSalary($staff_id);
//        return (new Staff)->getStaffSalary($staff_id);
    }

    public static function getStaffDeduction($staff_id, $deduction_type)
    {
        return DeductionSubscription::select([DB::raw("deduction_settings.*, deductions.nature")])->join('deduction_settings', 'deduction_settings.deduction_id', '=', 'deduction_subscriptions.deduction_id')->join('deductions', 'deductions.id', '=', 'deduction_settings.deduction_id')->Where('deductions.abbreviation', $deduction_type)->Where('deduction_subscriptions.staff_id', $staff_id)->get()->first();
    }

    public static function getStaffLoan($staff_id)
    {
        $loan = Loan::Where('staff_id', $staff_id)->select([DB::raw("SUM(amount) as total_amount")])->orderBy('id', 'desc')->get()->first()['total_amount'] ?? 0;
        $paid = PayrollRecord::Where('staff_id', $staff_id)->select([DB::raw("SUM(loanDeduction) as total_amount")])->get()->first()['total_amount'] ?? 0;
        $paid_new_payroll = PayrollLoanDeduction::join('payrolls', 'payrolls.id', '=', 'payroll_loan_deductions.payroll_id')->Where('payrolls.status', 'APPROVED')->Where('payroll_loan_deductions.staff_id', $staff_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
        $paid_manually = LoanPayment::getTotalLoanPaid($staff_id);
        return $loan - $paid - $paid_new_payroll - $paid_manually;
    }

    public static function getStaffLoanDeductionForCurrentLoan($staff_id)
    {
        return Loan::Where('staff_id', $staff_id)->select([DB::raw("deduction as total_amount")])->orderBy('id', 'desc')->get()->first()['total_amount'] ?? 0;
    }



    public static function isStaffHasLoan($staff_id)
    {
        $total_loan = User::getStaffLoanAllTheTime($staff_id);
        $total_deduction = User::getStaffLoanDeductionAllTheTime($staff_id);
        $balance = $total_loan - $total_deduction;
        if ($balance != 0) {
            return true;
        } else {
            return false;
        }
    }

    public static function getStaffSalariesInDateRange($staff_id, $start_date = null, $end_date = null)
    {
        $query = StaffSalary::where('staff_id', $staff_id);

        if ($start_date && $end_date) {
            $end_date = date('Y-m-d 23:59:59', strtotime($end_date));
            $query->whereBetween('created_at', [$start_date, $end_date]);
        }

        return $query->sum('amount');
    }

    public static function getAllStaffSalariesInDateRange($start_date = null, $end_date = null)
    {
        $query = StaffSalary::query();

        if ($start_date && $end_date) {
            $end_date = date('Y-m-d 23:59:59', strtotime($end_date));
            $query->whereBetween('created_at', [$start_date, $end_date]);
        }

        return $query->sum('amount');
    }
}
