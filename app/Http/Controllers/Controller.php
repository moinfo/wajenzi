<?php

namespace App\Http\Controllers;

use App\Classes\Utility;
use App\Models\Approval;
use App\Models\AssignUserGroup;
use App\Models\Notification;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\SystemActionNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

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

    public function notify_toast($text, $type = 'success') {
        $toast_session_notifications = session('toast_notifications') ?? [];
        array_push($toast_session_notifications, ['text' => $text, 'type' => $type]);
        session(['toast_notifications' => $toast_session_notifications]);

    }


    public function handleCrud(Request $request, $class_name, $id = null) {
//        dd($class_name);
        if($request->isMethod('POST') || $request->isMethod('PUT')) {
            if($request->has('addItem')) {
                if($this->crudAdd($request, $class_name)) {
                    if ($class_name == 'BankReconciliation'){
                        $supplier_id = $request->input('supplier_id');
                        $efd_id = $request->input('efd_id');
                        $date = $request->input('date');
                        $debit = Utility::strip_commas($request->input('debit'));
                        $description = $request->input('description');
                        $reference = $request->input('reference');
                        $payment_type = $request->input('payment_type');
                        $is_whitestar = Supplier::isWhitestar($supplier_id);
                        if($efd_id != 16){
                            if ($is_whitestar && $supplier_id != 50){
                                DB::table('bank_reconciliations')->insert([
                                    ['supplier_id' => 50, 'efd_id' => 16, 'date' => $date, 'reference' => $reference.'Auto' , 'description' => $description, 'debit' => $debit, 'payment_type' => $payment_type],
                                ]);
                            }
                        }

                    }
                    $this->notify($class_name .'Added Successfully', 'Added!', 'success');
                    if($request->document_id != null){
                        if(Approval::getNextApproval($request->document_id,$request->document_type_id)) {
                            $next_user_group_id = Approval::getNextApproval($request->document_id,$request->document_type_id)->user_group_id;
                            $next_user_id = AssignUserGroup::getUserId($next_user_group_id)->user_id;
                            $user = User::find($next_user_id);

                            $details = [
                                'staff_id' => $next_user_id,
                                'title' => $class_name. ' '. 'Waiting for Approval',
                                'body' => 'A new '.$class_name.' '.$request->document_number.' has been created and submitted. You are required to review and approve the created '. $class_name,
                                'link' => $request->link,
                                'document_id' => $request->document_id,
                                'document_type_id' => $request->document_type_id
                            ];
                            $user->notify(new \App\Notifications\ApprovalNotification($details));

                            event(new \App\Events\Approved($details));
                        }
                    }
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
                'file' => 'required|mimes:png,jpg,jpeg,csv,txt,xlx,xls,xlsx,doc,docx,pdf|max:4048',
            ]);

            // Check for amount_formatted and convert to amount if exists
            if ($request->has('amount_formatted') && !empty($request->input('amount_formatted'))) {
                $amount = Utility::strip_commas($request->input('amount_formatted'));
            } else {
                $amount = Utility::strip_commas($request->input('amount'));
            }

            $request->request->add([
                'amount' => $amount,
                'credit' => Utility::strip_commas($request->input('credit')),
                'debit' => Utility::strip_commas($request->input('debit')),
                'net' => Utility::strip_commas($request->input('net')),
                'tax' => Utility::strip_commas($request->input('tax')),
                'turn_over' => Utility::strip_commas($request->input('turn_over')),
                'total_amount' => Utility::strip_commas($request->input('total_amount')),
                'amount_vat_exc' => Utility::strip_commas($request->input('amount_vat_exc')),
                'vat_amount' => Utility::strip_commas($request->input('vat_amount')),
                'deduction' => Utility::strip_commas($request->input('deduction')),
                'price' => Utility::strip_commas($request->input('price')),
//                'password' => bcrypt($request->input('password')),
            ]);
            $newObj->fill($request->all());
            $name = time().'_'.$request->file->getClientOriginalName();
            $filePath = $request->file('file')->storeAs('uploads', $name, 'public');
            $newObj->file = '/storage/'. $filePath;
            // Handle contract file if present
            if($request->hasFile('contract')) {
                $contractName = time().'_contract_'.$request->contract->getClientOriginalName();
                $contractPath = $request->file('contract')->storeAs('uploads/contracts', $contractName, 'public');
                $newObj->contract = '/storage/'. $contractPath;
            }
            // dd($newObj);
            if($newObj->save()) {
                return $newObj;
            } else {
                return false;
            }
        }else{
            $full_class_name = '\App\Models\\'. $class_name;
            $newObj = new $full_class_name();

            // Check for amount_formatted and convert to amount if exists
            if ($request->has('amount_formatted') && !empty($request->input('amount_formatted'))) {
                $amount = Utility::strip_commas($request->input('amount_formatted'));
            } else {
                $amount = Utility::strip_commas($request->input('amount'));
            }

            $request->request->add([
                'amount' => $amount,
                'credit' => Utility::strip_commas($request->input('credit')),
                'debit' => Utility::strip_commas($request->input('debit')),
                'net' => Utility::strip_commas($request->input('net')),
                'tax' => Utility::strip_commas($request->input('tax')),
                'turn_over' => Utility::strip_commas($request->input('turn_over')),
                'total_amount' => Utility::strip_commas($request->input('total_amount')),
                'amount_vat_exc' => Utility::strip_commas($request->input('amount_vat_exc')),
                'vat_amount' => Utility::strip_commas($request->input('vat_amount')),
                'deduction' => Utility::strip_commas($request->input('deduction')),
                'price' => Utility::strip_commas($request->input('price')),
//                'password' => bcrypt($request->input('password')),
            ]);
            $newObj->fill($request->all());
            // Handle contract file if present
            if($request->hasFile('contract')) {
                $contractName = time().'_contract_'.$request->contract->getClientOriginalName();
                $contractPath = $request->file('contract')->storeAs('uploads/contracts', $contractName, 'public');
                $newObj->contract = '/storage/'. $contractPath;
            }
            if($newObj->save()) {
                return $newObj;
            } else {
                return false;
            }
        }
    }
    private function crudUpdate(Request $request, $class_name, $id = null){
        if($request->file()) {
            if($request->input('profile_image')) {
                $full_class_name = '\App\Models\\' . ($class_name ?? $request->updateItem);
                $obj_id = $request->input('id') ?? $id; //TODO or the other way round
                $obj = $full_class_name::find($request->input('id'));

                $request->validate([
                    'profile' => 'required|mimes:png,jpg,jpeg,csv,txt,xlx,xls,xlsx,doc,docx,pdf|max:4048'
                ]);

                // Check for amount_formatted and convert to amount if exists
                if ($request->has('amount_formatted') && !empty($request->input('amount_formatted'))) {
                    $amount = Utility::strip_commas($request->input('amount_formatted'));
                } else {
                    $amount = Utility::strip_commas($request->input('amount'));
                }

                $request->request->add([
                    'amount' => $amount,
                    'credit' => Utility::strip_commas($request->input('credit')),
                    'debit' => Utility::strip_commas($request->input('debit')),
                    'net' => Utility::strip_commas($request->input('net')),
                    'tax' => Utility::strip_commas($request->input('tax')),
                    'turn_over' => Utility::strip_commas($request->input('turn_over')),
                    'total_amount' => Utility::strip_commas($request->input('total_amount')),
                    'amount_vat_exc' => Utility::strip_commas($request->input('amount_vat_exc')),
                    'vat_amount' => Utility::strip_commas($request->input('vat_amount')),
                    'deduction' => Utility::strip_commas($request->input('deduction')),
                    'price' => Utility::strip_commas($request->input('price')),
//                'password' => bcrypt($request->input('password')),
                ]);
                $obj->fill($request->all());
                $name = time() . '_' . $request->profile->getClientOriginalName();
                $filePath = $request->file('profile')->storeAs('uploads', $name, 'public');
                $obj->profile = '/storage/'. $filePath;
                // Handle contract file if present
                if($request->hasFile('contract')) {
                    $contractName = time().'_contract_'.$request->contract->getClientOriginalName();
                    $contractPath = $request->file('contract')->storeAs('uploads/contracts', $contractName, 'public');
                    $obj->contract = '/storage/'. $contractPath;
                }
                return $obj->save();
            }else if($request->hasFile('file')){
                $full_class_name = '\App\Models\\'. ($class_name ?? $request->updateItem);
                $obj_id = $request->input('id') ?? $id; //TODO or the other way round
                $obj = $full_class_name::find($request->input('id'));

                $request->validate([
                    'file' => 'required|mimes:png,jpg,jpeg,csv,txt,xlx,xls,xlsx,doc,docx,pdf|max:4048'
                ]);

                // Check for amount_formatted and convert to amount if exists
                if ($request->has('amount_formatted') && !empty($request->input('amount_formatted'))) {
                    $amount = Utility::strip_commas($request->input('amount_formatted'));
                } else {
                    $amount = Utility::strip_commas($request->input('amount'));
                }

                $request->request->add([
                    'amount' => $amount,
                    'credit' => Utility::strip_commas($request->input('credit')),
                    'debit' => Utility::strip_commas($request->input('debit')),
                    'net' => Utility::strip_commas($request->input('net')),
                    'tax' => Utility::strip_commas($request->input('tax')),
                    'turn_over' => Utility::strip_commas($request->input('turn_over')),
                    'total_amount' => Utility::strip_commas($request->input('total_amount')),
                    'amount_vat_exc' => Utility::strip_commas($request->input('amount_vat_exc')),
                    'vat_amount' => Utility::strip_commas($request->input('vat_amount')),
                    'deduction' => Utility::strip_commas($request->input('deduction')),
                    'price' => Utility::strip_commas($request->input('price')),
//                'password' => bcrypt($request->input('password')),
                ]);
                $obj->fill($request->all());
                $name = time().'_'.$request->file->getClientOriginalName();
                $filePath = $request->file('file')->storeAs('uploads', $name, 'public');
                $obj->file = '/storage/'. $filePath;
                // Handle contract file if present
                if($request->hasFile('contract')) {
                    $contractName = time().'_contract_'.$request->contract->getClientOriginalName();
                    $contractPath = $request->file('contract')->storeAs('uploads/contracts', $contractName, 'public');
                    $obj->contract = '/storage/'. $contractPath;
                }
                return $obj->save();
            }else if($request->hasFile('contract')){
                // Contract-only upload (no signature file)
                $full_class_name = '\App\Models\\'. ($class_name ?? $request->updateItem);
                $obj = $full_class_name::find($request->input('id') ?? $id);

                // Check for amount_formatted and convert to amount if exists
                if ($request->has('amount_formatted') && !empty($request->input('amount_formatted'))) {
                    $amount = Utility::strip_commas($request->input('amount_formatted'));
                } else {
                    $amount = Utility::strip_commas($request->input('amount'));
                }

                $request->request->add([
                    'amount' => $amount,
                    'credit' => Utility::strip_commas($request->input('credit')),
                    'debit' => Utility::strip_commas($request->input('debit')),
                    'net' => Utility::strip_commas($request->input('net')),
                    'tax' => Utility::strip_commas($request->input('tax')),
                    'turn_over' => Utility::strip_commas($request->input('turn_over')),
                    'total_amount' => Utility::strip_commas($request->input('total_amount')),
                    'amount_vat_exc' => Utility::strip_commas($request->input('amount_vat_exc')),
                    'vat_amount' => Utility::strip_commas($request->input('vat_amount')),
                    'deduction' => Utility::strip_commas($request->input('deduction')),
                    'price' => Utility::strip_commas($request->input('price')),
                ]);
                $obj->fill($request->all());
                $contractName = time().'_contract_'.$request->contract->getClientOriginalName();
                $contractPath = $request->file('contract')->storeAs('uploads/contracts', $contractName, 'public');
                $obj->contract = '/storage/'. $contractPath;
                return $obj->save();
            }
        } else {
            $full_class_name = '\App\Models\\'. ($class_name ?? $request->updateItem);
            $obj_id = $request->input('id') ?? $id; //TODO or the other way round
            $obj = $full_class_name::find($request->input('id'));

            // Check for amount_formatted and convert to amount if exists
            if ($request->has('amount_formatted') && !empty($request->input('amount_formatted'))) {
                $amount = Utility::strip_commas($request->input('amount_formatted'));
            } else {
                $amount = Utility::strip_commas($request->input('amount'));
            }

            $request->request->add([
                'amount' => $amount,
                'credit' => Utility::strip_commas($request->input('credit')),
                'debit' => Utility::strip_commas($request->input('debit')),
                'net' => Utility::strip_commas($request->input('net')),
                'tax' => Utility::strip_commas($request->input('tax')),
                'turn_over' => Utility::strip_commas($request->input('turn_over')),
                'total_amount' => Utility::strip_commas($request->input('total_amount')),
                'amount_vat_exc' => Utility::strip_commas($request->input('amount_vat_exc')),
                'vat_amount' => Utility::strip_commas($request->input('vat_amount')),
                'deduction' => Utility::strip_commas($request->input('deduction')),
                'price' => Utility::strip_commas($request->input('price')),
//                'password' => bcrypt($request->input('password')),
            ]);
            $obj->fill($request->all());
            // Handle contract file if present
            if($request->hasFile('contract')) {
                $contractName = time().'_contract_'.$request->contract->getClientOriginalName();
                $contractPath = $request->file('contract')->storeAs('uploads/contracts', $contractName, 'public');
                $obj->contract = '/storage/'. $contractPath;
            }
            return $obj->save();
        }
    }

    /**
     * Send system notification (database + email) to one or many users.
     */
    protected function sendNotification($users, string $title, string $body, string $link, ?int $documentId = null): void
    {
        $actionBy = auth()->user()->name ?? 'System';

        if ($users instanceof \Illuminate\Database\Eloquent\Model) {
            $users = collect([$users]);
        }

        foreach ($users as $user) {
            // Database notification first
            try {
                $user->notify(
                    (new SystemActionNotification($title, $body, $link, $actionBy, $documentId))->onlyDatabase()
                );
            } catch (\Exception $e) {
                \Log::warning("Failed to save notification for user {$user->id}: " . $e->getMessage());
            }

            // Email separately so it doesn't block database
            try {
                $user->notify(
                    (new SystemActionNotification($title, $body, $link, $actionBy, $documentId))->onlyMail()
                );
            } catch (\Exception $e) {
                \Log::warning("Failed to send notification email to {$user->email}: " . $e->getMessage());
            }
        }
    }

    public function delete(Request $request, $class_name, $id) {
        $full_class_name = '\App\Models\\'. $class_name;
        $obj = $full_class_name::find($request->input('id'));
        $obj->fill($request->all());
//        return $obj->delete();
    }
}
