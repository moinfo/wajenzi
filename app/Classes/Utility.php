<?php


namespace App\Classes;


use App\Models\AssignUserGroup;
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
        // Check if $array is null or empty
        if ($array === null || $array === '') {
            return 0; // or return null, or whatever default value makes sense
        }

        $str1 = $array;
        $x = str_replace(',', '', $str1);
        if (is_numeric($x)) {
            return $x;
        }

        // Consider adding a default return value here
        return $str1; // or return null, or some other appropriate default
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

    public static function expire($expiration_date) // delare the function and get the expiration date as a parameter
    {
        $date=strtotime($expiration_date); // get the expiration date in seconds
        $days_left=ceil(($date-time())/(60*60*24)); // calculate the days left. calculate the expiration date minus the current time in seconds. Devide the difference by the seonds in one day
        // The result number will be the days left.
        return $days_left; //return the value
    }
    public static function calculate_expiry( $signupDate ) {

        // Convert data into usable timestamp
        $signupDate = strtotime( $signupDate );
        $cutoffYear = date('Y', $signupDate) + 1;

        // Set the expiry to be the last day of Feb (the first day of March -1)
        $expiryDate = new DateTime();
        $expiryDate->setTimestamp( mktime( 0, 0, 0, 3, 1, $cutoffYear ) );
        $expiryDate->sub( new DateInterval('P1D') );

    }

    public static function monthNames($start = 1, $end = 12)
    {
        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $months = array_combine(range($start, $end), array_slice($months, $start - 1, $end + 1 - $start));
        return $months;
    }

    public static function money_format($number, $negative_brackets = true) {
        if ($number < 0 && $negative_brackets) {
            $number = ltrim($number, '-');
            $result = number_format($number, 2);
            $result = '(' . $result . ')';
        } else {
            $result = number_format($number, 2);
        }

        return $result;
    }

    public static function dateToDb($date)
    {
        $_date = new DateTime($date);
        return $_date->format('Y-m-d');
    }

    public static function countDays($start_date, $end_date)
    {
        $now = strtotime("$end_date"); // or your date as well
        $your_date = strtotime("$start_date");
        $datediff = $now - $your_date;

       return round($datediff / (60 * 60 * 24));
    }

    public static function sendSingleDestination($phone, $message){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://messaging-service.co.tz/api/sms/v1/text/single',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{"from":"LERUMA ENT", "to":"'.$phone.'",  "text": "'.$message.'", "senderID": "LERUMA ENT"}',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic TXVoaWRpbmk6MDc1NDg2MzgwMg==',
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public static function sendSingleMessageMultipleDestination($phones,$message){
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://messaging-service.co.tz/api/sms/v1/text/single',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{"from":"LERUMA ENT", "to":["'.$phones.'"],  "text": "'.$message.'", "senderID": "LERUMA ENT"}',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic TXVoaWRpbmk6MDc1NDg2MzgwMg==',
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }


    public static function userGroupsArray($id)
    {
        $user_group_id = AssignUserGroup::where('user_id',$id)->get();
        $user_group_ids = array();
        foreach ($user_group_id as $group_id) {
            array_push($user_group_ids, $group_id['user_group_id']);
        }

        return $user_group_ids;
    }

    public static function check_in_range($start_date, $end_date, $date_from_user) {
        // Convert to timestamp
        $start = strtotime($start_date);
        $end = strtotime($end_date);
        $check = strtotime($date_from_user);

        // Check that user date is between start & end
        if(($start <= $check ) && ($check <= $end)){
            return true;
        }else{
            return false;
        }
    }

}
