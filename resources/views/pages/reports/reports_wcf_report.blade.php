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
                                <form  name="collection_search" action="" id="filter-form" method="post" autocomplete="off">
                                    @csrf
                                    <div class="row">
                                        <div class="class col-md-2">
                                            <div class="input-group mb-3">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text" id="basic-addon1">Month</span>
                                                </div>
                                                <select name="month" id="month" class="form-control">
                                                    <option value="1" {{ ($this_month == 1) ? 'selected' : '' }}>Jan</option>
                                                    <option value="2" {{ ($this_month == 2) ? 'selected' : '' }}>Feb</option>
                                                    <option value="3" {{ ($this_month == 3) ? 'selected' : '' }}>Mar</option>
                                                    <option value="4" {{ ($this_month == 4) ? 'selected' : '' }}>Apr</option>
                                                    <option value="5" {{ ($this_month == 5) ? 'selected' : '' }}>May</option>
                                                    <option value="6" {{ ($this_month == 6) ? 'selected' : '' }}>Jun</option>
                                                    <option value="7" {{ ($this_month == 7) ? 'selected' : '' }}>Jul</option>
                                                    <option value="8" {{ ($this_month == 8) ? 'selected' : '' }}>Aug</option>
                                                    <option value="9" {{ ($this_month == 9) ? 'selected' : '' }}>Sept</option>
                                                    <option value="10" {{ ($this_month == 10) ? 'selected' : '' }}>Oct</option>
                                                    <option value="11" {{ ($this_month == 11) ? 'selected' : '' }}>Nov</option>
                                                    <option value="12" {{ ($this_month == 12) ? 'selected' : '' }}>Dec</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="class col-md-2">
                                            <div class="input-group mb-3">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text" id="basic-addon1">Year</span>
                                                </div>
                                                <select name="year" id="year" class="form-control">
                                                    <option value="2021" {{ ($this_year == 2021) ? 'selected' : '' }}>2021</option>
                                                    <option value="2022" {{ ($this_year == 2022) ? 'selected' : '' }}>2022</option>
                                                    <option value="2023" {{ ($this_year == 2023) ? 'selected' : '' }}>2023</option>
                                                    <option value="2024" {{ ($this_year == 2024) ? 'selected' : '' }}>2024</option>
                                                    <option value="2025" {{ ($this_year == 2025) ? 'selected' : '' }}>2025</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="class col-md-2">
                                            <div>
                                                <button type="submit" name="submit" class="btn btn-sm btn-primary">
                                                    Show
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="block-header text-center">
                                <h3 class="block-title">WCF Report</h3>
                            </div>
                        </div>
                    </div>

                    <div class="block-content">
                        <div class="hide_print">Payroll Number : <b>{{$payroll->payroll_number ?? ''}}</b></div>
                        <table class="table table-condensed">
                            <tbody>
                            <tr>
                                <td width="25%" class="txt-left"><p></p><p>S.L.P 79655</p>
                                    <p>Plot No. 37, GEPF House,New Bagamoyo Road</p>
                                    <p>0800 110028 / 0800 110029</p>
                                    <p>info@wcf.go.tz</p></td>
                                <td width="50%" class="text-center">
                                    <h2>WORKERS COMPENSATION FUND</h2>
                                    <img alt=" " width="180px" src="{{ asset('logo/wcf.png') }}">
                                </td>
                                <td width="25%" class="text-right">WCF/CONT/{{$payroll_id}}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-center">
                                    <h5 class="text-capitalize"><u>CONTRIBUTIONS FOR THE MONTH OF @if($payroll) {{date('F',strtotime(($payroll->year).'-'.$payroll->month.'-'.'01')).' - '.$payroll->year}} @endif</u></h5>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3">
                                    <p>Name of Employer &nbsp;&nbsp;&nbsp; <span class="text-strong">{{settings('ORGANIZATION_NAME')}}</span></p>
                                    <p>Employer’s Registration Number &nbsp;&nbsp;&nbsp; <span class="text-strong">{{settings('TAX_IDENTIFICATION_NUMBER')}}</span></p>
                                </td>

                            </tr>

                            </tbody>
                        </table>
                        @if($payroll)
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <th>S/No</th>
                                    <th>Name in Full</th>
                                    <th>Employee Gross Salary</th>
                                    <th colspan="2">Deduction Amount (Tsh.)</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php
                                    $total_basic_salary = 0;
                                    $total_gross_pay = 0;
                                    $total_employee_deducted_amount_wcf = 0;
                                @endphp
                                @foreach($staffs as $staff)
                                    @php
                                        $staff_id = $staff->id;
                                        $employee_deducted_amount_wcf = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,3,'employee_deduction_amount') ?? 0;
                                    @endphp
                                    @if($employee_deducted_amount_wcf != 0)
                                        @php
                                            $basic_salary = \App\Models\Staff::getStaffSalaryPaid($staff_id,$payroll_id);
                                            $total_basic_salary += $basic_salary;
                                            $gross_pay = \App\Models\Staff::getStaffGrossPayPaid($staff_id,$payroll_id) ?? 0;
                                            $total_gross_pay += $gross_pay;
                                            $total_employee_deducted_amount_wcf += $employee_deducted_amount_wcf;
                                        @endphp
                                        <tr>
                                            <td>{{$loop->iteration}}</td>
                                            <td>{{$staff->name}}</td>
                                            <td class="money text-right">{{number_format($gross_pay)}}</td>
                                            <td class="money text-right" width="40">1%</td>
                                            <td class="money text-right">{{number_format($employee_deducted_amount_wcf)}}</td>
                                        </tr>
                                    @endif
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th colspan="4" class="text-center text-strong">TOTAL</th>
                                    <th class="money text-right">{{number_format($total_employee_deducted_amount_wcf)}}</th>
                                </tr>
                                </tfoot>
                            </table>
                        @else
                            <div>
                                <div class="block block-themed bg-gray min-height-200 text-center" >
                                    <div class="block-content">
                                        <div class="row no-print m-t-10">
                                            <div class="class col-md-12">
                                                <div class="class card-box ">
                                                    <div class='jumbotron '>Failed to get WCF Report for this month!</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
