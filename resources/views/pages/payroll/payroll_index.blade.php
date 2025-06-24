@extends('layouts.backend')
@section('css_before')
    <!-- Page JS Plugins CSS -->

@endsection

@section('js_after')


    <script>

        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd'
        });
        //var TableData = new Array();
        var TableData;
        TableData = storeTblValues()
        // TableData = $.toJSON(TableData);
        var TableData = JSON.stringify(TableData);
        function storeTblValues() {
            var TableData = new Array();
            $('#payroll tr').each(function (row, tr) {
                TableData[row] = {
                    "name": $(tr).find('td:eq(1)').text()
                    , "basicSalary": $(tr).find('td:eq(2)').text()
                    , "allowance": $(tr).find('td:eq(3)').text()
                    , "grossPay": $(tr).find('td:eq(4)').text()
                    , "employerPension": $(tr).find('td:eq(5)').text()
                    , "employeePension": $(tr).find('td:eq(6)').text()
                    , "employerHealth": $(tr).find('td:eq(7)').text()
                    , "employeeHealth": $(tr).find('td:eq(8)').text()
                    , "taxable": $(tr).find('td:eq(9)').text()
                    , "paye": $(tr).find('td:eq(10)').text()
                    , "wpf": $(tr).find('td:eq(11)').text()
                    , "sdl": $(tr).find('td:eq(12)').text()
                    , "heslb": $(tr).find('td:eq(13)').text()
                    , "advanceSalary": $(tr).find('td:eq(14)').text()
                    , "totalLoan": $(tr).find('td:eq(15)').text()
                    , "loanDeduction": $(tr).find('td:eq(16)').text()
                    , "loanBalance": $(tr).find('td:eq(17)').text()
                    , "net": $(tr).find('td:eq(18)').text()
                    , "staff_id": $(tr).find('td:eq(19)').text()
                }
            });
            TableData.shift();  // first row is the table header - so remove
            TableData.pop();  // last row is the table header - so remove
            return TableData;
            //console.log(TableData)
        }
        $(".btn-submit").click(function(e){

            e.preventDefault();

            var TableData = JSON.stringify(storeTblValues());


            $.ajax({
                type:'POST',
                url:"{{ route('ajax_request.post') }}",
                {{--data:{"_token": "{{ csrf_token() }}","class":"PayrollRecord"},--}}
                data:{TableData,"_token": "{{ csrf_token() }}"},
                success:function(data){
                    if(data == 1){
                        Swal.fire('Payroll Created!', 'You have successful created a Payroll for this Month', 'success');
                        setTimeout(function(){// wait for 5 secs(2)
                            location.reload(); // then reload the page.(3)
                        }, 5000);
                    }else{
                        Swal.fire('Changes are not saved', 'There is a Problem', 'info')
                    }
                    //alert(data.success);
                }
            });
        });

       // console.log(TableData)
    </script>
@endsection
@section('content')
    <?php
         use Illuminate\Support\Facades\DB;
         $start_date = $_POST['start_date'] ?? date('Y-m-01');
         $end_date = $_POST['end_date'] ?? date('Y-m-t');
         $payroll_record = new \App\Models\PayrollRecord();
         $payroll_records = $payroll_record->getCurrentPayroll($start_date,$end_date);
         $is_current_payroll_paid = \App\Models\Payroll::isCurrentPayrollPaid($start_date,$end_date);
     ?>
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Payroll
                <div class="float-right">
                    @if($is_current_payroll_paid)
                    <button class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-clock">&nbsp;</i>Already Created Payroll This Month</button>
                    @else
                        @can('Add Payroll')
                            <button type="button"  class="btn btn-rounded btn-outline-primary min-width-125 mb-10 btn-submit"><i class="si si-plus">&nbsp;</i>Create Payroll</button>
                        @endcan

                    @endif
                </div>
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-gd-lake">
                        <h3 class="block-title">Staff Payroll</h3>
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
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-t')}}">
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
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full" id="payroll">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Name</th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
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
                                    <th>Total Loan</th>
                                    <th>Loan Deduction</th>
                                    <th>Loan Balance</th>
                                    <th>NET</th>
                                    <th class="d-none">ID</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php

                                $basic_salary_total = 0;
                                $allowance_total = 0;
                                $gross_pay_total = 0;
                                $employee_health_amount_total = 0;
                                $employee_pension_amount_total = 0;
                                $employer_health_amount_total = 0;
                                $employer_pension_amount_total = 0;
                                $taxable_total = 0;
                                $paye_amount_total = 0;
                                $employer_sdl_amount_total = 0;
                                $employer_wcf_amount_total = 0;
                                $advance_salary_total = 0;
                                $employee_heslb_amount_total = 0;
                                $net_total = 0;
                                $current_loan_total = 0;
                                $current_loan_deduction_total = 0;
                                $Staff = new \App\Models\Staff();


                                ?>
                                @if(!$is_current_payroll_paid)
                                    @foreach($staffs as $staff)
                                    <?php
                                    $month = date('m');
                                    $staff_id = $staff->id;
                                    $basic_salary = \App\Models\Staff::getStaffSalary($staff_id);
                                    $advance_salary = \App\Models\Staff::getStaffAdvanceSalary($staff_id,$start_date,$end_date);
                                    //$advance_salary = \App\Models\Staff::getStaffAdvanceSalary($staff_id,$start_date,$end_date);
                                    $allowance = \App\Models\Staff::getStaffAllowance($staff_id,$month);
                                    //$gross_pay = $basic_salary + $allowance;
                                    $gross_pay = \App\Models\Staff::getStaffGrossPay($staff_id,$month);

                                    $pension = \App\Models\Staff::getStaffDeduction($staff_id,'NSSF');
                                    $health = \App\Models\Staff::getStaffDeduction($staff_id,'NHIF');
                                    $paye = \App\Models\Staff::getStaffDeduction($staff_id,'PAYE');
                                    $wcf = \App\Models\Staff::getStaffDeduction($staff_id,'WCF');
                                    $sdl = \App\Models\Staff::getStaffDeduction($staff_id,'SDL');
                                    $heslb = \App\Models\Staff::getStaffDeduction($staff_id,'HESLB');
                                    $loan = \App\Models\Staff::getStaffLoan($staff_id);
                                    $loan_deduction = \App\Models\Staff::getStaffLoanDeductionForCurrentLoan($staff_id);
                                    $check_if_staff_has_loan = \App\Models\Staff::isStaffHasLoan($staff_id);

                                    if($check_if_staff_has_loan){
                                        $current_loan = $loan;
                                        $current_loan_deduction = $loan_deduction;
                                    }else{
                                        $current_loan = 0;
                                        $current_loan_deduction = 0;
                                    }

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
//                                    $net = $taxable - ($paye_amount+$employee_heslb_amount+$advance_salary+$current_loan_deduction);
                                    $net = $taxable - ($advance_salary+$current_loan_deduction);
                                    $basic_salary_total += $basic_salary;
                                    $allowance_total += $allowance;
                                    $gross_pay_total += $gross_pay;
                                    $employee_health_amount_total += $employee_health_amount;
                                    $employee_pension_amount_total += $employee_pension_amount;
                                    $employer_health_amount_total += $employer_health_amount;
                                    $employer_pension_amount_total += $employer_pension_amount;
                                    $taxable_total += $taxable;
                                    $paye_amount_total += $paye_amount;
                                    $employer_sdl_amount_total += $employer_sdl_amount;
                                    $employer_wcf_amount_total += $employer_wcf_amount;
                                    $advance_salary_total += $advance_salary;
                                    $current_loan_total += $current_loan;
                                    $current_loan_deduction_total += $current_loan_deduction;
                                    $employee_heslb_amount_total += $employee_heslb_amount;
                                    $net_total += $net;



                                    ?>
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$staff->name}}</td>
                                        <td class="text-right d-none">{{$basic_salary}}</td>
                                        <td class="text-right d-none">{{$allowance,2,'.',','}}</td>
                                        <td class="text-right d-none">{{$gross_pay,2,'.',','}}</td>
                                        <td class="text-right d-none">{{$employer_pension_amount,2,'.',','}}</td>
                                        <td class="text-right d-none">{{$employee_pension_amount,2,'.',','}}</td>
                                        <td class="text-right d-none">{{$employer_health_amount,2,'.',','}}</td>
                                        <td class="text-right d-none">{{$employee_health_amount,2,'.',','}}</td>
                                        <td class="text-right d-none">{{$taxable,2,'.',','}}</td>
                                        <td class="text-right d-none">{{$paye_amount,2,'.',','}}</td>
                                        <td class="text-right d-none">{{$employer_wcf_amount,2,'.',','}}</td>
                                        <td class="text-right d-none">{{$employer_sdl_amount,2,'.',','}}</td>
                                        <td class="text-right d-none">{{$employee_heslb_amount,2,'.',','}}</td>
                                        <td class="text-right d-none">{{$advance_salary,2,'.',','}}</td>
                                        <td class="text-right d-none">{{$current_loan,2,'.',','}}</td>
                                        <td class="text-right d-none">{{$current_loan_deduction,2,'.',','}}</td>
                                        <td class="text-right d-none">{{($current_loan - $current_loan_deduction),2,'.',','}}</td>
                                        <td class="text-right d-none">{{$net,2,'.',','}}</td>
                                        <td class="text-right d-none">{{$staff_id}}</td>
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
                                        <td class="text-right">{{number_format($current_loan,2,'.',',')}}</td>
                                        <td class="text-right">{{number_format($current_loan_deduction,2,'.',',')}}</td>
                                        <td class="text-right">{{number_format(($current_loan - $current_loan_deduction),2,'.',',')}}</td>
                                        <td class="text-right">{{number_format($net,2,'.',',')}}</td>
                                        <td class="text-right d-none">{{$staff_id}}</td>
                                    </tr>
                                @endforeach
                                @else
                                  @foreach($payroll_records as $item)
                                      <?php
                                      $basic_salary =  $item->basicSalary;
                                      $allowance = $item->allowance;
                                      $gross_pay = $item->grossPay;
                                      $employer_pension_amount = $item->employerPension;
                                      $employee_pension_amount = $item->employeePension;
                                      $employer_health_amount = $item->employerHealth;
                                      $employee_health_amount = $item->employeeHealth;
                                      $taxable = $item->taxable;
                                      $paye_amount = $item->paye;
                                      $employer_wcf_amount = $item->wcf;
                                      $employer_sdl_amount = $item->sdl;
                                      $employee_heslb_amount = $item->heslb;
                                      $advance_salary = $item->advanceSalary;
                                      $net = $item->net;
                                      $name= $item->name;
                                      $totalLoan= $item->totalLoan;
                                      $loanBalance= $item->loanBalance;
                                      $loanDeduction= $item->loanDeduction;
                                      $staff_id = $item->staff_id;
                                      $basic_salary_total += $basic_salary;
                                      $allowance_total += $allowance;
                                      $gross_pay_total += $gross_pay;
                                      $employee_health_amount_total += $employee_health_amount;
                                      $employee_pension_amount_total += $employee_pension_amount;
                                      $employer_health_amount_total += $employer_health_amount;
                                      $employer_pension_amount_total += $employer_pension_amount;
                                      $taxable_total += $taxable;
                                      $paye_amount_total += $paye_amount;
                                      $employer_sdl_amount_total += $employer_sdl_amount;
                                      $employer_wcf_amount_total += $employer_wcf_amount;
                                      $advance_salary_total += $advance_salary;
                                      $employee_heslb_amount_total += $employee_heslb_amount;
                                      $net_total += $net;
                                      $current_loan_total += $totalLoan;
                                      $current_loan_deduction_total += $loanDeduction;
                                      ?>
                                      <tr>
                                          <td>{{$loop->iteration}}</td>
                                          <td>{{$name}}</td>
                                          <td class="text-right d-none">{{$basic_salary}}</td>
                                          <td class="text-right d-none">{{$allowance,2,'.',','}}</td>
                                          <td class="text-right d-none">{{$gross_pay,2,'.',','}}</td>
                                          <td class="text-right d-none">{{$employer_pension_amount,2,'.',','}}</td>
                                          <td class="text-right d-none">{{$employee_pension_amount,2,'.',','}}</td>
                                          <td class="text-right d-none">{{$employer_health_amount,2,'.',','}}</td>
                                          <td class="text-right d-none">{{$employee_health_amount,2,'.',','}}</td>
                                          <td class="text-right d-none">{{$taxable,2,'.',','}}</td>
                                          <td class="text-right d-none">{{$paye_amount,2,'.',','}}</td>
                                          <td class="text-right d-none">{{$employer_wcf_amount,2,'.',','}}</td>
                                          <td class="text-right d-none">{{$employer_sdl_amount,2,'.',','}}</td>
                                          <td class="text-right d-none">{{$employee_heslb_amount,2,'.',','}}</td>
                                          <td class="text-right d-none">{{$advance_salary,2,'.',','}}</td>
                                          <td class="text-right d-none">{{$totalLoan,2,'.',','}}</td>
                                          <td class="text-right d-none">{{$loanDeduction,2,'.',','}}</td>
                                          <td class="text-right d-none">{{($totalLoan - $loanDeduction),2,'.',','}}</td>
                                          <td class="text-right d-none">{{$net,2,'.',','}}</td>
                                          <td class="text-right d-none">{{$staff_id}}</td>
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
                                          <td class="text-right">{{number_format($totalLoan),2,'.',','}}</td>
                                          <td class="text-right">{{number_format($loanDeduction),2,'.',','}}</td>
                                          <td class="text-right">{{number_format($totalLoan - $loanDeduction),2,'.',','}}</td>
                                          <td class="text-right">{{number_format($net,2,'.',',')}}</td>
                                          <td class="text-right d-none">{{$staff_id}}</td>
                                      </tr>
                                  @endforeach
                                @endif

                                </tbody>
                                <tfoot>
                                <tr>
                                    <th class="text-center" style="width: 100px;"></th>
                                    <th></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right d-none"></th>
                                    <th class="text-right">{{number_format($basic_salary_total)}}</th>
                                    <th class="text-right">{{number_format($allowance_total,2,'.',',')}}</th>
                                    <th class="text-right">{{number_format($gross_pay_total,2,'.',',')}}</th>
                                    <th class="text-right">{{number_format($employer_pension_amount_total,2,'.',',')}}</th>
                                    <th class="text-right">{{number_format($employee_pension_amount_total,2,'.',',')}}</th>
                                    <th class="text-right">{{number_format($employer_health_amount_total,2,'.',',')}}</th>
                                    <th class="text-right">{{number_format($employee_health_amount_total,2,'.',',')}}</th>
                                    <th class="text-right">{{number_format($taxable_total,2,'.',',')}}</th>
                                    <th class="text-right">{{number_format($paye_amount_total,2,'.',',')}}</th>
                                    <th class="text-right">{{number_format($employer_wcf_amount_total,2,'.',',')}}</th>
                                    <th class="text-right">{{number_format($employer_sdl_amount_total,2,'.',',')}}</th>
                                    <th class="text-right">{{number_format($employee_heslb_amount_total,2,'.',',')}}</th>
                                    <th class="text-right">{{number_format($advance_salary_total,2,'.',',')}}</th>
                                    <th class="text-right">{{number_format($current_loan_total,2,'.',',')}}</th>
                                    <th class="text-right">{{number_format($current_loan_deduction_total,2,'.',',')}}</th>
                                    <th class="text-right">{{number_format(($current_loan_total - $current_loan_deduction_total),2,'.',',')}}</th>
                                    <th class="text-right">{{number_format($net_total,2,'.',',')}}</th>
                                    <th class="d-none">ID</th>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection


