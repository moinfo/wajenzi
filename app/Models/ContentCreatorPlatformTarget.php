<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentCreatorPlatformTarget extends Model
{
    protected $fillable = ['platform', 'target_posts', 'month', 'year'];

    public function getCurrentPostsAttribute(): int
    {
        return ContentCreatorTask::where('platform', $this->platform)
            ->where('status', 'published')
            ->whereMonth('approved_at', $this->month)
            ->whereYear('approved_at', $this->year)
            ->count();
    }

    public function getProgressPercentAttribute(): int
    {
        if (!$this->target_posts) return 0;
        return min(100, (int) round(($this->current_posts / $this->target_posts) * 100));
    }
}
