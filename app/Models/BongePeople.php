<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BongePeople extends Model
{
    use HasFactory;
    protected $connection = 'mysql5';
    protected $table="ospos_people";


}
