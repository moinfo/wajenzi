<?php


namespace App\Classes;


use App\Models\Sale;
use Illuminate\Support\Facades\Auth;

class Utility
{
    /**
     *Returns the session name
     */
    public static function sessionName() {
        return $session_id = md5(env('APP_NAME', 'Financial Analysis'));
    }

    /**
     * Show UI Notification
     * @param $text
     * @param $type
     * @param $title
     */
    public static function notify($text, $type, $title) {

    }

    public static function strip_commas($array)
    {
        $str1 = $array;
        $x = str_replace( ',', '', $str1);
        if( is_numeric($x))
        {
            return $x;
        }
    }

    public static function getLastRow($class_name){
        $class = '\App\Models\\'. $class_name;
        return $class::orderBy('id','DESC')->get()->first();
    }

    public static function getLastId($class_name){
        return self::getLastRow($class_name)->id ?? 0;
    }


    public static function getMonthNames($start = 1, $end = 12) {
        $months = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
        return array_slice($months, $start - 1, $end - 1);
    }
    public static function isAdmin(){
        if(Auth::user()->id == 1){
            return true;
        }else{
            return false;
        }
    }
}
