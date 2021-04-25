<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeductionSetting extends Model
{
    use HasFactory;
    public $fillable = ['id', 'minimum_amount', 'deduction_id', 'maximum_amount', 'employee_percentage', 'employer_percentage', 'additional_amount'];

    public function deduction(){
        return $this->belongsTo(Deduction::class);
    }
}
