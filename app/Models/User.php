<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    public $fillable = [
        'name',
        'email',
        'gender',
        'password',
        'employee_number',
        'employee_type',
        'employment_type',
        'recruitment_date',
        'employment_date',
        'address',
        'national_id',
        'tin',
        'dob',
        'status',
        'marital_status',
        'designation',
        'profile',
        'file',
        'department_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getSignature()
    {
        return $this->file; // Return the path to user's signature
    }

    // Existing relationships
    /**
     * @var mixed
     */
    private $role;

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    // Project Management relationships
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_team_members')
            ->withPivot('role', 'assigned_date', 'end_date', 'status')
            ->withTimestamps();
    }

    public function siteVisits(): HasMany
    {
        return $this->hasMany(ProjectSiteVisit::class, 'inspector_id');
    }

    public function projectDesigns(): HasMany
    {
        return $this->hasMany(ProjectDesign::class, 'designer_id');
    }

    public function materialRequests(): HasMany
    {
        return $this->hasMany(ProjectMaterialRequest::class, 'requester_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class, 'created_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ProjectActivityLog::class);
    }

    // Permission handling methods
    public function hasProjectPermission($permission, $project_id = null)
    {
        return UsersPermission::isUserAllowed(
            $this->id,
            'project',
            $permission,
            $project_id
        );
    }

    public function assignProjectRole($project_id, $role)
    {
        return $this->projects()->attach($project_id, [
            'role' => $role,
            'assigned_date' => now(),
            'status' => 'active'
        ]);
    }

    // Helper methods for project access
    public function getActiveProjects()
    {
        return $this->projects()
            ->wherePivot('status', 'active')
            ->wherePivot('end_date', null)
            ->get();
    }

    public function canManageProject($project_id)
    {
        return $this->hasProjectPermission('manage_project', $project_id) ||
            $this->role->name === 'project_manager';
    }

    public static function  getUserCounts()
    {
        $counts = DB::table('users')
            ->select(
                DB::raw('COUNT(CASE WHEN gender = "FEMALE" THEN 1 END) AS total_female'),
                DB::raw('COUNT(CASE WHEN gender = "MALE" THEN 1 END) AS total_male'),
                DB::raw('COUNT(*) AS total')
            )->where('type', 'STAFF')
            ->first();

        return $counts;
    }

    public static function getDepartmentMemberCounts()
    {
        $counts = DB::table('users')
            ->select('department_id','departments.name AS name', DB::raw('COUNT(*) AS total_members'))
            ->join('departments', 'departments.id', '=', 'users.department_id')
            ->groupBy('department_id')
            ->get();

        return $counts;
    }

    public static function getStaffSalaryPaid($staff_id, $payroll_id)
    {
        return PayrollSalary::Where('staff_id', $staff_id)->Where('payroll_id', $payroll_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }


    public static function getUserName($user_id)
    {
        return User::where('id','=', $user_id)->get()->first()->name ?? null;
    }
    public static function getUserSignature($user_id)
    {
        return User::where('id','=', $user_id)->get()->first()->file ?? null;
    }
    public static function getStaffLoanDeductionAllTheTime($staff_id)
    {
        return PayrollRecord::Where('staff_id', $staff_id)->select([DB::raw("SUM(loanDeduction) as total_amount")])->groupBy('staff_id')->get()->first()['total_amount'] ?? 0;
    }

    public static function getStaffLoanAllTheTime($staff_id)
    {
        return Loan::Where('staff_id', $staff_id)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('staff_id')->get()->first()['total_amount'] ?? 0;
    }

    public function allowance_subscriptions()
    {
        return $this->hasMany(AllowanceSubscription::class, 'staff_id', 'id');
    }

    public function allowanceSubscriptions()
    {
        return $this->hasMany(AllowanceSubscription::class, 'staff_id');
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

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function getRemainingLeaveBalance($leaveTypeId)
    {
        $leaveType = LeaveType::findOrFail($leaveTypeId);
        $usedLeaves = $this->leaveRequests()
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', 'approved')
            ->whereYear('start_date', date('Y'))
            ->sum('total_days');

        return $leaveType->days_allowed - $usedLeaves;
    }


}
