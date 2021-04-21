<?php

namespace App\Http\Controllers;

use App\Classes\Utility;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;



    /**
     * Show UI Notification
     * @param $text
     * @param $type
     * @param $title
     */
    public function notify($text, $title = '',  $type = 'success') {
        $session_notifications = session('notifications') ?? [];
        array_push($session_notifications, ['text' => $text, 'type' => $type, 'title' => $title]);
        session(['notifications' => $session_notifications]);

    }


    public function handleCrud(Request $request, $class_name, $id = null) {
        if($request->isMethod('POST') || $request->isMethod('PUT')) {
//            dd($request);
            if($request->has('addItem')) {
                if($this->crudAdd($request, $class_name)) {
                    $this->notify($class_name .'Added Successfully', 'Added!', 'success');
                } else {
                    $this->notify('Failed to Add '.$class_name, 'Failed', 'error');
                }
                return true;
            } else if($request->has('updateItem')) {
                if($this->crudUpdate($request, $class_name, $id)) {
                    $this->notify($class_name .' Updated Successfully', 'Updated!', 'success');
                } else {
                    $this->notify('Failed to Update '.$class_name , 'Failed', 'error');
                }
                return true;
            }
        } else if($request->isMethod('DELETE')) {
            if($request->has('deleteItem')) {
                if($this->crudDelete($request, $class_name)) {
                    $this->notify($class_name .'Deleted Successfully', 'Added!', 'success');
                } else {
                    $this->notify('Failed to Delete '.$class_name, 'Failed', 'error');
                }
                return true;
            }
        }
        return false;
    }

    private function crudAdd(Request $request, $class_name) {



        if($request->hasFile('file')) {
            $full_class_name = '\App\Models\\'. $class_name;
            $newObj = new $full_class_name();
            $request->validate([
                'file' => 'required|mimes:png,jpg,jpeg,csv,txt,xlx,xls,pdf|max:4048'
            ]);
            $newObj->fill($request->all());
            $name = time().'_'.$request->file->getClientOriginalName();
            $filePath = $request->file('file')->storeAs('uploads', $name, 'public');
            $newObj->file = '/storage/'. $filePath;
            if($newObj->save()) {
                return $newObj;
            } else {
                return false;
            }
        }else{
            $full_class_name = '\App\Models\\'. $class_name;
            $newObj = new $full_class_name();
            $newObj->fill($request->all());
            if($newObj->save()) {
                return $newObj;
            } else {
                return false;
            }
        }


    }

    private function crudUpdate(Request $request, $class_name, $id = null){

        if($request->hasFile('file')) {
            $full_class_name = '\App\Models\\'. $class_name;
            $obj_id = $request->input('id') ?? $id; //TODO or the other way round
            $obj = $full_class_name::find($request->input('id'));

            $request->validate([
                'file' => 'required|mimes:png,jpg,jpeg,csv,txt,xlx,xls,pdf|max:4048'
            ]);
            $obj->fill($request->all());
            $name = time().'_'.$request->file->getClientOriginalName();
            $filePath = $request->file('file')->storeAs('uploads', $name, 'public');
            $obj->file = '/storage/'. $filePath;
            return $obj->save();
        }else {
            $full_class_name = '\App\Models\\'. $class_name;
            $obj_id = $request->input('id') ?? $id; //TODO or the other way round
            $obj = $full_class_name::find($request->input('id'));

            $obj->fill($request->all());
            return $obj->save();
        }
    }

    public function delete(Request $request, $class_name, $id) {
        $full_class_name = '\App\Models\\'. $class_name;
        $obj = $full_class_name::find($request->input('id'));
        $obj->fill($request->all());
//        return $obj->delete();
    }
}
