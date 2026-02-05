@extends('layouts.backend')

@section('content')

    <div class="container-fluid">
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
                                            <div class="class col-md-2">
                                                <div>
                                                    <button type="submit" name="submit"  class="btn btn-sm btn-primary">Show</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="block-header text-center">
                                    <h3 class="block-title">Expenses Report</h3>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Date</th>
                                    @foreach ($supervisors as $supervisor)
                                       <th> {{ $supervisor->name }} </th>
                                    @endforeach
                                    <th>Total Expense</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                    $start_date = $_POST['start_date'] ?? date('Y-m-01');
                                    $end_date = $_POST['end_date'] ?? date('Y-m-d');
                                $period = new DatePeriod(new DateTime("$start_date"), new DateInterval('P1D'), new DateTime("$end_date +1 day"));
                                foreach ($period as $date) {
                                    $dates[] = $date->format("Y-m-d");
                                }
                                    ?>
                                @foreach(array_reverse($dates) as $date)
                                    <tr>
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td>{{ $date }}</td>
                                        @foreach($supervisors as $supervisor)
                                            <?php
                                            $key_name = 'supervisor_id';
                                            $id = $supervisor->id;
                                           $expense = \App\Models\Expense::Where('status','APPROVED')->Where('date',$date)->Where('supervisor_id',$id)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first();
                                           $total_expense_per_day = \App\Models\Expense::Where('status','APPROVED')->Where('date',$date)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first();

                                            ?>
                                            <td class="text-right">
                                                <a onclick="loadFormModal('expenses_per_supervisor_form', {className: 'Expense', date_find:'{{$date}}',  key_name:'{{$key_name}}', id: {{$id}} }, '{{$supervisor->name}} Expenses For {{$date}}', 'modal-md');"
                                                   class=" js-tooltip-enabled"
                                                   data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    {{number_format($expense['total_amount'] ?? 0)}}</a></td>
                                        @endforeach
                                        <td class="text-right">
                                            <a onclick="loadFormModal('expenses_per_day_form', {className: 'Expense', date_find:'{{$date}}' }, 'All Expenses For {{$date}}', 'modal-md');"
                                               class=" js-tooltip-enabled">
                                                {{number_format($total_expense_per_day['total_amount'] ?? 0)}}</a></td>

                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th colspan="2"></th>
                                    @foreach ($supervisors as $supervisor)
                                        <?php
                                        $total_expense_by_supervisor = \App\Models\Expense::Where('status','APPROVED')->Where('supervisor_id',$supervisor->id)->whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(amount) as total_amount")])->groupBy('supervisor_id')->get()->first();
                                        ?>
                                        <td class="text-right">{{number_format($total_expense_by_supervisor['total_amount'] ?? 0)}}</td>
                                    @endforeach
                                    <?php
                                    $total_expense_by_all_supervisor = \App\Models\Expense::Where('status','APPROVED')->whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(amount) as total_amount")])->get()->first();
                                    ?>
                                    <td class="text-right">{{number_format($total_expense_by_all_supervisor['total_amount'] ?? 0)}}</td>
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



