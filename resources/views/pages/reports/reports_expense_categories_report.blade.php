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
                                    <div class="row">
                                        <div class="col-md-12 text-right">
                                            <a href="{{route('reports_auto_expenses_report')}}" class="btn btn-alt-secondary">Auto Expenses Report</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="block-header text-center">
                                    <h3 class="block-title">Auto Expense Categories Report</h3>
                                </div>
                            </div>
                        </div>
                        <br/>
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="expense_search" action="" id="filter-form" method="post" autocomplete="off">
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
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon2">Drivers</span>
                                                    </div>
                                                    <select name="driver_id" id="driver_id" class="form-control">
                                                        <option value="0">All</option>
                                                        @foreach($drivers as $driver)
                                                            <option value="{{$driver->id}}">{{$driver->name}}</option>
                                                        @endforeach
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
                                    <th class="text-center">#</th>
                                    <th>Date</th>
                                    @foreach($expense_categories as $expense_category)
                                    <th>{{$expense_category->category_name}}</th>
                                    @endforeach
                                    <th>Total Expenses</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php
                                $driver_id = $_POST['driver_id'] ?? 0;
                                @endphp
                                    @foreach(array_reverse($dates) as $date)
                                        <tr>
                                            <td>{{$loop->iteration}}</td>
                                            <td>{{$date}}</td>
                                            @php
                                            $total_expenses_by_supervisor = 0;
                                            @endphp
                                            @foreach($expense_categories as $expense_category)
                                                @php
                                                $category_id = $expense_category->expense_category_id;
                                                $expenses = \App\Models\Report::getTotalExpensesByCategory($category_id,$date,$date,$driver_id);
                                                $total_expenses_by_supervisor += $expenses;
                                                $key_name1 = 'ospos_expenses.deleted'

                                                @endphp
                                                <td class="text-right">
                                                    <a onclick="loadFormModal('auto_expenses_per_day_form', {className: 'BongeExpense',expense_category_id:'{{$category_id}}', date_find:'{{$date}}', id:'0', key_name:'{{$key_name1}}' }, 'All Bonge Expense category From {{$date}} to {{$date}}', 'modal-md');"
                                                       class=" js-tooltip-enabled">
                                                    {{number_format($expenses)}}
                                                    </a>
                                                </td>
                                            @endforeach
                                            <th class="text-right">{{number_format($total_expenses_by_supervisor)}}</th>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    @php
                                        $total_expenses_by_date = 0;
                                    @endphp
                                    @foreach($expense_categories as $expense_category)
                                    @php
                                            $category_id = $expense_category->expense_category_id;
                                            $total_expenses = \App\Models\Report::getTotalExpensesByCategory($category_id,reset($dates),end($dates),$driver_id);
                                            $total_expenses_by_date += $total_expenses;
                                        @endphp
                                        <th class="text-right">{{number_format($total_expenses)}}</th>
                                    @endforeach
                                    <th class="text-right">{{number_format($total_expenses_by_date)}}</th>
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



