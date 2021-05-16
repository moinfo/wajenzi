<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    public $fillable = ['id', 'staff_id','title', 'body', 'link', 'icon', 'shown', 'seen'];

    public static function forUser($user_id) {
        return self::where('user_id', $user_id);
    }

    public static function getUnreadNotification($user_id){
        return Notification::where('staff_id',$user_id)->where('shown',0)->limit(5)->get();
    }
    public static function getCountUnreadNotification($user_id){
            return Notification::where('staff_id',$user_id)->where('shown',0)->get()->count();

    }
}
