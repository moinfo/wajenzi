<?php

namespace App\Http\Controllers;

use App\Classes\Utility;
use App\Models\FinancialCharge;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class MessageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'Message')) {
            $phone = $request->input('phone');
            $message = $request->input('message');
            if ($phone[0] == 0){
                $phone_number = '255'.substr("$phone", 1);
            }elseif ($phone[0] == 2){
                $phone_number = $phone;
            }else{
                $phone_number = '255'.$phone;
            }
            Utility::sendSingleDestination("$phone_number","$message");
            return back();
        }
        $messages = Message::latest()->get();

        $data = [
            'messages' => $messages
        ];
        return view('pages.messages.messages_index')->with($data);
    }
    public function bulk_sms(Request $request)
    {

        $message = $request->input('message');
        $department_id = $request->input('department_id');
        if($department_id != 0){
            $users = \App\Models\User::select('phone_number')->where('department_id',$department_id)->where('status','ACTIVE')->where('phone_number','!=', NULL)->get()->toArray();
            $phone_number =  '255'.implode('","255', array_column($users, "phone_number"));
            Utility::sendSingleMessageMultipleDestination($phone_number,$message);
            $users_section = \App\Models\User::select('phone_number','name')->where('department_id',$department_id)->where('status','ACTIVE')->where('phone_number','!=', NULL)->get()->toArray();
            foreach ($users_section as $index => $item) {
                $phone_number =  '255'.$item['phone_number'];
                $data = [
                    'name' =>  $item['name'] ?? null,
                    'phone' =>  $item['phone_number'] ?? null,
                    'message' =>  $message,
                    'created_at' =>  date('Y-m-d H:i:s'),
                    'updated_at' =>  date('Y-m-d H:i:s'),
                ];
                \App\Models\Message::insert($data);
            }
        }else {
            $users = \App\Models\User::select('phone_number')->where('status', 'ACTIVE')->where('phone_number', '!=', NULL)->get()->toArray();
            $phone_number = '255' . implode('","255', array_column($users, "phone_number"));
            Utility::sendSingleMessageMultipleDestination($phone_number, $message);
            $users_section = \App\Models\User::select('phone_number', 'name')->where('status', 'ACTIVE')->where('phone_number', '!=', NULL)->get()->toArray();
            foreach ($users_section as $index => $item) {
                $phone_number = '255' . $item['phone_number'];
                $data = [
                    'name' => $item['name'] ?? null,
                    'phone' => $item['phone_number'] ?? null,
                    'message' => $message,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                \App\Models\Message::insert($data);
            }
        }
        return Redirect::back();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Message  $message
     * @return \Illuminate\Http\Response
     */
    public function show(Message $message)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Message  $message
     * @return \Illuminate\Http\Response
     */
    public function edit(Message $message)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Message  $message
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Message $message)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Message  $message
     * @return \Illuminate\Http\Response
     */
    public function destroy(Message $message)
    {
        //
    }
}
