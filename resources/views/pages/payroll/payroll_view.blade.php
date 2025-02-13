@extends('layouts.backend')

@section('content')
    <?php
    use App\Models\Approval;use Illuminate\Http\Request;
    $notifiable_id = Auth::user()->id;
    $route_id = request()->route('id');
    $payroll_id = $route_id;
    $route_document_type_id = request()->route('document_type_id');
    $base_route = 'gross/' . $route_id . '/' . $route_document_type_id;
    foreach (Auth::user()->unreadNotifications as $notification) {
        if ($notification->data['link'] == $base_route) {
            $notification_id = \App\Models\Notification::Where('notifiable_id', $notifiable_id)->where('data->link', $base_route)->get()->first()->id;
            $notification = auth()->user()->notifications()->find($notification_id);
            if ($notification) {
                $notification->markAsRead();
            }
        }
    }
    ?>
    @if($payroll == null)
        @php
            header("Location: " . URL::to('/404'), true, 302);
            exit();
        @endphp
    @endif
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Payroll
            </div>
            <div>
                <div class="block ">
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter">
                            <tbody>
                            <tr>
                                <th width="30%">Document Number</th>
                                <td>{{$payroll->document_number ?? null }}</td>
                            </tr>
                            <tr>
                                <th width="30%">Payroll Number</th>
                                <td>{{$payroll->payroll_number ?? null}}</td>
                            </tr>
                            <tr>
                                <th>Month</th>
{{--                                <td>{{date('Y-m-d',strtotime($payroll->year.'-'.$payroll->month.'-'.'01'))}}</td>--}}
                                <td>{{date('F',strtotime($payroll->year.'-'.$payroll->month.'-'.'01')).' '.$payroll->year}}</td>
                            </tr>
                            <tr>
                                <th>Created Date</th>
                                <td>{{$payroll->submitted_date}}</td>
                            </tr>
                            <tr>
                                <th width="30%">status</th>
                                @if($payroll->status == 'PENDING')
                                    <td width="70%" class="bold text-capitalize"
                                        style="font-size: 16px!important">
                                        <div
                                            class="badge badge-warning badge-pill">{{ $payroll->status}}</div>
                                    </td>
                                @elseif($payroll->status == 'APPROVED')
                                    <td width="70%" class="bold text-capitalize"
                                        style="font-size: 16px!important">
                                        <div
                                            class="badge badge-primary badge-pill">{{ $payroll->status}}</div>
                                    </td>
                                @elseif($payroll->status == 'REJECTED')
                                    <td width="70%" class="bold text-capitalize"
                                        style="font-size: 16px!important">
                                        <div
                                            class="badge badge-danger badge-pill">{{ $payroll->status}}</div>
                                    </td>
                                @elseif($payroll->status == 'PAID')
                                    <td width="70%" class="bold text-capitalize"
                                        style="font-size: 16px!important">
                                        <div
                                            class="badge badge-primary badge-pill">{{ $payroll->status}}</div>
                                    </td>
                                @elseif($payroll->status == 'COMPLETED')
                                    <td width="70%" class="bold text-capitalize"
                                        style="font-size: 16px!important">
                                        <div
                                            class="badge badge-success badge-pill">{{ $payroll->status}}</div>
                                    </td>
                                @else
                                    <td width="70%" class="bold text-capitalize"
                                        style="font-size: 16px!important">
                                        <div
                                            class="badge badge-secondary badge-pill">{{ $payroll->status }}</div>
                                    </td>
                                @endif
                            </tr>
                            </tbody>
                        </table>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full" data-ordering="false" data-sorting="false">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Name</th>
                                    <th>Basic Salary</th>
                                    <th>Allowance</th>
                                    <th>Gross Pay</th>
                                    <th>Employer Pension</th>
                                    <th>Employee Pension</th>
                                    <th>Taxable</th>
                                    <th>PAYE</th>
                                    <th>WCF</th>
                                    <th>SDL</th>
                                    <th>HESLB</th>
                                    <th>Employer Health</th>
                                    <th>Employee Health</th>
                                    <th>Advance Salary</th>
                                    <th>Total Loan</th>
                                    <th>Loan Deduction</th>
                                    <th>Loan Balance</th>
                                    <th>Adjustment</th>
                                    <th>NET</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php
                                    $start_date = date('Y-m-d',strtotime($payroll->year.'-'.$payroll->month.'-'.'01'));
                                    $end_date = date('Y-m-d',strtotime($payroll->year.'-'.$payroll->month.'-'.'31'));
                                    $sum_total_basic_salary = 0;
                                    $sum_total_advance_salary = 0;
                                    $sum_total_allowance = 0;
                                    $sum_total_gross_pay = 0;
                                    $sum_total_employee_deducted_amount_pension = 0;
                                    $sum_total_employer_deducted_amount_pension = 0;
                                    $sum_total_employee_deducted_amount_health = 0;
                                    $sum_total_employer_deducted_amount_health = 0;
                                    $sum_total_employee_deducted_amount_wcf = 0;
                                    $sum_total_employer_deducted_amount_wcf = 0;
                                    $sum_total_employee_deducted_amount_sdl = 0;
                                    $sum_total_employer_deducted_amount_sdl = 0;
                                    $sum_total_employee_deducted_amount_heslb = 0;
                                    $sum_total_employer_deducted_amount_heslb = 0;
                                    $sum_total_employee_deducted_amount_payee = 0;
                                    $sum_total_employer_deducted_amount_payee = 0;
                                    $sum_total_loan_balance = 0;
                                    $sum_total_current_loan = 0;
                                    $sum_total_loan_deduction = 0;
                                    $sum_total_taxable = 0;
                                    $sum_total_net = 0;
                                    $sum_total_adjustment = 0;
                                @endphp
                                @foreach($payroll_types as $payroll_type)
                                    @php
                                        $payroll_type_id = $payroll_type->id;
                                        $staffs = \App\Models\Staff::getAllStaffSalaryPaid($payroll_id,$payroll_type_id);
                                    @endphp
                                    <tr>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td style="display: none;"></td>
                                        <td colspan="20" class="text-center">{{$payroll_type->name}}'s Payroll</td>
                                    </tr>
                                    @php
                                        $total_basic_salary = 0;
                                        $total_advance_salary = 0;
                                        $total_allowance = 0;
                                        $total_gross_pay = 0;
                                        $total_employee_deducted_amount_pension = 0;
                                        $total_employer_deducted_amount_pension = 0;
                                        $total_employee_deducted_amount_health = 0;
                                        $total_employer_deducted_amount_health = 0;
                                        $total_employee_deducted_amount_wcf = 0;
                                        $total_employer_deducted_amount_wcf = 0;
                                        $total_employee_deducted_amount_sdl = 0;
                                        $total_employer_deducted_amount_sdl = 0;
                                        $total_employee_deducted_amount_heslb = 0;
                                        $total_employer_deducted_amount_heslb = 0;
                                        $total_employee_deducted_amount_payee = 0;
                                        $total_employer_deducted_amount_payee = 0;
                                        $total_loan_balance = 0;
                                        $total_current_loan = 0;
                                        $total_loan_deduction = 0;
                                        $total_taxable = 0;
                                        $total_net = 0;
                                        $total_adjustment = 0;
                                    @endphp
                                    @foreach($staffs as $staff)
                                        @php

                                            $month = date('m');
                                            $staff_id = $staff->staff_id;
                                            $basic_salary = \App\Models\Staff::getStaffSalaryPaid($staff_id,$payroll_id);
                                            $total_basic_salary += $basic_salary;
                                            $staff_salary_id = \App\Models\Staff::getStaffSalaryId($staff_id) ?? 0;
                                            $advance_salary = \App\Models\Staff::getStaffAdvanceSalaryPaid($staff_id,$payroll_id) ?? 0;
                                            $total_advance_salary += $advance_salary;
                                            $adjustment = \App\Models\Staff::getStaffAdjustmentPaid($staff_id,$payroll_id) ?? 0;
                                            $total_adjustment += $adjustment;
                                            $allowance = \App\Models\Staff::getStaffAllowancePaid($staff_id,$payroll_id) ?? 0;
                                            $total_allowance += $allowance;
                                            $gross_pay = \App\Models\Staff::getStaffGrossPayPaid($staff_id,$payroll_id) ?? 0;
                                            $total_gross_pay += $gross_pay;
                                            $employee_deducted_amount_pension = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,2,'employee_deduction_amount') ?? 0;
                                            $total_employee_deducted_amount_pension += $employee_deducted_amount_pension;
                                            $employer_deducted_amount_pension = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,2,'employer_deduction_amount') ?? 0;
                                            $total_employer_deducted_amount_pension += $employer_deducted_amount_pension;
                                            $employee_deducted_amount_health = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,6,'employee_deduction_amount') ?? 0;
                                            $total_employee_deducted_amount_health += $employee_deducted_amount_health;
                                            $employer_deducted_amount_health = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,6,'employer_deduction_amount') ?? 0;
                                            $total_employer_deducted_amount_health += $employer_deducted_amount_health;
                                            $employee_deducted_amount_wcf = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,3,'employee_deduction_amount') ?? 0;
                                            $total_employee_deducted_amount_wcf += $employee_deducted_amount_wcf;
                                            $employer_deducted_amount_wcf = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,3,'employer_deduction_amount') ?? 0;
                                            $total_employer_deducted_amount_wcf += $employer_deducted_amount_wcf;
                                            $employee_deducted_amount_sdl = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,5,'employee_deduction_amount') ?? 0;
                                            $total_employee_deducted_amount_sdl += $employee_deducted_amount_sdl;
                                            $employer_deducted_amount_sdl = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,5,'employer_deduction_amount') ?? 0;
                                            $total_employer_deducted_amount_sdl += $employer_deducted_amount_sdl;
                                            $employee_deducted_amount_heslb = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,4,'employee_deduction_amount') ?? 0;
                                            $total_employee_deducted_amount_heslb += $employee_deducted_amount_heslb;
                                            $employer_deducted_amount_heslb = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,4,'employer_deduction_amount') ?? 0;
                                            $total_employer_deducted_amount_heslb += $employer_deducted_amount_heslb;
                                            $employee_deducted_amount_payee = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,1,'employee_deduction_amount') ?? 0;
                                            $total_employee_deducted_amount_payee += $employee_deducted_amount_payee;
                                            $employer_deducted_amount_payee = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,1,'employer_deduction_amount') ?? 0;
                                            $total_employer_deducted_amount_payee += $employer_deducted_amount_payee;
                                            $loan_balance = \App\Models\Staff::getStaffLoanBalancePaid($staff_id,$payroll_id) ?? 0;
                                            $total_loan_balance += $loan_balance;
                                            $current_loan = \App\Models\Staff::getStaffLoanPaid($staff_id,$payroll_id) ?? 0;
                                            $total_current_loan += $current_loan;
                                            $loan_deduction = \App\Models\Staff::getStaffLoanDeductionPaid($staff_id,$payroll_id) ?? 0;
                                            $total_loan_deduction += $loan_deduction;
                                            $taxable = \App\Models\Staff::getStaffTaxablePaid($staff_id,$payroll_id) ?? 0;
                                            $total_taxable += $taxable;
                                            $net = \App\Models\Staff::getStaffNetPaid($staff_id,$payroll_id) ?? 0;
                                            $total_net += $net;
                                        @endphp
                                        <tr>
                                            <td>{{$loop->iteration}}</td>
                                            <td>{{$staff->name ?? null}}</td>
                                            <td class="text-right">{{number_format($basic_salary)}}</td>
                                            <td class="text-right">{{number_format($allowance)}}</td>
                                            <td class="text-right">{{number_format($gross_pay)}}</td>
                                            <td class="text-right">{{number_format($employer_deducted_amount_pension)}}</td>
                                            <td class="text-right">{{number_format($employee_deducted_amount_pension)}}</td>
                                            <td class="text-right">{{number_format($taxable)}}</td>
                                            <td class="text-right">{{number_format($employee_deducted_amount_payee)}}</td>
                                            <td class="text-right">{{number_format($employer_deducted_amount_wcf)}}</td>
                                            <td class="text-right">{{number_format($employer_deducted_amount_sdl)}}</td>
                                            <td class="text-right">{{number_format($employee_deducted_amount_heslb)}}</td>
                                            <td class="text-right">{{number_format($employer_deducted_amount_health)}}</td>
                                            <td class="text-right">{{number_format($employee_deducted_amount_health)}}</td>
                                            <td class="text-right">{{number_format($advance_salary)}}</td>
                                            <td class="text-right">{{number_format($current_loan)}}</td>
                                            <td class="text-right">{{number_format($loan_deduction)}}</td>
                                            <td class="text-right">{{number_format($loan_balance)}}</td>
                                            <td class="text-right">{{number_format($adjustment)}}</td>
                                            <td class="text-right">{{number_format($net)}}</td>
                                        </tr>
                                    @endforeach
                                    @php
                                        $sum_total_basic_salary += $total_basic_salary;
                                        $sum_total_advance_salary += $total_advance_salary;
                                        $sum_total_allowance += $total_allowance;
                                        $sum_total_gross_pay += $total_gross_pay;
                                        $sum_total_employee_deducted_amount_pension += $total_employee_deducted_amount_pension;
                                        $sum_total_employer_deducted_amount_pension += $total_employer_deducted_amount_pension;
                                        $sum_total_employee_deducted_amount_health += $total_employee_deducted_amount_health;
                                        $sum_total_employer_deducted_amount_health += $total_employer_deducted_amount_health;
                                        $sum_total_employee_deducted_amount_wcf += $total_employee_deducted_amount_wcf;
                                        $sum_total_employer_deducted_amount_wcf += $total_employer_deducted_amount_wcf;
                                        $sum_total_employee_deducted_amount_sdl += $total_employee_deducted_amount_sdl;
                                        $sum_total_employer_deducted_amount_sdl += $total_employer_deducted_amount_sdl;
                                        $sum_total_employee_deducted_amount_heslb += $total_employee_deducted_amount_heslb;
                                        $sum_total_employer_deducted_amount_heslb += $total_employer_deducted_amount_heslb;
                                        $sum_total_employee_deducted_amount_payee += $total_employee_deducted_amount_payee;
                                        $sum_total_employer_deducted_amount_payee += $total_employer_deducted_amount_payee;
                                        $sum_total_loan_balance += $total_loan_balance;
                                        $sum_total_current_loan += $total_current_loan;
                                        $sum_total_loan_deduction += $total_loan_deduction;
                                        $sum_total_taxable += $total_taxable;
                                        $sum_total_adjustment += $total_adjustment;
                                        $sum_total_net += $total_net;
                                    @endphp
                                    <tr>
                                        <th></th>
                                        <th>{{$payroll_type->name}}'s Total</th>
                                        <th class="text-right">{{number_format($total_basic_salary)}}</th>
                                        <th class="text-right">{{number_format($total_allowance)}}</th>
                                        <th class="text-right">{{number_format($total_gross_pay)}}</th>
                                        <th class="text-right">{{number_format($total_employer_deducted_amount_pension)}}</th>
                                        <th class="text-right">{{number_format($total_employee_deducted_amount_pension)}}</th>
                                        <th class="text-right">{{number_format($total_taxable)}}</th>
                                        <th class="text-right">{{number_format($total_employee_deducted_amount_payee)}}</th>
                                        <th class="text-right">{{number_format($total_employer_deducted_amount_wcf)}}</th>
                                        <th class="text-right">{{number_format($total_employer_deducted_amount_sdl)}}</th>
                                        <th class="text-right">{{number_format($total_employee_deducted_amount_heslb)}}</th>
                                        <th class="text-right">{{number_format($total_employer_deducted_amount_health)}}</th>
                                        <th class="text-right">{{number_format($total_employee_deducted_amount_health)}}</th>
                                        <th class="text-right">{{number_format($total_advance_salary)}}</th>
                                        <th class="text-right">{{number_format($total_current_loan)}}</th>
                                        <th class="text-right">{{number_format($total_loan_deduction)}}</th>
                                        <th class="text-right">{{number_format($total_loan_balance)}}</th>
                                        <th class="text-right">{{number_format($total_adjustment)}}</th>
                                        <th class="text-right">{{number_format($total_net)}}</th>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th></th>
                                    <th>All Total</th>
                                    <th class="text-right">{{number_format($sum_total_basic_salary)}}</th>
                                    <th class="text-right">{{number_format($sum_total_allowance)}}</th>
                                    <th class="text-right">{{number_format($sum_total_gross_pay)}}</th>
                                    <th class="text-right">{{number_format($sum_total_employer_deducted_amount_pension)}}</th>
                                    <th class="text-right">{{number_format($sum_total_employee_deducted_amount_pension)}}</th>
                                    <th class="text-right">{{number_format($sum_total_taxable)}}</th>
                                    <th class="text-right">{{number_format($sum_total_employee_deducted_amount_payee)}}</th>
                                    <th class="text-right">{{number_format($sum_total_employer_deducted_amount_wcf)}}</th>
                                    <th class="text-right">{{number_format($sum_total_employer_deducted_amount_sdl)}}</th>
                                    <th class="text-right">{{number_format($sum_total_employee_deducted_amount_heslb)}}</th>
                                    <th class="text-right">{{number_format($sum_total_employer_deducted_amount_health)}}</th>
                                    <th class="text-right">{{number_format($sum_total_employee_deducted_amount_health)}}</th>
                                    <th class="text-right">{{number_format($sum_total_advance_salary)}}</th>
                                    <th class="text-right">{{number_format($sum_total_current_loan)}}</th>
                                    <th class="text-right">{{number_format($sum_total_loan_deduction)}}</th>
                                    <th class="text-right">{{number_format($sum_total_loan_balance)}}</th>
                                    <th class="text-right">{{number_format($sum_total_adjustment)}}</th>
                                    <th class="text-right">{{number_format($sum_total_net)}}</th>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="block">
                    <div class="block-header">
                        <h3 class="block-title text-center">APPROVALS</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter" id="payroll">
                            <thead>
                            @php
                                $payroll_id = $payroll->id;
                                    $approval_document_types_id = 5;
                                    $approvals = \App\Models\ApprovalLevel::getUsersApprovals($approval_document_types_id);

                            @endphp
                            <tr>
                                <td class="text-center">Prepared By</td>
                                @foreach($approvals as $approval)
                                    @php
                                        $group_name = \App\Models\ApprovalLevel::getUserGroupName($approval->id);
                                    @endphp
                                    <td>{{$group_name ?? null}} Approval</td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="text-center">{{$payroll->user->name}} <br/>
                                    <img src="{{ asset($payroll->user->file) }}" alt="" width="120">
                                </td>
                                @foreach($approvals as $approval)
                                    @php
                                        $approved = \App\Models\Approval::getApprovedDocument($approval->id,$approval_document_types_id,$payroll_id);
                                        $approver = \App\Models\User::getUserName($approved['user_id']);
                                        $signature = \App\Models\User::getUserSignature($approved['user_id']);
                                    @endphp
                                    <td class="text-center">{{$approver}} <br/>
                                        @if($signature)
                                            <img src="{{ asset($signature) }}" alt="" width="120">
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            <tr>
                                <td class="text-center">{{$payroll->submitted_date}}</td>
                                @foreach($approvals as $approval)
                                    @php
                                        $approved = \App\Models\Approval::getApprovedDocument($approval->id,$approval_document_types_id,$payroll_id);
                                        $comment = $approved['comments'];
                                    @endphp
                                    <td class="text-center">
                                        @if($approved['created_at'])
                                            <span class='pull-right link no-print text-primary'
                                                  onclick='showComments("{{$comment}}")'><i
                                                    class='fa fa-comment'>&nbsp;</i> Comments </span>
                                            {{$approved['created_at'] ?? ''}}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>

                            </thead>
                        </table>
                    </div>
                </div>
            </div>
            <div>
                <form method="post" action="{{route('hr_settings_approvals')}}" enctype="multipart/form-data">
                    @csrf
                    <div class="block">
                        <div class="block-content">
                            <div class="row">
                                <?php
                                $get_user_group_id = \App\Models\AssignUserGroup::getAssignUserGroup(Auth::user()->id);
                                foreach ($get_user_group_id as $index => $item) {
                                    $arr[] = $item->user_group_id;
                                }
                                ?>

                                @if($nextApproval)
                                    @if($rejected)
                                        <div class="col-md-9">
                                            <span class='pull-right'>This Payroll was rejected <i class='text-light'>Comment: {{$rejected->comments}}</i></span>
                                        </div>
                                        <div class="col-md-3">
                                        </div>
                                    @else
                                        @if(!in_array($nextApproval->user_group_id,$arr))
                                            <div class="col-md-12">
                                                <span class='pull-left'><i
                                                        class='fa fa-clock'>&nbsp;&nbsp;&nbsp;&nbsp;</i> Waiting {{$nextApproval->user_group_name}} for Approval</span>
                                            </div>
                                        @else
                                            <div class="col-md-12">
                                                <input type="hidden" name="status" id="status" value="APPROVED">
                                                <input type="hidden" name="approval_document_types_id"
                                                       id="approval_document_types_id"
                                                       value="{{$nextApproval->document_id}}">
                                                <input type="hidden" name="link" id="link"
                                                       value="payroll/{{$document_id}}/5">
                                                <input type="hidden" name="user_id" id="user_id"
                                                       value="{{Auth::user()->id }}">
                                                <input type="hidden" name="approval_level_id" id="approval_level_id"
                                                       value="{{$nextApproval->order_id ?? null}}">
                                                <input type="hidden" name="user_group_id" id="user_group_id"
                                                       value="{{$nextApproval->user_group_id ?? null}}">
                                                <input type="hidden" name="document_id" id="document_id"
                                                       value="{{$document_id}}">
                                                <input type="hidden" name="document_type_id" id="document_type_id"
                                                       value="5">
                                                <input type="hidden" name="approval_date" id="approval_date"
                                                       value="<?=date('Y-m-d H:i:s')?>">
                                                <input type="hidden" name="route" id="route"
                                                       value="payroll/payroll_administration">
                                                <br/>
                                                <div class="form-group row">
                                                    <label for="example-text-input" class="col-md-2 col-form-label">Comments</label>
                                                    <div class="col-md-10">
                                                        <textarea class="form-control" type="text" id="comments"
                                                                  name="comments" rows="3" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="btn-group pull-right">
                                                        <button type="submit" class="btn btn-alt-primary btn-sm"
                                                                name="approveItem" value="Payroll">Approve now
                                                        </button>
                                                        <button type="submit" class="btn btn-alt-danger btn-sm"
                                                                name="rejectItem" value="Payroll">Reject
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                @elseif($approvalCompleted)
                                    <div class="col-md-10">
                                        <button type="button"
                                                onclick="loadFormModal('net_form', {className: 'Payroll', id:{{$payroll_id}}}, 'NET Salary for {{date('F',strtotime($payroll->year.'-'.$payroll->month.'-'.'01')).' '.$payroll->year}}', 'modal-xl');"
                                                class="btn btn-rounded btn-outline-primary mb-10"><i class="si si-list">&nbsp;</i>NETS
                                        </button>
                                        <button type="button"
                                                onclick="loadFormModal('paye_form', {className: 'Payroll', id:{{$payroll_id}}}, 'PAYE for {{date('F',strtotime($payroll->year.'-'.$payroll->month.'-'.'01')).' '.$payroll->year}}', 'modal-xl');"
                                                class="btn btn-rounded btn-outline-primary mb-10"><i class="si si-list">&nbsp;</i>PAYE
                                        </button>
                                        <button type="button"
                                                onclick="loadFormModal('sdl_form', {className: 'Payroll', id:{{$payroll_id}}}, 'SDL for {{date('F',strtotime($payroll->year.'-'.$payroll->month.'-'.'01')).' '.$payroll->year}}', 'modal-xl');"
                                                class="btn btn-rounded btn-outline-primary mb-10"><i class="si si-list">&nbsp;</i>SDL
                                        </button>
                                        <button type="button"
                                                onclick="loadFormModal('nssf_form', {className: 'Payroll', id:{{$payroll_id}}}, 'NSSF for {{date('F',strtotime($payroll->year.'-'.$payroll->month.'-'.'01')).' '.$payroll->year}}', 'modal-xl');"
                                                class="btn btn-rounded btn-outline-primary mb-10"><i class="si si-list">&nbsp;</i>NSSF
                                        </button>
                                        <button type="button"
                                                onclick="loadFormModal('nhif_form', {className: 'Payroll', id:{{$payroll_id}}}, 'NHIF for {{date('F',strtotime($payroll->year.'-'.$payroll->month.'-'.'01')).' '.$payroll->year}}', 'modal-xl');"
                                                class="btn btn-rounded btn-outline-primary mb-10"><i class="si si-list">&nbsp;</i>NHIF
                                        </button>
                                        <button type="button"
                                                onclick="loadFormModal('wcf_form', {className: 'Payroll', id:{{$payroll_id}}}, 'WCF for {{date('F',strtotime($payroll->year.'-'.$payroll->month.'-'.'01')).' '.$payroll->year}}', 'modal-xl');"
                                                class="btn btn-rounded btn-outline-primary mb-10"><i class="si si-list">&nbsp;</i>WCF
                                        </button>
                                        <button type="button"
                                                onclick="loadFormModal('heslb_form', {className: 'Payroll', id:{{$payroll_id}}}, 'HESLB for {{date('F',strtotime($payroll->year.'-'.$payroll->month.'-'.'01')).' '.$payroll->year}}', 'modal-xl');"
                                                class="btn btn-rounded btn-outline-primary mb-10"><i class="si si-list">&nbsp;</i>HESLB
                                        </button>
                                    </div>
                                    <div class="col-md-2">
                                        <span class='text-primary'><i class='fa fa-check '>&nbsp;&nbsp;&nbsp;</i> Payrolls Approved</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>
            </div>

        </div>

    </div>

@endsection


<script>
    function showComments(comment) {
        swal.fire({
            title: "Comments",
            text: comment,
            // type: "input"
        });
    }

    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>



