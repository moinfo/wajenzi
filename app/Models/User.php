<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    public $table = 'users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'name',
        'email',
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

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];


    public function department() {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function position() {
        return $this->belongsTo(Position::class, 'position_id');
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getAvatar() {
        return 'media/avatars/avatar15.jpg';
    }

    public function getResignation() {
        return 'Weird Resignation';
        // TODO work on it
    }

    public function getName(){
        return $this->name;
    }

    public static function getCount() {
        return count(self::all());
    }

    public function approvals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Approval::class);
    }

    public function assignUserGroups(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AssignUserGroup::class);
    }

}
