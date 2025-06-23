<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessApprovalFlow extends Model
{
    use HasFactory;

    public $fillable = ['name','approvable_type'];
}
