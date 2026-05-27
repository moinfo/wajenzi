<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandingProject extends Model
{
    protected $fillable = [
        'title',
        'category',
        'description',
        'price_tzs',
        'price_usd',
        'youtube_url',
        'model_3d_url',
        'is_featured',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'title' => 'array',
        'category' => 'array',
        'description' => 'array',
        'price_tzs' => 'decimal:2',
        'price_usd' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(LandingProjectImage::class)
            ->orderByDesc('is_primary')
            ->orderBy('sort_order');
    }

    public function amenities(): HasMany
    {
        return $this->hasMany(LandingProjectAmenity::class)->orderBy('sort_order');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(LandingProjectLike::class);
    }

    /**
     * Resolve a multilingual JSON value to a single string for the given
     * language, falling back to English then the first available value.
     */
    public static function localize($value, ?string $lang = 'en'): ?string
    {
        if (is_string($value) || is_null($value)) {
            return $value;
        }
        if (!is_array($value)) {
            return (string) $value;
        }
        $lang = $lang ?: 'en';
        if (!empty($value[$lang])) {
            return $value[$lang];
        }
        if (!empty($value['en'])) {
            return $value['en'];
        }
        $first = collect($value)->first(fn ($v) => filled($v));
        return $first !== null ? (string) $first : null;
    }

    /**
     * Recalculate and persist the cached likes_count from the likes table.
     */
    public function syncLikesCount(): int
    {
        $count = $this->likes()->count();
        $this->forceFill(['likes_count' => $count])->saveQuietly();
        return $count;
    }
}