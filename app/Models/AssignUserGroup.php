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

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function userGroup(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }
}
