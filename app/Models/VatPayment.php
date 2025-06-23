<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;
class VatPayment extends Model implements ApprovableModel
{
    use HasFactory,Approvable;
    public $fillable = ['id', 'bank_id', 'amount', 'date', 'description', 'file', 'status', 'create_by_id','document_number'];

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

    public function getAll($start_date,$end_date){
        $vat_payments =  DB::table('vat_payments')
            ->join('banks', 'banks.id', '=', 'vat_payments.bank_id','LEFT')
            ->select('vat_payments.*','banks.name as bank_name')
            ->whereBetween('date', [$start_date,$end_date])
            ->Where('status','APPROVED')
            ->orderBy('date','desc')
            ->get();
        return $vat_payments;
    }

    public function bank(){
        return $this->belongsTo(Bank::class, 'bank_id');
    }
    public function user(){
        return $this->belongsTo(User::class, 'create_by_id');
    }

    public static function getTotalPayments($end_date,$start_date=null){
        $start_date = $start_date ?? '2010-01-01';
        return DB::table('vat_payments')
            ->whereBetween('date', [$start_date,$end_date])
            ->Where('status','APPROVED')
            ->sum('vat_payments.amount');

    }
    public static function getTotalPaymentOfLastMonth($start_date,$end_date){
        $vat_payments =  DB::table('vat_payments')
            ->whereBetween('date', [$start_date,$end_date])
            ->Where('status','APPROVED')
            ->sum('vat_payments.amount');
        return $vat_payments;
    }

}
