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
        return $this->belongsTo(Staff::class);
    }


}
