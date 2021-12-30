<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Efd extends Model
{
    use HasFactory;
    public $fillable = ['id', 'name'];

    public function sales() {
        return $this->hasMany(Sale::class);
    }

    public function transactions()
    {
        return $this->hasMany(BankReconciliation::class, 'efd_id', 'id');
    }


    public static function allWithTransactions($start_date, $end_date = null)
    {
        $start_date = $start_date ?? date('Y-m-d', 0);
        $end_date =  $end_date ?? date('Y-m-d');
        $res = self::with(["transactions" => function ($query) use($start_date, $end_date) {
            $query->where('date','>=',$start_date)->where('date','<=',$end_date)->with('supplier');
        }])->get();
        return $res;
    }
}
