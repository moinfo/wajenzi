@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Reports
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-gd-dusk">
                        <h3 class="block-title">Statement of Comprehensive Income For the Year ended {{date('jS-M-Y', strtotime('last day of december last year'))}}</h3>
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
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d', strtotime('first day of january this year'))}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon2">End Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d', strtotime('last day of december this year'))}}">
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
                        <?php
                        $start_date = $_POST['start_date'] ?? date('Y-m-d', strtotime('first day of january this year'));
                        $end_date = $_POST['end_date'] ?? date('Y-m-d', strtotime('last day of december this year'));
                        $start_date_last =  date('Y-m-d', strtotime('first day of january last year'));
                        $end_date_last = date('Y-m-d', strtotime('last day of december last year'));
                        $revenue_current = \App\Models\Sale::getTotalRevenue($start_date,$end_date) ?? 0;
                        $revenue_last = \App\Models\Sale::getTotalRevenue($start_date_last,$end_date_last) ?? 0;
                        $cost_of_sales_current = \App\Models\Sale::getCostOfSales($start_date,$end_date) ?? 0;
                        $cost_of_sales_last = \App\Models\Sale::getCostOfSales($start_date_last,$end_date_last) ?? 0;
                         $gross_profit_current = \App\Models\Gross::getTotalGrossProfit($start_date,$end_date) ?? 0;
                        $gross_profit_last = \App\Models\Gross::getTotalGrossProfit($start_date_last,$end_date_last) ?? 0;
                        $administrative_expenses_current = \App\Models\Expense::getTotalAdministrativeExpenses($start_date,$end_date) ?? 0;
                        $administrative_expenses_last = \App\Models\Expense::getTotalAdministrativeExpenses($start_date_last,$end_date_last) ?? 0;
                        $financial_charges_current = \App\Models\Expense::getTotalFinancialCharges($start_date,$end_date) ?? 0;
                        $financial_charges_last = \App\Models\Expense::getTotalFinancialCharges($start_date_last,$end_date_last) ?? 0;
                        $depreciation_current = \App\Models\Expense::getTotalDepreciation($start_date,$end_date) ?? 0;
                        $depreciation_last = \App\Models\Expense::getTotalDepreciation($start_date_last,$end_date_last) ?? 0;
                        $total_expenses_current = \App\Models\Expense::getTotalExpensesInFinancial($start_date,$end_date) ?? 0;
                        $total_expenses_last = \App\Models\Expense::getTotalExpensesInFinancial($start_date_last,$end_date_last) ?? 0;
                        $Profit_from_Operating_Activities_Before_Taxation_current = \App\Models\Taxation::ProfitFromOperatingActivitiesBeforeTaxation($start_date,$end_date) ?? 0;
                        $Profit_from_Operating_Activities_Before_Taxation_last = \App\Models\Taxation::ProfitFromOperatingActivitiesBeforeTaxation($start_date_last,$end_date_last) ?? 0;
                        $Taxation_current = \App\Models\Taxation::getMainlandTaxation($start_date,$end_date) ?? 0;
                        $expenses = \App\Models\ExpensesCategory::all();
                        $Taxation_last = \App\Models\Taxation::getMainlandTaxation($start_date_last,$end_date_last) ?? 0;
                        $Profit_from_Operating_Activities_After_Taxation_current = \App\Models\Taxation::Profit_From_Operating_Activities_After_Taxation($start_date,$end_date) ?? 0;
                        $Profit_from_Operating_Activities_After_Taxation_last = \App\Models\Taxation::Profit_From_Operating_Activities_After_Taxation($start_date_last,$end_date_last) ?? 0;
                        dump($expenses);

                        ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter ">
                                <thead>
                                <tr>
                                    <th></th>
                                    <th class="text-center">Note</th>
                                    <th class="text-right">{{date('Y-m-d', strtotime('last day of december this year'))}}</th>
                                    <th class="text-right">{{date('Y-m-d', strtotime('last day of december last year'))}}</th>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td class="text-right">TShs</td>
                                    <td class="text-right">TShs</td>
                                </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Revenue</td>
                                        <td class="text-center">4</td>
                                        <td class="text-right">{{number_format($revenue_current)}}</td>
                                        <td class="text-right">{{number_format($revenue_last)}}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>Cost of Sales</td>
                                        <td class="text-center">8</td>
                                      <td class="text-right">{{number_format($cost_of_sales_current)}}</td>
                                        <td class="text-right">{{number_format($cost_of_sales_last)}}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>Gross Profit</td>
                                        <td class="text-center"></td>
                                      <td class="text-right">{{number_format($gross_profit_current)}}</td>
                                        <td class="text-right">{{number_format($gross_profit_last)}}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <?php
                                        $total_expenses = 0;
                                    foreach ($expenses as $index => $expense) {
                                        $total = \App\Models\Expense::getTotalExpensesGroupByExpensesCategory($start_date,$end_date,$expense->id);
                                        $total_expenses += $total;
                                       ?>

                                    <tr>
                                        <td>{{$expense->name}}</td>
                                        <td class="text-center"></td>
                                        <td class="text-right">{{number_format($total)}}</td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <?php
                                    }
                                   ?>

                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td class="text-right">{{number_format($total_expenses)}}</td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>

                                    <tr>
                                        <td> Profit from Operating Activities Before Taxation</td>
                                        <td class="text-center"></td>
                                      <td class="text-right">{{number_format($Profit_from_Operating_Activities_Before_Taxation_current)}}</td>
                                        <td class="text-right">{{number_format($Profit_from_Operating_Activities_Before_Taxation_last)}}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>

                                    <tr>
                                        <td>Tax</td>
                                        <td class="text-center"></td>
                                      <td class="text-right">{{number_format($Taxation_current)}}</td>
                                        <td class="text-right">{{number_format($Taxation_last)}}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>  Profit from Operating Activities After Taxation</td>
                                        <td class="text-center"></td>
                                      <td class="text-right">{{number_format($Profit_from_Operating_Activities_After_Taxation_current)}}</td>
                                        <td class="text-right">{{number_format($Profit_from_Operating_Activities_After_Taxation_last)}}</td>
                                    </tr>
                                </tbody>

                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection



