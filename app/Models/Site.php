<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'description',
        'status',
        'start_date',
        'expected_end_date',
        'actual_end_date',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'expected_end_date' => 'date',
        'actual_end_date' => 'date',
    ];

    /**
     * Relationships
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function supervisorAssignments()
    {
        return $this->hasMany(SiteSupervisorAssignment::class);
    }

    public function currentSupervisorAssignment()
    {
        return $this->hasOne(SiteSupervisorAssignment::class)
            ->where('is_active', true)
            ->latest();
    }

    public function currentSupervisor()
    {
        return $this->hasOneThrough(
            User::class,
            SiteSupervisorAssignment::class,
            'site_id',
            'id',
            'id',
            'user_id'
        )->where('site_supervisor_assignments.is_active', true);
    }

    public function dailyReports()
    {
        return $this->hasMany(SiteDailyReport::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ACTIVE');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'INACTIVE');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'COMPLETED');
    }

    /**
     * Helper methods
     */
    public function isActive()
    {
        return $this->status === 'ACTIVE';
    }

    public function hasActiveSupervisor()
    {
        return $this->currentSupervisorAssignment()->exists();
    }

    public function getProgressPercentage()
    {
        $latestReport = $this->dailyReports()->latest('report_date')->first();
        return $latestReport ? $latestReport->progress_percentage : 0;
    }

    public function canDelete()
    {
        return $this->dailyReports()->count() === 0;
    }
}