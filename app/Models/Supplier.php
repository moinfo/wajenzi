<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class supplier extends Model
{
    use HasFactory;
    public $fillable = ['id', 'name', 'phone', 'address', 'email', 'vrn'];

}
