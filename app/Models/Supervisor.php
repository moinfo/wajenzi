<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Supervisor extends Model
{
    use HasFactory;
    public $fillable = ['id', 'name', 'phone', 'details', 'employee_id', 'system_id'];

    public function grosses() {
        return $this->hasMany(Gross::class);
    }
    public function system() {
        return $this->belongsTo(System::class);
    }
    public function expenses(){
        return $this->hasMany(Expense::class);
    }

    public function collections(){
        return $this->hasMany(Collection::class);
    }

    static function getTotalSupervisorExpensesByDate($date,$supervisor_id){
        return Expense::Where('date',$date)->Where('status','APPROVED')->Where('supervisor_id',$supervisor_id)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first()['total_amount'] ?? 0;
    }
    static function getTotalSupervisorExpensesByDateAsSupervisorOnly($date){
        return Expense::select([DB::raw("SUM(amount) as total_amount")])->join('supervisors', 'supervisors.id', '=','expenses.supervisor_id')->Where('date',$date)->Where('status','APPROVED')->Where('employee_id',1)->groupBy('date')->get()->first()['total_amount'] ?? 0;
    }
    static function getSumSupervisorExpensesByDateAsSupervisorOnly($start_date, $end_date){
        return \App\Models\expense::Where('status','APPROVED')->whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;
    }
    static function getTotalSupervisorExpensesPerDay($date){
        return Expense::Where('status','APPROVED')->Where('date',$date)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first()['total_amount'] ?? 0;
    }
    static function getSumSupervisorExpensesPerDateGiven($start_date, $end_date){
        return \App\Models\Expense::select([DB::raw("SUM(amount) as total_amount")])->join('supervisors', 'supervisors.id', '=','expenses.supervisor_id')->Where('status','APPROVED')->Where('employee_id',1)->whereBetween('date', [$start_date, $end_date])->get()->first()['total_amount'] ?? 0;
    }
    static function getSumOfExpensesBySupervisorForSpecificDate($supervisor_id,$start_date, $end_date){
        return Expense::Where('status','APPROVED')->Where('supervisor_id',$supervisor_id)->whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(amount) as total_amount")])->groupBy('supervisor_id')->get()->first()['total_amount'] ?? 0;
    }


}
