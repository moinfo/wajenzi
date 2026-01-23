<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'name',
        'year',
    ];

    protected $casts = [
        'date' => 'date',
        'year' => 'integer',
    ];

    /**
     * Check if a date is a holiday
     */
    public static function isHoliday($date): bool
    {
        return static::where('date', $date)->exists();
    }

    /**
     * Get all holiday dates for a year
     */
    public static function getHolidayDates($year): array
    {
        return static::where('year', $year)
            ->pluck('date')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->toArray();
    }

    /**
     * Get holidays between two dates
     */
    public static function getHolidaysBetween($startDate, $endDate): array
    {
        return static::whereBetween('date', [$startDate, $endDate])
            ->pluck('date')
            ->map(fn($d) => $d->format('Y-m-d'))
            ->toArray();
    }
}
