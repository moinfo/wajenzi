<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChartAccount extends Model
{
    use HasFactory;
    protected $table = 'charts_accounts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'account_name',
        'account_type',
        'currency',
        'parent',
        'description',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'entry_timestamp' => 'datetime',
        'status' => 'string',
    ];

    /**
     * Get the account type that owns the chart account.
     */
    public function accountType()
    {
        return $this->belongsTo(AccountType::class, 'account_type', 'id');
    }

    /**
     * Get the parent account if exists.
     */
    public function parentAccount()
    {
        return $this->belongsTo(ChartAccount::class, 'parent', 'id');
    }

    /**
     * Get the child accounts.
     */
    public function childAccounts()
    {
        return $this->hasMany(ChartAccount::class, 'parent', 'id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency', 'id');
    }
}
