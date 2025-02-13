<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Adjustment extends Model
{
    use HasFactory;
    public $fillable = ['staff_id','date','amount'];

    public function staff(){
        return $this->belongsTo(User::class);
    }

}
