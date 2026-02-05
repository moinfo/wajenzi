@extends('layouts.backend')

@section('content')
    @php
        $year = $_POST['year'] ?? date("Y");
            $start_date_year = $year.'-'.'01'.'-'.'01';
                $end_date_year = $year.'-'.'12'.'-'.'31';
                $start    = new DateTime("$start_date_year");
                 $start->modify('first day of this month');
                 $end      = new DateTime("$end_date_year");
                 $end->modify('first day of next month');
                 $interval = DateInterval::createFromDateString('1 month');
                 $period   = new DatePeriod($start, $interval, $end);
 $total_employee = 0;
                $total_employer = 0;
                $total_total = 0;
    @endphp
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Reports
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-wajenzi-gradient">
                        <h3 class="block-title">Annually Deduction Report</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                @include('components.headed_paper')
                                <br/>
                                <div class="class card-box">
                                    <form name="collection_search" action="" id="filter-form" method="post"
                                          autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Year</span>
                                                    </div>
                                                    <select name="year" id="year" class="form-control">
                                                        <option value="2019">2019</option>
                                                        <option value="2020">2020</option>
                                                        <option value="2021">2021</option>
                                                        <option value="2022">2022</option>
                                                        <option value="2023">2023</option>
                                                        <option value="2024" selected>2024</option>
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
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full"
                                   data-ordering="false">
                                <thead>
                                <tr>
                                    <th></th>
                                    @foreach ($period as $dt)
                                        <th>{{$dt->format("F, Y")}}</th>
                                    @endforeach
                                    <th>Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>INCOME TAX</td>
                                    @php
                                        $total_income_tax = 0;
                                    @endphp
                                    @foreach ($period as $dt)
                                        @php
                                            $start_date = $dt->format("Y-m-01");
                                             $end_date = $dt->format("Y-m-t");
                                             $income_tax_actual = App\Models\ProvisionTax::Profit_From_Operating_Activities_After_Provision($start_date,$end_date);
                                             $withholding_tax = App\Models\WithholdingTax::getTotalWithholdingTax($start_date,$end_date);
                                             $adjusted_assessment_tax = App\Models\AdjustedAssessmentTax::getTotalAdjustedAssessmentTax($start_date,$end_date);
                                             $income_tax = $income_tax_actual + $adjusted_assessment_tax + $withholding_tax;
                                             $total_income_tax += $income_tax;
                                        @endphp
                                        <td class="text-right">{{number_format($income_tax)}}</td>
                                    @endforeach
                                    <td class="text-right">{{number_format($total_income_tax)}}</td>
                                </tr>
                                <tr>
                                    <td>VAT PAYMENT</td>
                                    @php
                                        $total_vat_payment = 0;
                                    @endphp
                                    @foreach ($period as $dt)
                                        @php
                                            $start_date = $dt->format("Y-m-01");
                                             $end_date = $dt->format("Y-m-t");
                                             $vat_payment = App\Models\VatPayment::getTotalPaymentOfLastMonth($start_date,$end_date);
                                             $total_vat_payment += $vat_payment;
                                        @endphp
                                        <td class="text-right">{{number_format($vat_payment)}}</td>
                                    @endforeach
                                    <td class="text-right">{{number_format($total_vat_payment)}}</td>
                                </tr>


                                @foreach($deductions as $deduction)
                                    <tr>
                                        <td>{{$deduction->name}}</td>
                                        @php
                                            $total_deduction_type = 0;
                                        @endphp
                                        @foreach ($period as $dt)
                                            @php
                                                $start_date = $dt->format("Y-m-01");
                                                 $end_date = $dt->format("Y-m-t");
                                                 $deduction_type = App\Models\PayrollRecord::getTotalDeduction($start_date,$end_date,$deduction->search);
                                                 $total_deduction_type += $deduction_type;
                                            @endphp
                                            <td class="text-right">{{number_format($deduction_type)}}</td>
                                        @endforeach
                                        <td class="text-right">{{number_format($total_deduction_type)}}</td>
                                    </tr>
                                @endforeach

                                @foreach($sub_expenses as $sub_expense)
                                    @php
                                        $total_expenses = 0;
                                    @endphp
                                    <tr>
                                        <td>{{$sub_expense->name}}</td>
                                        @foreach ($period as $dt)
                                            @php
                                                $start_date = $dt->format("Y-m-01");
                                                 $end_date = $dt->format("Y-m-t");
                                                 $expenses = App\Models\Expense::getTotalExpensesGroupBySubExpensesCategory($start_date,$end_date,$sub_expense->id);
                                                 $total_expenses += $expenses;
                                            @endphp
                                            <td class="text-right">{{number_format($expenses)}}</td>
                                        @endforeach
                                        <td class="text-right">{{number_format($total_expenses)}}</td>
                                    </tr>
                                @endforeach
                                @foreach ($period as $dt)
                                    @php
                                        $start_date = $dt->format("Y-m-01");
                                        $end_date = $dt->format("Y-m-t");
                                        $efd_id = null;
                                        $employee = \App\Models\PayrollRecord::getTotalNSSFEmployee($start_date,$end_date) ?? 0;
                                        $total_employee += $employee;
                                        $employer = \App\Models\PayrollRecord::getTotalNSSFEmployer($start_date,$end_date) ?? 0;
                                        $total_employee += $employer;
                                        $total = $employee + $employer;
                                        $total_total = $total_employee + $total_employee;
                                    @endphp
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th>Total</th>
                                    @php
                                        $total_income_tax_footer = 0;
                                        $total_vat_payment_footer = 0;
                                        $total_deduction_type_footer = 0;
                                        $total_expenses_footer = 0;
                                    @endphp
                                    @foreach ($period as $dt)
                                    @php
                                        $start_date = $dt->format("Y-m-01");
                                        $end_date = $dt->format("Y-m-t");
                                        $income_tax_actual = App\Models\ProvisionTax::Profit_From_Operating_Activities_After_Provision($start_date,$end_date);
                                             $withholding_tax = App\Models\WithholdingTax::getTotalWithholdingTax($start_date,$end_date);
                                             $adjusted_assessment_tax = App\Models\AdjustedAssessmentTax::getTotalAdjustedAssessmentTax($start_date,$end_date);
                                             $income_tax = $income_tax_actual + $adjusted_assessment_tax + $withholding_tax;
                                             $total_income_tax_footer += $income_tax;
                                              $vat_payment = App\Models\VatPayment::getTotalPaymentOfLastMonth($start_date,$end_date);
                                             $total_vat_payment_footer += $vat_payment;
                                              $deduction_type_employerPension = App\Models\PayrollRecord::getTotalDeduction($start_date,$end_date,'employerPension');
                                              $deduction_type_employerHealth = App\Models\PayrollRecord::getTotalDeduction($start_date,$end_date,'employerHealth');
                                              $deduction_type_wpf = App\Models\PayrollRecord::getTotalDeduction($start_date,$end_date,'wpf');
                                              $deduction_type_sdl = App\Models\PayrollRecord::getTotalDeduction($start_date,$end_date,'sdl');
                                              $total_deduction_type_footer += ($deduction_type_employerPension+$deduction_type_employerHealth+$deduction_type_wpf+$deduction_type_sdl);
                                    $expenses = App\Models\Expense::getTotalExpensesGroupBySubExpensesCategoryOnlyFinancial($start_date,$end_date);
                                                 $total_expenses_footer += $expenses;
                                    @endphp

                                    <th class="text-right">{{number_format($expenses+$income_tax+$vat_payment+($deduction_type_employerPension+$deduction_type_employerHealth+$deduction_type_wpf+$deduction_type_sdl))}}</th>
                                    @endforeach
                                    <th class="text-right">{{number_format($total_expenses_footer+$total_income_tax_footer+$total_vat_payment_footer+$total_deduction_type_footer)}}</th>
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



