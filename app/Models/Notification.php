<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    public static function forUser($user_id) {
        return self::where('user_id', $user_id);
    }

    public static function getUnreadNotificationsCount($user_id){
        $user = User::find($user_id);
        return $user->unreadNotifications->count();
    }
    public static function getLatestUnreadNotifications($user_id){
        $user = User::find($user_id);
        return $user->unreadNotifications;
    }
}
