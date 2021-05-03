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
        'permission_id'
    ];

    public static function getUserPermissions($user_id)
    {
        return \App\Models\UsersPermission::select([DB::raw("permissions.name as permission_name")])->join('permissions', 'permissions.id','=','users_permissions.permission_id')->Where('user_id',$user_id)->get();
    }

    public function users() {
        return $this->hasMany(User::class);
    }
}
