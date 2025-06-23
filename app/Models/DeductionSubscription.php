<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeductionSubscription extends Model
{
    use HasFactory;

    public $fillable = ['id', 'staff_id', 'deduction_id', 'membership_number'];

    public function staff(){
        return $this->belongsTo(User::class);
    }

    public function deduction(){
        return $this->belongsTo(Deduction::class);
    }
}
