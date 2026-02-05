@extends('layouts.backend')

@section('content')

    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Reports
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-wajenzi-gradient">
                        <h3 class="block-title">Annually Expense Sub Categories Summary Report</h3>
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
                                    @foreach($sub_categories as $sub_category)
                                        <th>{{$sub_category->name}}</th>
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
                                            $total_charges = 0;
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
                                                $sub_category_id = $sub_category->id;
                                                    $charges = \App\Models\Expense::getTotalExpensesGroupBySubExpensesCategory($start_date,$end_date,$sub_category_id);
                                                   $total_charges += $charges;
                                            @endphp
                                            <td class="text-right">{{number_format($charges)}}</td>
                                        @endforeach
                                        @php
                                            $all_charges = \App\Models\Expense::getTotalExpensesGroupBySubExpensesCategory($start_date,$end_date,null);
                                        @endphp
                                        <td class="text-right">{{number_format($all_charges)}}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td>Total</td>
                                    @foreach($sub_categories as $sub_category)
                                        @php
                                            $sub_category_id = $sub_category->id;
                                                $charges = \App\Models\Expense::getTotalExpensesGroupBySubExpensesCategory($start_date_year,$end_date_year,$sub_category_id);
                                        @endphp
                                        <td class="text-right">{{number_format($charges)}}</td>
                                    @endforeach
                                    @php
                                        $total_all_charges = \App\Models\Expense::getTotalExpensesGroupBySubExpensesCategory($start_date_year,$end_date_year,null);
                                    @endphp
                                    <td class="text-right">{{number_format($total_all_charges)}}</td>
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



