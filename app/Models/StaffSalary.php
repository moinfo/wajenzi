<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\This;

class StaffSalary extends Model
{
    use HasFactory;
    public $fillable = ['id', 'staff_id', 'amount'];

    public function staff(){
        return $this->belongsTo(User::class);
    }

    public static function staffSalary($user_id)
    {
        return StaffSalary::select(DB::raw('SUM(amount) as amount'))->where('staff_id',$user_id)->get()->first()['amount'] ?? 0;
    }

}
