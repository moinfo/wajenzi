<?php

namespace App\Http\Controllers;

use App\Models\AttendanceType;
use Illuminate\Http\Request;
use App\Classes\Utility;

class AttendanceTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'AttendanceType')) {
            return back();
        }
        
        $data = [
            'attendance_types' => AttendanceType::orderBy('name')->get()
        ];
        
        return view('pages.settings.settings_attendance_types')->with($data);
    }

}