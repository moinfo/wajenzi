<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentCreatorCrew extends Model
{
    protected $table = 'content_creator_crew';
    protected $fillable = ['user_id', 'role', 'skills', 'online_status'];

    protected $casts = ['skills' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusDotAttribute(): string
    {
        return match($this->online_status) {
            'online'  => '🟢',
            'busy'    => '🟠',
            'away'    => '🟡',
            default   => '⚫',
        };
    }
}
