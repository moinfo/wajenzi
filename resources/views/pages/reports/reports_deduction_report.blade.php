@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Reports
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                @include('components.headed_paper')
                                <br/>
                                <div class="class card-box">
                                    <form  name="gross_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Start Date</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-01')}}">
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
                                            <div class="class col-md-2">
                                                <div>
                                                    <button type="submit" name="submit"  class="btn btn-sm btn-primary">Show</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="block-header text-center">
                                    <h3 class="block-title">Deduction Report</h3>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Name</th>
                                    <th>Basic Salary</th>
                                    <th>Allowance</th>
                                    <th>Gross Pay</th>
                                    <th>Employer Pension</th>
                                    <th>Employee Pension</th>
                                    <th>Employer Health</th>
                                    <th>Employee Health</th>
                                    <th>Taxable</th>
                                    <th>PAYE</th>
                                    <th>WCF</th>
                                    <th>SDL</th>
                                    <th>HESLB</th>
                                    <th>Advance Salary</th>
                                    <th>Loan</th>
                                    <th>NET</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($staffs as $staff)
                                    <?php
                                        $start_date = date('Y-m-01');
                                        $end_date = date('Y-m-t');
                                        $staff_id = $staff->id;
                                        $basic_salary = \App\Models\StaffSalary::Where('staff_id',$staff_id)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('staff_id')->get()->first()['total_amount'] ?? 0;
                                        $advance_salary = \App\Models\AdvanceSalary::Where('staff_id',$staff_id)->WhereBetween('date',[$start_date,$end_date])->select([DB::raw("SUM(amount) as total_amount")])->groupBy('staff_id')->get()->first()['total_amount'] ?? 0;
                                        $allowance = \App\Models\AllowanceSubscription::Where('staff_id',$staff_id)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('staff_id')->get()->first()['total_amount'] ?? 0;
                                        $gross_pay = $basic_salary + $allowance;
                                        $pension = \App\Models\DeductionSubscription::select([DB::raw("deduction_settings.*, deductions.nature")])->join('deduction_settings','deduction_settings.deduction_id', '=', 'deduction_subscriptions.deduction_id')->join('deductions','deductions.id','=','deduction_settings.deduction_id')->Where('deductions.abbreviation','NSSF')->Where('deduction_subscriptions.staff_id',$staff_id)->get()->first();
                                        $health = \App\Models\DeductionSubscription::select([DB::raw("deduction_settings.*, deductions.nature")])->join('deduction_settings','deduction_settings.deduction_id', '=', 'deduction_subscriptions.deduction_id')->join('deductions','deductions.id','=','deduction_settings.deduction_id')->Where('deductions.abbreviation','NHIF')->Where('deduction_subscriptions.staff_id',$staff_id)->get()->first();
                                        $payes = \App\Models\DeductionSubscription::select([DB::raw("deduction_settings.*, deductions.nature")])->join('deduction_settings','deduction_settings.deduction_id', '=', 'deduction_subscriptions.deduction_id')->join('deductions','deductions.id','=','deduction_settings.deduction_id')->Where('deductions.abbreviation','PAYE')->Where('deduction_subscriptions.staff_id',$staff_id)->get();
                                        $wcf = \App\Models\DeductionSubscription::select([DB::raw("deduction_settings.*, deductions.nature")])->join('deduction_settings','deduction_settings.deduction_id', '=', 'deduction_subscriptions.deduction_id')->join('deductions','deductions.id','=','deduction_settings.deduction_id')->Where('deductions.abbreviation','WCF')->Where('deduction_subscriptions.staff_id',$staff_id)->get()->first();
                                        $sdl = \App\Models\DeductionSubscription::select([DB::raw("deduction_settings.*, deductions.nature")])->join('deduction_settings','deduction_settings.deduction_id', '=', 'deduction_subscriptions.deduction_id')->join('deductions','deductions.id','=','deduction_settings.deduction_id')->Where('deductions.abbreviation','SDL')->Where('deduction_subscriptions.staff_id',$staff_id)->get()->first();
                                        $heslb = \App\Models\DeductionSubscription::select([DB::raw("deduction_settings.*, deductions.nature")])->join('deduction_settings','deduction_settings.deduction_id', '=', 'deduction_subscriptions.deduction_id')->join('deductions','deductions.id','=','deduction_settings.deduction_id')->Where('deductions.abbreviation','HESLB')->Where('deduction_subscriptions.staff_id',$staff_id)->get()->first();

                                        if($pension['nature'] == 'GROSS'){
                                            $employer_pension_amount = $gross_pay * ($pension['employer_percentage']/100);
                                            $employee_pension_amount = $gross_pay * ($pension['employee_percentage']/100);
                                        }else{
                                            $employer_pension_amount = $basic_salary * ($pension['employer_percentage']/100);
                                            $employee_pension_amount = $basic_salary * ($pension['employee_percentage']/100);
                                        }

                                        if($health['nature'] == 'GROSS'){
                                            $employer_health_amount = $gross_pay * ($health['employer_percentage']/100);
                                            $employee_health_amount = $gross_pay * ($health['employee_percentage']/100);
                                        }else{
                                            $employer_health_amount = $basic_salary * ($health['employer_percentage']/100);
                                            $employee_health_amount = $basic_salary * ($health['employee_percentage']/100);
                                        }
                                    $taxable = $gross_pay - ($employee_pension_amount+$employee_health_amount);

                                        if($taxable >= 0 && $taxable < 270000){
                                            $employee_percentage = 0;
                                            $additional_amount = 0;
                                            $maximum_amount = 270000;
                                        }elseif($taxable >= 270000 && $taxable < 520000){
                                            $employee_percentage = 0.08;
                                            $additional_amount = 0;
                                            $maximum_amount = 520000;
                                        }elseif($taxable >= 520000 && $taxable < 760000){
                                            $employee_percentage = 0.2;
                                            $additional_amount = 20000;
                                            $maximum_amount = 760000;
                                        }elseif($taxable >= 760000 && $taxable < 1000000){
                                            $employee_percentage = 0.25;
                                            $additional_amount = 68000;
                                            $maximum_amount = 1000000;
                                        }elseif($taxable >= 1000000){
                                            $employee_percentage = 0.30;
                                            $additional_amount = 128000;
                                            $maximum_amount = 1000000;
                                        }

                                    $paye_amount = ($additional_amount + $employee_percentage* ($maximum_amount - $taxable));


                                    if($wcf['nature'] == 'GROSS'){
                                        $employer_wcf_amount = $gross_pay * ($wcf['employer_percentage']/100);
                                    }elseif($wcf['nature'] == 'PAYE'){
                                        $employer_wcf_amount = $paye_amount * ($wcf['employer_percentage']/100);
                                    } else{
                                        $employer_wcf_amount = $basic_salary * ($wcf['employer_percentage']/100);
                                    }

                                    if($sdl['nature'] == 'GROSS'){
                                        $employer_sdl_amount = $gross_pay * ($sdl['employer_percentage']/100);
                                    }elseif($wcf['nature'] == 'PAYE'){
                                        $employer_sdl_amount = $paye_amount * ($sdl['employer_percentage']/100);
                                    } else{
                                        $employer_sdl_amount = $basic_salary * ($sdl['employer_percentage']/100);
                                    }

                                    if($heslb['nature'] == 'GROSS'){
                                        $employee_heslb_amount = $gross_pay * ($heslb['employee_percentage']/100);
                                    }elseif($wcf['nature'] == 'PAYE'){
                                        $employee_heslb_amount = $paye_amount * ($heslb['employee_percentage']/100);
                                    } else{
                                        $employee_heslb_amount = $basic_salary * ($heslb['employee_percentage']/100);
                                    }
                                    $net = $taxable - ($paye_amount+$employee_heslb_amount+$advance_salary);
                                    ?>
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$staff->name}}</td>
                                        <td class="text-right">{{number_format($basic_salary)}}</td>
                                        <td class="text-right">{{number_format($allowance,2,'.',',')}}</td>
                                        <td class="text-right">{{number_format($gross_pay,2,'.',',')}}</td>
                                        <td class="text-right">{{number_format($employer_pension_amount,2,'.',',')}}</td>
                                        <td class="text-right">{{number_format($employee_pension_amount,2,'.',',')}}</td>
                                        <td class="text-right">{{number_format($employer_health_amount,2,'.',',')}}</td>
                                        <td class="text-right">{{number_format($employee_health_amount,2,'.',',')}}</td>
                                        <td class="text-right">{{number_format($taxable,2,'.',',')}}</td>
                                        <td class="text-right">{{number_format($paye_amount,2,'.',',')}}</td>
                                        <td class="text-right">{{number_format($employer_wcf_amount,2,'.',',')}}</td>
                                        <td class="text-right">{{number_format($employer_sdl_amount,2,'.',',')}}</td>
                                        <td class="text-right">{{number_format($employee_heslb_amount,2,'.',',')}}</td>
                                        <td class="text-right">{{number_format($advance_salary,2,'.',',')}}</td>
                                        <td class="text-right">{{number_format(0,2,'.',',')}}</td>
                                        <td class="text-right">{{number_format($net,2,'.',',')}}</td>
                                    </tr>
                                @endforeach

                                </tbody>

                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection



