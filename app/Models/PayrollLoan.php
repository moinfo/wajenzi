<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollLoan extends Model
{
    use HasFactory;
    public $fillable = ['staff_id','payroll_id','amount'];

    public function staff(){
        return $this->belongsTo(User::class);
    }
    public function payroll(){
        return $this->belongsTo(Payroll::class);
    }
}
