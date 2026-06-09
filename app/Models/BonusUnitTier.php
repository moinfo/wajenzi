<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusUnitTier extends Model
{
    protected $fillable = ['min_amount', 'max_amount', 'max_units'];

    /**
     * Get max units for a given project budget amount.
     *
     * A non-positive amount means the project has no recorded budget (the lead's
     * estimated_value is 0 or null). Such projects get NO bonus cap — returning 0
     * here prevents an unpriced project from silently inheriting the highest tier.
     * Set the real budget and recompute to give it a meaningful cap.
     */
    public static function getMaxUnits(float $amount): int
    {
        if ($amount <= 0) {
            return 0;
        }

        $tier = static::where('min_amount', '<', $amount)
            ->where('max_amount', '>=', $amount)
            ->first();

        if ($tier) {
            return $tier->max_units;
        }

        // No tier matched — only valid when the amount exceeds every tier's
        // ceiling. Use the highest tier in that case; otherwise no cap applies.
        $highest = static::orderBy('max_amount', 'desc')->first();
        if ($highest && $amount > $highest->max_amount) {
            return $highest->max_units;
        }

        return 0;
    }
}
