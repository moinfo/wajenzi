<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BongeCustomer extends Model
{
    use HasFactory;
    protected $connection = 'mysql5';
    protected $table="ospos_customers";

    public function people(){
        return $this->belongsTo(BongePeople::class,'person_id','person_id');
    }


}
