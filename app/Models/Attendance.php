<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Attendance extends Model
{
    use HasFactory;

    public $fillable = ['user_id', 'device_user_id', 'record_time', 'ip', 'comment', 'file'];


    public static function getUserAttendanceStatus($start_date, $end_date, $user_id)
    {
        $comeEarlyCount = 0;
        $comeLateCount = 0;
        $absentCount = 0;

        // Retrieve attendance records within the specified date range for the user
        $attendanceRecords = Attendance::where('user_id', $user_id)
            ->whereBetween('record_time', [$start_date, $end_date])
            ->get();

        // Retrieve work permit records that intersect with the specified date range for the user
        $workPermitRecords = WorkPermit::where('user_id', $user_id)
            ->where(function ($query) use ($start_date, $end_date) {
                $query->whereBetween('start_date', [$start_date, $end_date])
                    ->orWhereBetween('end_date', [$start_date, $end_date]);
            })
            ->get();

        // Calculate the time threshold for "come early" and "come late"
        $earlyThreshold = Carbon::parse('06:30:00');
        $lateThreshold = Carbon::parse('06:31:00');

        // Loop through each attendance record and determine the user's attendance status
        foreach ($attendanceRecords as $attendanceRecord) {
            $recordTime = Carbon::parse($attendanceRecord->record_time);
            if ($recordTime < $earlyThreshold) {
                $comeEarlyCount++;
            } elseif ($recordTime > $lateThreshold) {
                $comeLateCount++;
            }
        }

        // Check for absent records where there is no attendance or intersecting work permit record
//        $absentCount = ($end_date->diffInDays($start_date) + 1) * count($workPermitRecords) - count($attendanceRecords);
        $formatted_dt1 = Carbon::parse($start_date);
        $formatted_dt2 = Carbon::parse($end_date);
        $date_diff = $formatted_dt1->diffInDays($formatted_dt2);

        $absentCount =$date_diff - ($comeEarlyCount+$comeLateCount);
        return [
            'come_early' => $comeEarlyCount,
            'come_late' => $comeLateCount,
            'absent' => $absentCount,
        ];
    }
    public static function isAttendEarly($staff_id, $date)
    {
        $late_in = \App\Models\Shift::getLateInTime();
        $date_time_limit_start = "$date".' '.'05:00:00';
        $date_time_limit_end = "$date".' '.$late_in;
//        return $date_time_limit;
        $attendance = Attendance::where('device_user_id',$staff_id)
            ->where('record_time','>=',"$date_time_limit_start")
            ->where('record_time','<=',"$date_time_limit_end")->limit(1)->get()->first()['record_time'];
        if ($attendance == null || $attendance == ''){
            return 2;
        }elseif($attendance <= $date_time_limit_end){
            return 1;
        }elseif($attendance >= $date_time_limit_end){
            return 0;
        }
    }

//    public static function getAttendancesTimeInByDate($start_date,$user_id){
//        return Attendance::Join('users','users.id','=','attendances.user_id')->where('users.id',$user_id)->whereDate('attendances.record_time',$start_date)->groupBy('attendances.device_user_id')->get()->first();
//    }
    public static function getAttendancesTimeInByDate($start_date, $user_id) {
        return Attendance::leftJoin('users', 'users.id', '=', 'attendances.user_id')
            ->where('users.id', $user_id)
            ->whereDate('attendances.record_time', $start_date)
            ->select('attendances.*')
            ->orderBy('attendances.device_user_id')
            ->first();
    }
    public static function countAttended( $date,$staff_id = null)
    {
        $date_time_limit_start = "$date".' '.'05:00:00';
        $date_time_limit_end = "$date".' '.'08:00:00';
//        return $date_time_limit;
        $attendance = Attendance::where('record_time','>=',"$date_time_limit_start")->where('record_time','<=',"$date_time_limit_end");
        if($staff_id != null){
            $attendance = $attendance->where('device_user_id',$staff_id);
        }
        $attendance = $attendance->get()->limit(1);
        if (count($attendance) > 0){
            return true;
        }else{
            return false;
        }
    }

    public static function getStaffInTime($staff_id, $start_date)
    {
        $attendance = Attendance::select('record_time')->whereDate('record_time',"$start_date");
        if($staff_id != null){
            $attendance = $attendance->where('device_user_id',$staff_id);
        }
       return $attendance->limit(1)->get()->first()['record_time'];
    }

    public static function getStaffInTimeId($staff_id, $start_date)
    {
        $attendance = Attendance::select('id')->whereDate('record_time',"$start_date");
        if($staff_id != null){
            $attendance = $attendance->where('device_user_id',$staff_id);
        }
       return $attendance->limit(1)->get()->first()['id'];
    }

    public static function getStaffOutTime($staff_id, $start_date)
    {
        $attendance = Attendance::select('record_time')->whereDate('record_time',"$start_date");
        if($staff_id != null){
            $attendance = $attendance->where('device_user_id',$staff_id);
        }
       return $attendance->orderBy('record_time','desc')->limit(1)->get()->first()['record_time'];
    }

    public static function displayDates($date1, $date2, $format = 'Y-m-d')
    {
            $dates = array();
            $current = strtotime($date1);
            $date2 = strtotime($date2);
            $stepVal = '+1 day';
            while( $current <= $date2 ) {
                $dates[] = date($format, $current);
                $current = strtotime($stepVal, $current);
            }
            return $dates;

    }

    public static function getStaffInTimeAttachment($staff_id, $start_date)
    {
        $attendance = Attendance::select('file')->whereDate('record_time',"$start_date");
        if($staff_id != null){
            $attendance = $attendance->where('device_user_id',$staff_id);
        }
        return $attendance->limit(1)->get()->first()['file'];
    }

    public static function getStaffInTimeComment($staff_id, $start_date)
    {
        $attendance = Attendance::select('comment')->whereDate('record_time',"$start_date");
        if($staff_id != null){
            $attendance = $attendance->where('device_user_id',$staff_id);
        }
        return $attendance->limit(1)->get()->first()['comment'];
    }

    public static function getAllDateAttended($staff_id, $start_date, $end_date)
    {
        return Attendance::whereBetween('record_time',[$start_date,$end_date])->where('user_id',$staff_id)->groupBy(DB::raw('Date(record_time)'))->get();
    }

    public static function getTotalDaysAttended($staff_id, $start_date, $end_date)
    {
        return count(self::getAllDateAttended($staff_id, $start_date, $end_date));
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function lastEntry($ip)
    {
        return self::query()->where('ip',$ip)->orderByDesc('record_time')->first();
    }

    public static function recordFromDevice(?array $data)
    {
        $newData = [];

        foreach ($data as $index => $item) {
            $ip = $item['ip'];
            $lastEntry = Attendance::lastEntry($ip);
            if($lastEntry){
                $newDate = (strtotime($item['recordTime'])  + (60*60*3));
                $oldDate = strtotime($lastEntry->record_time);
                if($newDate <= $oldDate) {
                continue;
                }
            }
            $entry = [
                'user_id' => self::mapUserId($item['deviceUserId']),
                'device_user_id' => $item['deviceUserId'],
                'record_time' => date('Y-m-d H:i:s', strtotime($item['recordTime']) + (60*60*3)),
                'ip' => $item['ip'],
            ];
            if(self::create($entry)){
            $newData[] = $entry;
            }
        }

        return $newData;
    }

    static function mapUserId($userSn)
    {
        // TODO In case of different IDs between db users and device users
        // implement logic for ID mapping here

        return User::where('user_device_id',$userSn)->get()->first()->id ?? 0;
    }

}
