@extends('layouts.backend')

@section('content')

    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Reports
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-wajenzi-gradient">
                        <h3 class="block-title">Statutory Schedules Report</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
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
                                                        <option value="2024">2024</option>
                                                        <option value="2024 selected" selected>2025</option>
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
                                   data-ordering="false" data-sorting="false">
                                <thead>
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
                                            $total_payment_per_monthly = 0;

                                @endphp
                                <tr>
                                    <th>No</th>
                                    <th class="w-100">Statutory</th>
                                    <th class="w-100">Sub Category</th>
                                    <th class="w-100">Per Annually</th>
                                    <th class="w-100">Per Monthly</th>
                                    <th class="w-100">Per Bill</th>
                                    <th class="w-100">Billing Cycle</th>
                                    @foreach ($period as $dt)
                                        @php
                                            $start_date = $dt->format("Y-m-01");
                                                    $end_date = $dt->format("Y-m-t");
                                        @endphp
                                        <th>{{$dt->format("F, Y")}}</th>
                                    @endforeach
                                    <th>Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php
//                                    $product_amount = 0;
//                                    $start_date_invoice = 0;
//                                    $end_date_invoice = 0;
//                                    $billing_cycle = 0;
//                                    $product_amount = 0;
//                                    $product_amount = 0;
                                @endphp
                                @foreach($products as $product)
                                    @php
                                        $start_date_invoice = $product->issue_date;
                                        $end_date_invoice = $product->due_date;
                                        $product_amount = $product->amount;
                                        $billing_cycle = $product->billing_cycle;
                                         if($billing_cycle == 0){
                                                $billing_cycle_name = 'One Time';
                                                $amount_per_monthly = $product_amount;
                                                $total_cost = $product_amount*1;
                                            } elseif($billing_cycle == 12){
                                                $billing_cycle_name = 'Annually';
                                                $total_cost = $product_amount*1;
                                                $amount_per_monthly = $total_cost/12;
                                            }elseif($billing_cycle == 3){
                                                $billing_cycle_name = 'Quarterly';
                                                $total_cost = $product_amount*3;
                                                $amount_per_monthly = $total_cost/12;
                                            }elseif($billing_cycle == 6){
                                                $billing_cycle_name = 'Semi-Annually';
                                                $total_cost = $product_amount*2;
                                                $amount_per_monthly = $total_cost/12;
                                            }elseif($billing_cycle == 1){
                                                $billing_cycle_name = 'Monthly';
                                                $total_cost = $product_amount*12;
                                                $amount_per_monthly = $total_cost/12;

                                            }else{
                                                $billing_cycle_name = 'Nothing';
                                            }
                                    @endphp
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td class="w-100">{{$product->name}}</td>
                                        <td class="w-100">{{$product->SubCategory->name ?? null}}</td>
                                        <td class="w-100">{{number_format($total_cost)}}</td>
                                        <td class="w-100">{{number_format($amount_per_monthly)}}</td>
                                        <td class="w-100">{{number_format($product_amount)}}</td>
                                        <td class="w-100">{{$billing_cycle_name}}</td>

                                        @foreach ($period as $dt)
                                            @php
                                                $start_date = $dt->format("Y-m-01");
                                                $end_date = $dt->format("Y-m-t");

                                                $paid_amount = \App\Models\StatutoryInvoicePayment::getPaidAmountByDate($product_amount,$billing_cycle,$start_date_invoice,$end_date_invoice,$start_date);

                                            @endphp
                                            <th class="text-right">{{number_format($paid_amount)}} <i
                                                    class="{{($paid_amount != 0) ? 'text-success' : 'text-danger'}}  {{($paid_amount != 0) ? 'fa fa-check' : 'fa fa-times'}}"></i>
                                            </th>

                                        @endforeach
                                        <td class="text-right">{{number_format(0)}}</td>

                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
{{--                                <tr>--}}
{{--                                    <td colspan="7">All Total</td>--}}
{{--                                    @php--}}
{{--                                        $total_paid_amount = 0;--}}
{{--                                    @endphp--}}
{{--                                    @foreach ($period as $dt)--}}
{{--                                        @php--}}
{{--                                            $start_date = $dt->format("Y-m-01");--}}
{{--                                                    $end_date = $dt->format("Y-m-t");--}}
{{--                                                    $paid_amount = \App\Models\StatutoryInvoicePayment::getPaidAmountByDate($product_amount,$billing_cycle,$start_date_invoice,$end_date_invoice,$start_date);--}}
{{--                                                    $total_paid_amount += $paid_amount;--}}
{{--                                        @endphp--}}
{{--                                        <th class="text-right">{{number_format($total_paid_amount)}}</th>--}}
{{--                                    @endforeach--}}
{{--                                    <td></td>--}}
{{--                                </tr>--}}

                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection



