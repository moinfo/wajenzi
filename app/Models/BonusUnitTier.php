<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusUnitTier extends Model
{
    protected $fillable = ['min_amount', 'max_amount', 'max_units'];

    /**
     * Get max units for a given project budget amount.
     */
    public static function getMaxUnits(float $amount): int
    {
        $tier = static::where('min_amount', '<', $amount)
            ->where('max_amount', '>=', $amount)
            ->first();

        // If amount exceeds all tiers, use the highest tier
        if (!$tier) {
            $tier = static::orderBy('max_amount', 'desc')->first();
            if ($tier && $amount > $tier->max_amount) {
                return $tier->max_units;
            }
        }

        return $tier ? $tier->max_units : 0;
    }
}
