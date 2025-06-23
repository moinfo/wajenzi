<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class Notification extends Model
{
    use HasFactory;
    protected $keyType = 'string';
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
