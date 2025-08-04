<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteSupervisorAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'user_id',
        'assigned_from',
        'assigned_to',
        'is_active',
        'assigned_by',
        'notes'
    ];

    protected $casts = [
        'assigned_from' => 'date',
        'assigned_to' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeForSupervisor($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSite($query, $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    /**
     * Helper methods
     */
    public function deactivate()
    {
        $this->update([
            'is_active' => false,
            'assigned_to' => now()->format('Y-m-d')
        ]);
    }

    public function isCurrentlyActive()
    {
        return $this->is_active && 
               $this->assigned_from <= now() && 
               ($this->assigned_to === null || $this->assigned_to >= now());
    }

    public function getDurationInDays()
    {
        $from = $this->assigned_from;
        $to = $this->assigned_to ?? now();
        
        return $from->diffInDays($to);
    }

    /**
     * Static methods
     */
    public static function assignSupervisor($siteId, $userId, $assignedBy, $notes = null)
    {
        // Deactivate current assignment if exists
        self::where('site_id', $siteId)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'assigned_to' => now()->format('Y-m-d')
            ]);

        // Create new assignment
        return self::create([
            'site_id' => $siteId,
            'user_id' => $userId,
            'assigned_from' => now()->format('Y-m-d'),
            'assigned_to' => null,
            'is_active' => true,
            'assigned_by' => $assignedBy,
            'notes' => $notes
        ]);
    }
}