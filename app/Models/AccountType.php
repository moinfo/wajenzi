<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'type',
        'code',
        'normal_balance',
    ];

    /**
     * Relationship with charts accounts
     */
    public function chartAccounts()
    {
        return $this->hasMany(ChartAccount::class, 'account_type', 'id');
    }
}
