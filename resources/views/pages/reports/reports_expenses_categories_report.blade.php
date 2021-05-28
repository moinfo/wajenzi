@extends('layouts.backend')
@section('css_before')
    <!-- Page JS Plugins CSS -->
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables/dataTables.bootstrap4.css') }}">
@endsection

@section('js_after')
    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>

    <!-- Page JS Code -->
    <script src="{{ asset('js/pages/tables_datatables.js') }}"></script>

    <script>
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd'
        });
    </script>
@endsection
@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Reports
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-gd-dusk">
                        <h3 class="block-title">Expenses Categories Report</h3>
                    </div>
                    <div class="block-content">
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
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Date</th>
                                    @foreach ($expenses_categories as $expenses_category)
                                        <th> {{ $expenses_category->name }} </th>
                                    @endforeach
                                    <th>Total Expense</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $start_date = $_POST['start_date'] ?? date('Y-m-01');
                                $end_date = $_POST['end_date'] ?? date('Y-m-d');
                                $first_date = explode("-", $start_date);
                                $last_date = explode("-", $end_date);
                                $first_month = $first_date[1];
                                use Illuminate\Support\Facades\DB;
                                for($i = $first_date[2]; $i <=  $last_date[2]; $i++)
                                {
                                    // add the date to the dates array
                                    $dates[] = date('Y') . "-" . $first_month . "-" . str_pad($i, 2, '0', STR_PAD_LEFT);
                                }
                                ?>
                                @foreach(array_reverse($dates) as $date)
                                    <tr>
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td>{{ $date }}</td>
                                        @foreach($expenses_categories as $expenses_category)
                                            <?php
                                            $id = $expenses_category->id;
                                            $expense = \App\Models\Expense::
                                                join('expenses_sub_categories', 'expenses_sub_categories.id', '=', 'expenses.expenses_sub_category_id')
                                                ->join('expenses_categories', 'expenses_categories.id', '=', 'expenses_sub_categories.expenses_category_id')
                                               ->Where('expenses.date',$date)->Where('expenses.expenses_sub_category_id',$id)->select([DB::raw("SUM(expenses.amount) as total_amount")])->groupBy('expenses.date')->get()->first();
                                            $total_expense_per_day = \App\Models\Expense::Where('date',$date)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first();

                                            ?>
                                            <td class="text-right">{{number_format($expense['total_amount'] ?? 0)}}</td>
                                        @endforeach
                                        <td class="text-right">{{number_format($total_expense_per_day['total_amount']  ?? 0)}}</td>

                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th colspan="2"></th>
                                    @foreach ($expenses_categories as $expenses_category)
                                        <?php
                                        $total_expense_by_expenses_category = \App\Models\Expense::
                                        join('expenses_sub_categories', 'expenses_sub_categories.id', '=', 'expenses.expenses_sub_category_id')
                                            ->join('expenses_categories', 'expenses_categories.id', '=', 'expenses_sub_categories.expenses_category_id')

                                        ->Where('expenses_sub_category_id',$expenses_category->id)->whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(amount) as total_amount")])->groupBy('expenses_sub_categories.expenses_category_id')->get()->first();
                                        ?>
                                        <td class="text-right">{{number_format($total_expense_by_expenses_category['total_amount'] ?? 0)}}</td>
                                    @endforeach
                                    <?php
                                    $total_expense_by_all_expenses_category = \App\Models\Expense::whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(amount) as total_amount")])->get()->first();
                                    ?>
                                    <td class="text-right">{{number_format($total_expense_by_all_expenses_category['total_amount']  ?? 0)}}</td>
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



