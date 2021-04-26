@extends('layouts.backend')
@section('css_before')
    <!-- Page JS Plugins CSS -->
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables/dataTables.bootstrap4.css') }}">
@endsection

@section('js_after')
    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>

    <!-- Page JS Code -->
    <script src="{{ asset('js/pages/tables_datatables.js') }}"></script>

    <script>
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd'
        });
    </script>
@endsection
@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Reports
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-gd-dusk">
                        <h3 class="block-title">Deduction Report</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
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
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($staffs as $staff)
                                    <?php
                                        $staff_id = $staff->id;
                                        $basic_salary = \App\Models\StaffSalary::Where('staff_id',$staff_id)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('staff_id')->get()->first()['total_amount'] ?? 0;
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

                                       // $taxable_array[] = $gross_pay - ($employee_pension_amount+$employee_health_amount);
                                      //  dump($taxable_array);
                                    foreach ($payes as $paye){
                                        if ( in_array($taxable, range($paye->minimum_amount,$paye->maximum_amount)) ) {
                                            $employee_percentage = $paye->employee_percentage ?? 0;
                                            $additional_amount = $paye->additional_amount ?? 0;
                                            $maximum_amount = $paye->maximum_amount ?? 0;
                                        }
                                    }
                                   // echo $employee_percentage;
                                   // echo $additional_amount;
                                   // echo $maximum_amount;

                                   // $paye_amount = ($additional_amount + $employee_percentage/100 * ($maximum_amount - $taxable));
                                    $paye_amount = $taxable * ($employee_percentage/100);

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



