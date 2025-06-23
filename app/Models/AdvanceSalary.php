<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;
class AdvanceSalary extends Model implements ApprovableModel
{
    use HasFactory,Approvable;
    public $fillable = ['id', 'staff_id', 'amount', 'date', 'description', 'status', 'create_by_id','document_number'];


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

    public static function getTotalAdvanceSalaryPerDay($date)
    {
        return AdvanceSalary::select([DB::raw("SUM(amount) as total_amount")])->Where('status','APPROVED')->Where('date',$date)->groupBy('date')->get()->first()['total_amount'] ?? 0;
    }


    public function user(){
        return $this->belongsTo(User::class, 'create_by_id');
    }

    public static function countUnapproved()
    {
        return count(AdvanceSalary::where('status','!=','APPROVED')->where('status','!=','REJECTED')->get());
    }


    public static function getTotalAdvanceSalaryAmountFromBeginning($end_date)
    {
        $start_date = '2020-01-01';
        return AdvanceSalary::select([DB::raw("SUM(amount) as total_amount")])->Where('status','APPROVED')->WhereBetween('date',[$start_date,$end_date])->get()->first()['total_amount'] ?? 0;
    }

    public function staff(){
        return $this->belongsTo(User::class);
    }





}
