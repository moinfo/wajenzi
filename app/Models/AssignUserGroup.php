<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignUserGroup extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'user_group_id'
    ];

    public static function getAssignUserGroup($user_id)
    {
        return AssignUserGroup::where('user_id',$user_id)->get();
    }

    public static function getUserId($next_user_group_id)
    {
        return AssignUserGroup::where('user_group_id',$next_user_group_id)->get()->first();
    }

    /**
     * Return every user assigned to the given group (not just the first).
     * Used for fan-out notifications when any member of a role-group can act.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\User>
     */
    public static function getUsersInGroup($user_group_id)
    {
        $userIds = AssignUserGroup::where('user_group_id', $user_group_id)->pluck('user_id');
        if ($userIds->isEmpty()) {
            return User::query()->whereRaw('1=0')->get();
        }
        return User::whereIn('id', $userIds)->get();
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userGroup(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }
}
