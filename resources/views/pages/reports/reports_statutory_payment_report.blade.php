@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Reports
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-gd-dusk">
                        <h3 class="block-title">Statutory Sub Category Report</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="collection_search" action="" id="filter-form" method="post" autocomplete="off">
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
                                                        <option value="2022" selected>2022</option>
                                                        <option value="2023">2023</option>
                                                        <option value="2024">2024</option>
                                                    </select>
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
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full" data-ordering="false" data-sorting="false">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    @foreach($sub_categories as $sub_category)
                                    <th>{{$sub_category->name ?? null}}</th>
                                    @endforeach
                                    <th>Total</th>
                                </tr>
                                </thead>
                                <tbody>
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
                                    @foreach ($period as $dt)
                                        @php
                                            $start_date = $dt->format("Y-m-01");
                                            $end_date = $dt->format("Y-m-t");
                                        @endphp
                                    <tr>

                                        <td>{{$dt->format("F, Y")}}</td>
                                        @foreach($sub_categories as $sub_category)
                                            @php
                                                $payment = \App\Models\StatutoryPayment::getTotalPaymentBySubCategory($sub_category->id,$start_date,$end_date);
                                                $total_payment_per_monthly += $payment;
                                            @endphp
                                            <td class="text-right">
                                                <a onclick="loadFormModal('statutory_payment_per_sub_category_form', {className: 'StatutoryInvoicePayment',status:'APPROVED',sub_category_id:'{{$sub_category->id}}', start_date:'{{$start_date}}',end_date:'{{$end_date}}',model_type:'date_range',key_name:'invoice_payments.date' }, ' Statutory Payment For {{$start_date}} - {{$end_date}}', 'modal-lg');"
                                                   class=" js-tooltip-enabled"
                                                   data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    {{number_format($payment)}}</a></td>
                                        @endforeach
                                        @php
                                            $total_payment = \App\Models\StatutoryPayment::getTotalPayment($start_date,$end_date);
                                        @endphp
                                        <td class="text-right">{{number_format($total_payment)}}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td></td>
                                        @foreach($sub_categories as $sub_category)
                                            @php
                                                $payment = \App\Models\StatutoryPayment::getTotalPaymentBySubCategory($sub_category->id,$start_date_year,$end_date_year);
                                            @endphp
                                            <td class="text-right">{{number_format($payment)}}</td>
                                        @endforeach
                                        @php
                                            $total_payment_year = \App\Models\StatutoryPayment::getTotalPayment($start_date_year,$end_date_year);
                                        @endphp
                                        <td class="text-right">{{number_format($total_payment_year)}}</td>
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



