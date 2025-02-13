<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffBankDetail extends Model
{
    use HasFactory;
    public $fillable = ['staff_id','bank_id','account_number','branch'];

    public function staff(){
        return $this->belongsTo(User::class);
    }

    public function bank(){
        return $this->belongsTo(Bank::class);
    }

}
