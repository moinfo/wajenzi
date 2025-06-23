<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartAccountVariable extends Model
{
    use HasFactory;
    protected $table = 'chart_account_variables';

    protected $fillable = ['variable', 'value'];

}
