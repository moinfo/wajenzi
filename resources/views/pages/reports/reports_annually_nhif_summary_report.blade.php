@extends('layouts.backend')

@section('content')

    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Reports
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-wajenzi-gradient">
                        <h3 class="block-title">Annually NHIF Summary Report</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                @include('components.headed_paper')
                                <br/>
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
                                    <th>Employer</th>
                                    <th>Employee</th>
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
                                            $total_employee = 0;
                                            $total_employer = 0;
                                            $total_total = 0;

                                @endphp
                                @foreach ($period as $dt)
                                    @php
                                        $start_date = $dt->format("Y-m-01");
                                        $end_date = $dt->format("Y-m-t");
                                        $efd_id = null;
                                        $employee = \App\Models\PayrollRecord::getTotalNHIFEmployee($start_date,$end_date) ?? 0;
                                        $total_employee += $employee;
                                        $employer = \App\Models\PayrollRecord::getTotalNHIFEmployer($start_date,$end_date) ?? 0;
                                        $total_employee += $employer;
                                        $total = $employee + $employer;
                                        $total_total = $total_employee + $total_employee;
                                    @endphp
                                    <tr>

                                        <td>{{$dt->format("F, Y")}}</td>
                                        <td class="text-right">{{number_format($employer)}}</td>
                                        <td class="text-right">{{number_format($employee)}}</td>
                                        <td class="text-right">{{number_format($total)}}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td>Total</td>
                                    <td class="text-right">{{number_format($total_employer)}}</td>
                                    <td class="text-right">{{number_format($total_employee)}}</td>
                                    <td class="text-right">{{number_format($total_total)}}</td>
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



