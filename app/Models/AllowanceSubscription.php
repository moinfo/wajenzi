<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AllowanceSubscription extends Model
{
    use HasFactory;
    public $fillable = ['id', 'staff_id', 'amount', 'date', 'allowance_id'];

    public function staff(){
        return $this->belongsTo(User::class);
    }

    public function allowance(){
        return $this->belongsTo(Allowance::class);
    }
}
