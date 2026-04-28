<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FieldMarketingTarget extends Model
{
    protected $fillable = [
        'officer_id', 'year', 'month', 'target_visits', 'target_conversions', 'created_by',
    ];

    public function officer()
    {
        return $this->belongsTo(User::class, 'officer_id');
    }
}
