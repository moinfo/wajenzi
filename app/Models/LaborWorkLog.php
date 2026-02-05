<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaborWorkLog extends Model
{
    use HasFactory;

    protected $table = 'labor_work_logs';

    protected $fillable = [
        'labor_contract_id',
        'logged_by',
        'log_date',
        'work_done',
        'workers_present',
        'hours_worked',
        'progress_percentage',
        'challenges',
        'materials_used',
        'photos',
        'weather_conditions',
        'notes'
    ];

    protected $casts = [
        'log_date' => 'date',
        'hours_worked' => 'decimal:2',
        'progress_percentage' => 'decimal:2',
        'materials_used' => 'array',
        'photos' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->log_date)) {
                $model->log_date = now();
            }
            if (empty($model->logged_by)) {
                $model->logged_by = auth()->id();
            }
        });
    }

    // Relationships
    public function contract(): BelongsTo
    {
        return $this->belongsTo(LaborContract::class, 'labor_contract_id');
    }

    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    // Calculated attributes
    public function getPhotoCountAttribute(): int
    {
        return is_array($this->photos) ? count($this->photos) : 0;
    }

    public function getMaterialsCountAttribute(): int
    {
        return is_array($this->materials_used) ? count($this->materials_used) : 0;
    }

    // Weather badge helper
    public function getWeatherBadgeClassAttribute(): string
    {
        return match($this->weather_conditions) {
            'sunny' => 'warning',
            'cloudy' => 'secondary',
            'rainy' => 'info',
            'stormy' => 'danger',
            default => 'light'
        };
    }

    /**
     * Add photos to the log
     */
    public function addPhotos(array $photoPaths): void
    {
        $current = $this->photos ?? [];
        $this->photos = array_merge($current, $photoPaths);
        $this->save();
    }

    /**
     * Remove a photo from the log
     */
    public function removePhoto(string $photoPath): void
    {
        $current = $this->photos ?? [];
        $this->photos = array_filter($current, fn($path) => $path !== $photoPath);
        $this->save();
    }

    // Scopes
    public function scopeForContract($query, $contractId)
    {
        return $query->where('labor_contract_id', $contractId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('log_date', $date);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('log_date', [$startDate, $endDate]);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('log_date', '>=', now()->subDays($days));
    }
}
