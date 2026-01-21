<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'sync_type',
        'sync_data',
        'status',
        'records_synced',
        'records_failed',
        'errors',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'sync_data' => 'array',
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PARTIAL = 'partial';

    public const TYPE_PUSH = 'push';
    public const TYPE_PULL = 'pull';
    public const TYPE_FULL = 'full';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Start a new sync log.
     */
    public static function start(int $userId, string $deviceId, string $type): static
    {
        return static::create([
            'user_id' => $userId,
            'device_id' => $deviceId,
            'sync_type' => $type,
            'status' => self::STATUS_PENDING,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark sync as completed.
     */
    public function complete(int $synced = 0, int $failed = 0, ?array $errors = null): void
    {
        $this->update([
            'status' => $failed > 0 ? self::STATUS_PARTIAL : self::STATUS_SUCCESS,
            'records_synced' => $synced,
            'records_failed' => $failed,
            'errors' => $errors,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark sync as failed.
     */
    public function fail(array $errors = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'errors' => $errors,
            'completed_at' => now(),
        ]);
    }
}
