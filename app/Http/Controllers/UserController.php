<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UsersPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;

class UserController extends Controller
{
    public function index(Request $request) {
        $data = [];
        return view('pages.user.user_index')->with($data);
    }
    public function profile(Request $request) {
        $data = [
            'user' => Auth::user(),
        ];
        return view('pages.user.user_profile')->with($data);
    }
    public function settings(Request $request) {
        $data = [];
        return view('pages.user.user_settings')->with($data);
    }
    public function inbox(Request $request) {
        $data = [];
        return view('pages.user.user_inbox')->with($data);
    }
    public function notifications(Request $request) {
        $data = [];
        return view('pages.user.user_notifications')->with($data);
    }
    public function user_permissions(Request $request) {
        foreach($request->permission_id as $permissions) {

            UsersPermission::create([
                'user_id' => $request->user_id,
                'permission_id' => $permissions,
            ]);
        }
        //dd($request);
        $data = [];
        return view('pages.settings.settings_users')->with($data);
    }
    public static function readAllNotification(){
        $notifiable_id = Auth::user()->id;
        $user = User::find($notifiable_id);
        foreach ($user->unreadNotifications as $notification) {
            $notification->markAsRead();
        }
        return Redirect::back();
    }

    public function update_password(Request $request){
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|same:new_password_confirmation',
            'new_password_confirmation' => 'required|string',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->input('current_password'), $user->password)) {
            return Redirect::back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        return Redirect::back()->with('success', 'Password updated successfully.');
    }

    public function update_profile(Request $request){

        if($this->handleCrud($request, 'User')) {
            return back();
        }

        return Redirect::back();

//        $data = [
//            'users' => User::all()
//        ];
//        return view('pages.settings.settings_users')->with($data);
    }
}
