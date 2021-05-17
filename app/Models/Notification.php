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

    public static function notifyNextApproval($next_user_id,$class_name,$link){
       $save =  Notification::create([
            'staff_id' => $next_user_id,
            'title' => $class_name. ' '. 'Waiting for Approval',
            'body' => 'A new '.$class_name.' has been created and submitted. You are required to review and approve the created '. $class_name,
            'link' => $link]);
       return $save;
    }
}
