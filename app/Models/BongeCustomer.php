<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BongeCustomer extends Model
{
    use HasFactory;
    public $fillable = ['id', 'name','system_id','seller','bonge_customer_id'];


}
