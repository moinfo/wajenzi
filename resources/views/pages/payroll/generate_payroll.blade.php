@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Payroll
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-gd-dusk">
                        <h3 class="block-title">Payroll Record</h3>
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
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full" data-ordering="false">
                                <thead>
                                <tr>
                                    <th>Monthly</th>
                                    <th>Payroll No.</th>
                                    <th>Basic Salary</th>
                                    <th>SDL</th>
                                    <th>NSSF</th>
                                    <th>Net Salary</th>
                                    <th>Is Created</th>
                                    <th>Status</th>
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
                                            $payroll = \App\Models\PayrollRecord::getPayrollRecord($start_date,$end_date);
                                            $basic = \App\Models\PayrollRecord::getTotalBasicSalary($start_date,$end_date);
                                            $sdl = \App\Models\PayrollRecord::getTotalSDL($start_date,$end_date);
                                            $nssf = \App\Models\PayrollRecord::getTotalNSSF($start_date,$end_date);
                                            $net = \App\Models\PayrollRecord::getTotalNet($start_date,$end_date);
                                            $status = $payroll->status ?? 'NOT YET CREATED'
                                        @endphp
                                    <tr>

                                        <td>{{$dt->format("F, Y")}}</td>


                                        <td>{{$payroll->payroll_number ?? null}}</td>
                                        <td class="text-right">{{number_format($basic)}}</td>
                                        <td class="text-right">{{number_format($sdl)}}</td>
                                        <td class="text-right">{{number_format($nssf)}}</td>
                                        <td class="text-right">{{number_format($net)}}</td>
                                        <td>
                                            @if($status == 'PENDING')
                                                <div class="badge badge-warning">{{ $status}}</div>
                                            @elseif($status == 'APPROVED')
                                                <div class="badge badge-primary">{{ $status}}</div>
                                            @elseif($status == 'REJECTED')
                                                <div class="badge badge-danger">{{ $status}}</div>
                                            @elseif($status == 'PAID')
                                                <div class="badge badge-primary">{{ $status}}</div>
                                            @elseif($status == 'COMPLETED')
                                                <div class="badge badge-success">{{ $status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $status}}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{route('payroll_view',['month' => $dt->format("m"),'year'=>$dt->format("Y")])}}"><i class="fa fa-eye"></i>View</a>

                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
{{--                                    <tr>--}}
{{--                                        <td></td>--}}
{{--                                        @foreach($sub_categories as $sub_category)--}}
{{--                                            @php--}}
{{--                                                $payment = \App\Models\StatutoryPayment::getTotalPaymentBySubCategory($sub_category->id,$start_date_year,$end_date_year);--}}
{{--                                            @endphp--}}
{{--                                            <td class="text-right">{{number_format($payment)}}</td>--}}
{{--                                        @endforeach--}}
{{--                                        @php--}}
{{--                                            $total_payment_year = \App\Models\StatutoryPayment::getTotalPayment($start_date_year,$end_date_year);--}}
{{--                                        @endphp--}}
{{--                                        <td class="text-right">{{number_format($total_payment_year)}}</td>--}}
{{--                                    </tr>--}}
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection



