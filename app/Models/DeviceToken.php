<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'fcm_token',
        'platform',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all FCM tokens for a user.
     */
    public static function getTokensForUser(int $userId): array
    {
        return static::where('user_id', $userId)
            ->pluck('fcm_token')
            ->toArray();
    }

    /**
     * Get all FCM tokens for multiple users.
     */
    public static function getTokensForUsers(array $userIds): array
    {
        return static::whereIn('user_id', $userIds)
            ->pluck('fcm_token')
            ->toArray();
    }
}
