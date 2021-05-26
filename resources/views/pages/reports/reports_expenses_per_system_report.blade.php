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
                        <h3 class="block-title">Expense Per System Report</h3>
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
                                    @foreach ($systems as $system)
                                        <th> {{ $system->name }} </th>
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
                                        @foreach($systems as $system)
                                            <?php
                                            $id = $system->id;
                                            $expense = \App\Models\Expense::select([DB::raw("SUM(amount) as total_amount")])->join('supervisors','supervisors.id','=', 'expenses.supervisor_id')->join('systems','systems.id','=', 'supervisors.system_id')->Where('date',$date)->Where('supervisors.system_id',$id)->groupBy('date')->get()->first();
                                            $total_expense_per_day = \App\Models\Expense::Where('date',$date)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first();

                                            ?>
                                            <td class="text-right">{{number_format($expense['total_amount'] ?? 0)}}</td>
                                        @endforeach
                                        <td class="text-right">{{number_format($total_expense_per_day['total_amount'] ?? 0)}}</td>

                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th colspan="2"></th>
                                    @foreach ($systems as $system)
                                        <?php
                                        $id = $system->id;
                                        $total_expense_by_supervisor = \App\Models\Expense::select([DB::raw("SUM(expenses.amount) as total_amount")])->join('supervisors','supervisors.id','=', 'expenses.supervisor_id')->join('systems','systems.id','=', 'supervisors.system_id')->whereBetween('expenses.date', [$start_date, $end_date])->Where('supervisors.system_id',$id)->groupBy('supervisors.system_id')->get()->first();
                                        //$total_expense_by_supervisor = \App\Models\Expense::Where('supervisor_id',$supervisor->id)->whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(amount) as total_amount")])->groupBy('supervisor_id')->get()->first();
                                        ?>
                                        <td class="text-right">{{number_format($total_expense_by_supervisor['total_amount'] ?? 0)}}</td>
                                    @endforeach
                                    <?php
                                    $total_expense_by_all_supervisor = \App\Models\Expense::whereBetween('date', [$start_date, $end_date])->select([DB::raw("SUM(amount) as total_amount")])->get()->first();
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



