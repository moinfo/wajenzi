<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory;
    protected $fillable = [
        'foreign_currency_id',
        'base_currency_id',
        'rate',
        'month',
        'year',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'rate' => 'double',
        'entry_timestamp' => 'datetime',
    ];

    /**
     * Get the foreign currency associated with the exchange rate.
     */
    public function foreignCurrency()
    {
        // Assuming you have a Currency model
        return $this->belongsTo(Currency::class, 'foreign_currency_id', 'id');
    }

    /**
     * Get the base currency associated with the exchange rate.
     */
    public function baseCurrency()
    {
        // Assuming you have a Currency model
        return $this->belongsTo(Currency::class, 'base_currency_id', 'id');
    }

    /**
     * Scope a query to filter by year and month.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $year
     * @param  int  $month
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPeriod($query, $year, $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    /**
     * Scope a query to filter by currency pair.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $foreignCurrencyId
     * @param  int  $baseCurrencyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCurrencies($query, $foreignCurrencyId, $baseCurrencyId)
    {
        return $query->where('foreign_currency_id', $foreignCurrencyId)
            ->where('base_currency_id', $baseCurrencyId);
    }
}
