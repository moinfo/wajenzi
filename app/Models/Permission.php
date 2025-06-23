<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'permission_type',
        'module' // New field to identify permission module (e.g., 'project', 'hr', etc.)
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'roles_permissions');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_permissions')
            ->withPivot('project_id');
    }

    // New scope for project permissions
    public function scopeProject($query)
    {
        return $query->where('permission_type', 'project');
    }

    // Common project permissions
    public static function getProjectPermissions()
    {
        return [
            'view_project',
            'create_project',
            'edit_project',
            'delete_project',
            'manage_boq',
            'manage_expenses',
            'view_reports',
            'manage_team'
        ];
    }
}
