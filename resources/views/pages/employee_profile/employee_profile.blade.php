@extends('layouts.backend')

@section('content')

    <style>
        /*body {*/
        /*    !*background: #67B26F;  !* fallback for old browsers *!*!*/
        /*    !*background: -webkit-linear-gradient(to right, #4ca2cd, #67B26F);  !* Chrome 10-25, Safari 5.1-6 *!*!*/
        /*    !*background: linear-gradient(to right, #4ca2cd, #67B26F); !* W3C, IE 10+/ Edge, Firefox 16+, Chrome 26+, Opera 12+, Safari 7+ *!*!*/
        /*    padding: 0;*/
        /*    margin: 0;*/
        /*    font-family: 'Lato', sans-serif;*/
        /*    color: #000;*/
        /*}*/

        .card {
            background: white !important;
            padding: 20px
        }

        .student-profile .card {
            border-radius: 10px;
        }

        .student-profile .card .card-header .profile_img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            margin: 10px auto;
            border: 10px solid #ccc;
            border-radius: 50%;
        }

        .student-profile .card h3 {
            font-size: 20px;
            font-weight: 700;
        }

        .student-profile .card p {
            font-size: 16px;
            color: #000;
        }

        .student-profile .table th,
        .student-profile .table td {
            font-size: 14px;
            padding: 5px 10px;
            color: #000;
        }

        .d-flex {
            gap: 20px !important;
        }
    </style>
    <div class="main-container">
        <div class="row">
            <div class="col-xl-10 mx-auto">
                <h6 class="mb-0 text-uppercase">Employee Details Profile</h6>
                <hr/>
                <hr/>
                <div class="card shadow-sm border rounded">
                    <div class="card-body">
                        <div class="p-4 border rounded">
                            <form class="row g-3" action="" method="POST">
                                @csrf
                                <div class="class col-md-3">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">Start Date</span>
                                        </div>
                                        <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-01-01')}}">
                                    </div>
                                </div>
                                <div class="class col-md-3">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon2">End Date</span>
                                        </div>
                                        <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <select class="select2 form-control" id="staff_id" name="staff_id" required>
                                        <option selected disabled value="">Choose...</option>
                                        @foreach($staffs as $staff)
                                            <option value="{{ $staff->id }}"> {{ $staff->name }} </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">Please select a valid state.</div>
                                </div>
                                <div class="col-2">
                                    <button class="btn btn-success" type="submit"><i class="si si-search"></i>Search
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
                <div class="student-profile py-4">
                    <div class="container">
                        <div class="row mb-3">
                            <div class="col-12 col-lg-5 d-flex">
                                <div class="card shadow-sm radius-10 w-100">
                                    <div class="card-header bg-transparent text-center">
                                        <?php
                                        $profile = $employee->profile ?? 'media/avatars/avatar15.jpg'
                                        ?>
                                        <img class="profile_img" src="{{ asset("$profile")}}"
                                             alt="student dp">
                                        <h3>{{$employee->name}}</h3>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group">
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <strong class="pr-1">Employee No:</strong> <span
                                                    class="badge bg-primary rounded-pill text-white">HRM/{{$employee->employee_number}}</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <strong class="pr-1">Basic Salary:</strong> <span
                                                    class="badge bg-success rounded-pill  text-white">{{number_format($basic_salary)}}</span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <strong class="pr-1">Loan Balance:</strong><span
                                                    class="badge bg-danger rounded-pill  text-white">{{number_format($loan_balance)}}</span>
                                            </li>

                                        </ul>
                                    </div>
                                </div>

                            </div>
                            <div class="col-12 col-lg-7 d-flex">
                                <div class="card shadow-sm radius-10 w-100">
                                    <div class="card-header bg-transparent border-0">
                                        <h3 class="mb-0"><i class="far fa-clone pr-1"></i>General Information</h3>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-bordered">
                                            <tr>
                                                <th width="30%">Employed Date</th>
                                                <td width="2%">:</td>
                                                <td>{{ $employee->created_at  ?? ''}}</td>
                                            </tr>
                                            <tr>
                                                <th width="30%">Address</th>
                                                <td width="2%">:</td>
                                                <td>{{ $employee->address  ?? ''}}</td>
                                            </tr>
                                            <tr>
                                                <th width="30%">Designation</th>
                                                <td width="2%">:</td>
                                                <td>{{ $employee->designation  ?? ''}}</td>
                                            </tr>
                                            <tr>
                                                <th width="30%">Gender</th>
                                                <td width="2%">:</td>
                                                <td>{{ $employee->gender }}</td>
                                            </tr>
                                            <tr>
                                                <th width="30%">Birth of Date</th>
                                                <td width="2%">:</td>
                                                <td>{{ $employee->dob  ?? ''}}</td>
                                            </tr>
                                            <tr>
                                                <th width="30%">Mobile Number</th>
                                                <td width="2%">:</td>
                                                <td>{{ $employee->phone_number  ?? ''}}</td>
                                            </tr>
                                            <tr>
                                                <th width="30%">Department</th>
                                                <td width="2%">:</td>
                                                <td>{{ $employee->department->name  ?? ''}}</td>
                                            </tr>
                                            <tr>
                                                <th width="30%">System</th>
                                                <td width="2%">:</td>
                                                <td>{{ $employee->system->name  ?? ''}}</td>
                                            </tr>
                                            <tr>
                                                <th width="30%">NIDA</th>
                                                <td width="2%">:</td>
                                                <td>{{ $employee->national_id  ?? ''}}</td>
                                            </tr>
                                            <tr>
                                                <th width="30%">TIN</th>
                                                <td width="2%">:</td>
                                                <td>{{ $employee->tin  ?? ''}}</td>
                                            </tr>
                                            <tr>
                                                <th width="30%">Account Number</th>
                                                <td width="2%">:</td>
                                                <td>{{ $account_number }}</td>
                                            </tr>
                                            <tr>
                                                <th width="30%">Employee Status</th>
                                                <td width="2%">:</td>
                                                <td>
                                                    @if($employee->status == 'ACTIVE')
                                                        <span class="badge bg-success">ACTIVE</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i class="text-right">{{$employee->updated_at}}</i>
                                                    @elseif($employee->status == 'DORMANT')
                                                        <span class="badge bg-warning">DORMANT</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i class="text-right">{{$employee->updated_at}}</i>
                                                    @else
                                                        <span class="badge bg-danger">INACTIVE</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<i class="text-right">{{$employee->updated_at}}</i>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div><!--end row-->
                        <div class="row mb-3">
                            {{--                            <div class="col-12 col-lg-4 d-flex">--}}
                            {{--                                <div class="card shadow-sm">--}}
                            {{--                                    <h3>Monthly Attendance</h3>--}}
                            {{--                                    <div>--}}
                            {{--                                    <canvas id="doughnutChart"></canvas>--}}
                            {{--                                </div>--}}
                            {{--                                </div>--}}
                            {{--                            </div>--}}
                            <div class="col-12 col-lg-12 d-flex">
                                <div class="row row-cols-1 row-cols-lg-4 row-cols-xl-4 row-cols-xxl-4">
                                    <div class="col">
                                        <div class="card shadow-sm overflow-hidden radius-10">
                                            <div class="card-body">
                                                <div
                                                    class="d-flex align-items-stretch justify-content-between overflow-hidden">
                                                    <div class="w-50">
                                                        <p>Gross</p>
                                                        <h4 class="">{{number_format($gross_pay)}}</h4>
                                                    </div>
                                                    <div class="w-50">
                                                        <p class="mb-3 float-end text-success">+ 16% <i
                                                                class="bi bi-arrow-up"></i></p>
                                                        <div id="chart1"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="card overflow-hidden radius-10">
                                            <div class="card-body">
                                                <div
                                                    class="d-flex align-items-stretch justify-content-between overflow-hidden">
                                                    <div class="w-50">
                                                        <p>Deduction</p>
                                                        <h4 class="">{{number_format($total_deduction)}}</h4>
                                                    </div>
                                                    <div class="w-50">
                                                        <p class="mb-3 float-end text-danger">- 3.4% <i
                                                                class="bi bi-arrow-down"></i></p>
                                                        <div id="chart2"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="card overflow-hidden radius-10">
                                            <div class="card-body">
                                                <div
                                                    class="d-flex align-items-stretch justify-content-between overflow-hidden">
                                                    <div class="w-50">
                                                        <p>Allowances</p>
                                                        <h4 class="">{{number_format($allowance)}}</h4>
                                                    </div>
                                                    <div class="w-50">
                                                        <p class="mb-3 float-end text-success">+ 24% <i
                                                                class="bi bi-arrow-up"></i></p>
                                                        <div id="chart3"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="card overflow-hidden radius-10">
                                            <div class="card-body">
                                                <div
                                                    class="d-flex align-items-stretch justify-content-between overflow-hidden">
                                                    <div class="w-50">
                                                        <p>Net Pay</p>
                                                        <h4 class="">{{number_format($net)}}</h4>
                                                    </div>
                                                    <div class="w-50">
                                                        <p class="mb-3 float-end text-success">+ 8.2% <i
                                                                class="bi bi-arrow-up"></i></p>
                                                        <div id="chart4"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-12">
                                <div class="row">
                                    <div class="col-12 col-lg-5 d-flex">
                                        <div class="card radius-10 w-100">
                                            <div class="card-body">
                                                <h3>Loan History</h3>
                                                <table class="table table-bordered mb-0">
                                                    <thead>
                                                    <tr>
                                                        <th scope="col">#</th>
                                                        <th scope="col">Date</th>
                                                        <th scope="col">Deduct</th>
                                                        <th scope="col">Amount</th>
{{--                                                        <th scope="col">Status</th>--}}
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @php
                                                    $total_loan = 0;
                                                    @endphp
                                                    @foreach($loan_histories as $loan_history)
                                                        @php
                                                            $total_loan += $loan_history->amount;
                                                        @endphp
                                                    <tr>
                                                        <th scope="row">{{$loop->iteration}}</th>
                                                        <td>{{$loan_history->date}}</td>
                                                        <td class="text-right">{{number_format($loan_history->deduction)}}</td>
                                                        <td class="text-right">{{number_format($loan_history->amount)}}</td>
                                                    </tr>
                                                    @endforeach
                                                    <tr>
                                                        <td colspan="3">Total Loan</td>
                                                        <td class="text-right">{{number_format($total_loan)}}</td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-7 d-flex">
                                        <div class="card radius-10 w-100">
                                            <div class="card-body">
                                                <h3>Advance Salaries History</h3>
                                                <table class="table table-bordered mb-0">
                                                    <thead>
                                                    <tr>
                                                        <th scope="col">#</th>
                                                        <th scope="col">Date</th>
                                                        <th scope="col">Description</th>
                                                        <th scope="col">Amount</th>
                                                        {{--                                                        <th scope="col">Status</th>--}}
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @php
                                                        $total_advance_salary = 0;
                                                    @endphp
                                                    @foreach($advance_salaries as $advance_salary)
                                                        @php
                                                            $total_advance_salary += $advance_salary->amount;
                                                        @endphp
                                                        <tr>
                                                            <th scope="row">{{$loop->iteration}}</th>
                                                            <td>{{$advance_salary->date}}</td>
                                                            <td class="text-small">{{$advance_salary->description}}</td>
                                                            <td class="text-right">{{number_format($advance_salary->amount)}}</td>
                                                        </tr>
                                                    @endforeach
                                                    <tr>
                                                        <td colspan="3">Total Advance Salary</td>
                                                        <td class="text-right">{{number_format($total_advance_salary)}}</td>
                                                    </tr>
                                                    </tbody>
                                                </table>

                                            </div>
                                        </div>
                                    </div>
                                </div><!--end row-->
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-12">
                                <div class="row">
                                    <div class="col-12 col-lg-12 d-flex">
                                        <div class="card radius-10 w-100">
                                            <div class="card-body">
                                                <h3>Payroll History</h3>
                                                <div class="table-responsive">
                                                <table class="table table-bordered mb-0">
                                                    <thead>
                                                    <tr>
                                                        <th scope="col">#</th>
                                                        <th>Date</th>
                                                        <th>Salary</th>
                                                        <th>Allowance</th>
                                                        <th>Gross</th>
                                                        <th>NSSF</th>
                                                        <th>PAYE</th>
                                                        <th>Advance</th>
                                                        <th>Loan</th>
                                                        <th>Deduction</th>
                                                        <th>Balance</th>
                                                        <th>Net</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
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
                                                    @foreach($payrolls as $payroll)
                                                    @php
                                                        $payroll_id = $payroll->id;

                                                            $month = date('m');

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
                                                            <th scope="row">{{$loop->iteration}}</th>
                                                            <td width="30%">{{ date('F',strtotime("01-$payroll->month-$payroll->year")).' '.$payroll->year}}</td>
                                                            <td class="text-right">{{number_format($basic_salary)}}</td>
                                                            <td class="text-right">{{number_format($allowance)}}</td>
                                                            <td class="text-right">{{number_format($gross_pay)}}</td>
                                                            <td class="text-right">{{number_format($employee_deducted_amount_pension)}}</td>
                                                            <td class="text-right">{{number_format($employee_deducted_amount_payee)}}</td>
                                                            <td class="text-right">{{number_format($advance_salary)}}</td>
                                                            <td class="text-right">{{number_format($current_loan)}}</td>
                                                            <td class="text-right">{{number_format($loan_deduction)}}</td>
                                                            <td class="text-right">{{number_format($loan_balance)}}</td>
                                                            <td class="text-right">{{number_format($net)}}</td>
                                                            <td>
                                                                <a href="{{route("employee_salary_slip",['staff_id'=>$staff_id,'month'=>$payroll->month,'year'=>$payroll->year])}}" class="btn btn-rounded btn-outline-primary mb-10"><i class="si si-list">&nbsp;</i>Slip
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                    </tbody>
                                                    <tfoot>

                                                    <tr>
                                                        <th colspan="2">Total</th>
                                                        <th class="text-right">{{number_format($total_basic_salary)}}</th>
                                                        <th class="text-right">{{number_format($total_allowance)}}</th>
                                                        <th class="text-right">{{number_format($total_gross_pay)}}</th>
                                                        <th class="text-right">{{number_format($total_employee_deducted_amount_pension)}}</th>
                                                        <th class="text-right">{{number_format($total_employee_deducted_amount_payee)}}</th>
                                                        <th class="text-right">{{number_format($total_advance_salary)}}</th>
                                                        <th class="text-right">{{number_format($total_current_loan)}}</th>
                                                        <th class="text-right">{{number_format($total_loan_deduction)}}</th>
                                                        <th class="text-right">{{number_format($total_loan_balance)}}</th>
                                                        <th class="text-right">{{number_format($total_net)}}</th>
                                                        <th></th>
                                                    </tr>
                                                    </tfoot>
                                                </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div><!--end row-->
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-12">
                                <div class="row">
                                    <div class="col-12 col-lg-6 d-flex">
                                        <div class="card radius-10 w-100">
                                            <div class="card-body">
                                                <h3>Assest & Benefits History</h3>
                                                <table class="table table-bordered mb-0">
                                                    <thead>
                                                    <tr>
                                                        <th scope="col">#</th>
                                                        <th scope="col">Name</th>
                                                        <th scope="col">Description</th>
                                                        <th scope="col">Asset</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @foreach($assets as $asset)
                                                        <tr>
                                                            <th scope="row">{{$loop->iteration}}</th>
                                                            <td>{{$asset->asset_proper}}</td>
                                                            <td>{{$asset->description}}</td>
                                                            <td>{{$asset->asset_name}}</td>
                                                        </tr>
                                                    @endforeach

                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                </div><!--end row-->
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection


