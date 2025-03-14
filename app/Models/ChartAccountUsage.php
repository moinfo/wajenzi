<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartAccountUsage extends Model
{
    use HasFactory;

    protected $table = 'charts_account_usages';
    protected $fillable = ['name', 'charts_account_id', 'description'];


    public function chartAccount()
    {
        return $this->belongsTo(ChartAccount::class, 'charts_account_id');
    }
}
