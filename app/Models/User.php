<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'gender',
        'password',
        'employee_number',
        'recruitment_date',
        'address',
        'national_id',
        'tin',
        'dob',
        'status',
        'marital_status',
        'designation',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // Existing relationships
    /**
     * @var mixed
     */
    private $role;

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    // Project Management relationships
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_team_members')
            ->withPivot('role', 'assigned_date', 'end_date', 'status')
            ->withTimestamps();
    }

    public function siteVisits(): HasMany
    {
        return $this->hasMany(ProjectSiteVisit::class, 'inspector_id');
    }

    public function projectDesigns(): HasMany
    {
        return $this->hasMany(ProjectDesign::class, 'designer_id');
    }

    public function materialRequests(): HasMany
    {
        return $this->hasMany(ProjectMaterialRequest::class, 'requester_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ProjectExpense::class, 'created_by');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ProjectActivityLog::class);
    }

    // Permission handling methods
    public function hasProjectPermission($permission, $project_id = null)
    {
        return UsersPermission::isUserAllowed(
            $this->id,
            'project',
            $permission,
            $project_id
        );
    }

    public function assignProjectRole($project_id, $role)
    {
        return $this->projects()->attach($project_id, [
            'role' => $role,
            'assigned_date' => now(),
            'status' => 'active'
        ]);
    }

    // Helper methods for project access
    public function getActiveProjects()
    {
        return $this->projects()
            ->wherePivot('status', 'active')
            ->wherePivot('end_date', null)
            ->get();
    }

    public function canManageProject($project_id)
    {
        return $this->hasProjectPermission('manage_project', $project_id) ||
            $this->role->name === 'project_manager';
    }
}
