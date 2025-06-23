<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'keyword'
    ];

    public function assignUserGroups(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AssignUserGroup::class);
    }

    public function approval_levels(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ApprovalLevel::class);
    }
}
