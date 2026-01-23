<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class ProjectAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'project_schedule_id',
        'user_id',
        'role_id',
        'status',
        'assigned_by',
        'assigned_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the lead
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the schedule
     */
    public function schedule()
    {
        return $this->belongsTo(ProjectSchedule::class, 'project_schedule_id');
    }

    /**
     * Get the assigned user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the role
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the user who assigned
     */
    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Scope for active assignments
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted()
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->save();
    }

    /**
     * Get the count of active assignments for a user
     */
    public static function getActiveCountForUser($userId): int
    {
        return static::where('user_id', $userId)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Find the architect with least workload
     */
    public static function findArchitectWithLeastWorkload()
    {
        // Get the Architect role (id = 9 or by name)
        $architectRole = Role::where('name', 'Architect')->first();
        if (!$architectRole) {
            return null;
        }

        // Get all users with Architect role using model_has_roles table
        $architects = User::whereIn('id', function($query) use ($architectRole) {
            $query->select('model_id')
                ->from('model_has_roles')
                ->where('role_id', $architectRole->id)
                ->where('model_type', User::class);
        })->get();

        if ($architects->isEmpty()) {
            return null;
        }

        // Find the one with least active assignments
        $minWorkload = PHP_INT_MAX;
        $selectedArchitect = null;

        foreach ($architects as $architect) {
            $workload = static::getActiveCountForUser($architect->id);
            if ($workload < $minWorkload) {
                $minWorkload = $workload;
                $selectedArchitect = $architect;
            }
        }

        return $selectedArchitect;
    }
}
