<?php
namespace App\Traits;

use App\Models\ProjectActivityLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasActivityLogs
{
    public function activityLogs(): MorphMany
    {
        return $this->morphMany(ProjectActivityLog::class, 'loggable');
    }

    public function logActivity(string $activityType, string $description): void
    {
        $this->activityLogs()->create([
            'user_id' => auth()->id(),
            'activity_type' => $activityType,
            'description' => $description,
            'ip_address' => request()->ip()
        ]);
    }
}
