<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UsersPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'permission_id',
        'project_id' // New field for project-specific permissions
    ];

    public static function getUserPermissions($user_id, $project_id = null)
    {
        $query = self::select([DB::raw("permissions.name as permission_name")])
            ->join('permissions', 'permissions.id', '=', 'users_permissions.permission_id')
            ->where('user_id', $user_id);

        if ($project_id) {
            $query->where('project_id', $project_id);
        }

        return $query->get();
    }

    public static function isUserAllowed($user_id, $permission_type, $permission_name, $project_id = null)
    {
        $query = self::select([DB::raw("permissions.name as permission_name")])
            ->join('permissions', 'permissions.id', '=', 'users_permissions.permission_id')
            ->where('user_id', $user_id)
            ->where('permissions.permission_type', $permission_type)
            ->where('permissions.name', $permission_name);

        if ($project_id) {
            $query->where('project_id', $project_id);
        }

        return $query->exists();
    }

    // New method for project-specific permissions
    public static function grantProjectPermission($user_id, $permission_id, $project_id)
    {
        return self::create([
            'user_id' => $user_id,
            'permission_id' => $permission_id,
            'project_id' => $project_id
        ]);
    }
}
