<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;
class Loan extends Model implements ApprovableModel
{
    use HasFactory,Approvable;
    public $fillable = ['id', 'staff_id', 'amount', 'date', 'deduction','payment_type_id','create_by_id','document_number'];

    /**
     * Logic executed when the approval process is completed.
     *
     * This method handles the state transitions based on your application's status values:
     * 'CREATED', 'PENDING', 'APPROVED', 'REJECTED', 'PAID', 'COMPLETED'
     *
     * @param ProcessApproval $approval The approval object
     * @return bool Whether the approval completion logic succeeded
     */
    public function onApprovalCompleted(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {

        $this->status = 'APPROVED';
        $this->updated_at = now();
        $this->save();
        return true;
    }

    public function staff(){
        return $this->belongsTo(User::class);
    }
    public function user(){
        return $this->belongsTo(User::class,'create_by_id');
    }

    public static function countUnapproved()
    {
        return count(Loan::where('status','!=','APPROVED')->where('status','!=','REJECTED')->get());
    }

    static function getTotalLoanPerDay($date){
        return Loan::select([DB::raw("SUM(amount) as total_amount")])->Where('status','APPROVED')->Where('date',$date)->get()->first()['total_amount'] ?? 0;
    }

    public static function getTotalLoanAmountFromBeginning($end_date)
    {
        $start_date = '2020-01-01';
        return Loan::select([DB::raw("SUM(amount) as total_amount")])->Where('status','APPROVED')->WhereBetween('date',[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
    }

    public static function getTotalLoanPaidAmountFromBeginning($end_date)
    {
        $start_date = '2020-01-01';
        return PayrollRecord::select([DB::raw("SUM(loanDeduction) as total_amount")])->Where('status','APPROVED')->whereDate('created_at','>=',$start_date)
            ->whereDate('created_at','<=',$end_date)->get()->first()['total_amount'] ?? 0;
    }

    public static function getTotalCashLoanPaidAmountFromBeginning($end_date)
    {
        $start_date = '2020-01-01';
        return LoanPayment::select([DB::raw("SUM(amount) as total_amount")])->whereDate('payment_date','>=',$start_date)
            ->whereDate('payment_date','<=',$end_date)->get()->first()['total_amount'] ?? 0;
    }


    public static function getLoanPaidAmount($staff_id)
    {
        return
            PayrollRecord::select([DB::raw("SUM(loanDeduction) as total_amount")])
                ->Where('status','APPROVED')->where('staff_id',$staff_id)->get()->first()['total_amount'] ?? 0;

    }
    public static function getLoanPaidAmountInNewPayroll($staff_id)
    {
        return PayrollLoanDeduction::join('payrolls','payrolls.id','=','payroll_loan_deductions.payroll_id')->Where('payrolls.status','APPROVED')->Where('payroll_loan_deductions.staff_id',$staff_id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;


    }
    public static function getLoanBalance($end_date)
    {
        return self::getTotalLoanAmountFromBeginning($end_date)- (self::getTotalLoanAmountFromBeginningNewPayroll($end_date) + self::getTotalLoanPaidAmountFromBeginning($end_date)+ self::getTotalCashLoanPaidAmountFromBeginning($end_date));

    }

    public static function getTotalLoanAmountFromBeginningNewPayroll($end_date)
    {
        $start_date = '2020-01-01';
        return PayrollLoanDeduction::select([DB::raw("SUM(amount) as total_amount")])->join('payrolls','payrolls.id','=','payroll_loan_deductions.payroll_id')->WhereBetween('payrolls.submitted_date',[$start_date,$end_date])->where('payrolls.status','APPROVED')->where('amount','!=',0)->get()->first()['total_amount'] ?? 0;
//         Loan::select([DB::raw("SUM(amount) as total_amount")])->Where('status','APPROVED')->WhereBetween('date',[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
    }

    public static function getUnpaidLoanAmount($end_date,$staff_id)
    {
        $start_date = '2020-01-01';
        return Loan::select([DB::raw("SUM(amount) as total_amount")])->Where('status','APPROVED')->Where('staff_id',$staff_id)->WhereBetween('date',[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
    }
    public static function getLastDeductionAmount($staff_id)
    {
        $start_date = '2020-01-01';
        return Loan::select([DB::raw("deduction as total_amount")])->Where('status','APPROVED')->Where('staff_id',$staff_id)->orderBy('id','desc')->get()->first()['total_amount'] ?? 0;
    }
    public static function getTotalStaffLoanBalance($end_date,$staff_id){
        $loan_paid = \App\Models\Loan::getLoanPaidAmount($staff_id);
        $loan_paid3 = \App\Models\Loan::getLoanPaidAmountInNewPayroll($staff_id);
        $loan_paid2 = \App\Models\LoanPayment::getTotalLoanPaid($staff_id);
        $loan_unpaid = \App\Models\Loan::getUnpaidLoanAmount($end_date,$staff_id);
        return ($loan_unpaid) - ($loan_paid+$loan_paid2+$loan_paid3);
    }


}
