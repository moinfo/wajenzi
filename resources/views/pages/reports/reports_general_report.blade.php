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
                        <h3 class="block-title">General Report</h3>
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
                                    @foreach ($supervisors as $supervisor)
                                        <th> {{ $supervisor->name }} </th>
                                    @endforeach
                                    <th>Total Expenses</th>
                                    <th>Total Supplier Receiving</th>
                                    <th>Total Collections</th>
                                    <th>Total Transactions</th>
                                    <th>Total Balance</th>
                                    <th>Opening</th>
                                    <th>Closing</th>
                                    <th>Total Gross Profits</th>
                                    <th>Total Net Profit/Loss</th>
                                    <th>Total Actual Profit/Loss</th>
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
                                        <?php
                                        $sum = 0;
                                        ?>
                                        @foreach($supervisors as $supervisor)
                                            <?php
                                            $id = $supervisor->id;
                                            $yesterday = date('Y-m-d', strtotime('-1 day', strtotime($date)));
                                            $expense = \App\Models\Supervisor::getTotalSupervisorExpensesByDate($date,$id);
                                            $total_expense_per_day = \App\Models\Supervisor::getTotalSupervisorExpensesByDateAsSupervisorOnly($date);
                                            $total_expenses_for_all_per_day = \App\Models\Supervisor::getTotalSupervisorExpensesPerDay($date);
                                            $total_gross_profit_per_day = \App\Models\Gross::getTotalGrossProfitPerDay($date);
                                            $total_collection_per_day = \App\Models\Collection::getTotalCollectionPerDay($date);
                                            $total_collection_yesterday = \App\Models\Collection::getTotalCollectionPerDay($yesterday);
                                            $total_transaction_per_day = \App\Models\TransactionMovement::getTotalTransactionPerDay($date);
                                            $total_transaction_yesterday = \App\Models\TransactionMovement::getTotalTransactionPerDay($yesterday);
                                            $total_supplier_receiving_per_day = \App\Models\SupplierReceiving::getTotalSupplierReceivingPerDay($date);
                                            //$expense = \App\Models\Expense::select([DB::raw("SUM(amount) as total_amount")])->join('supervisors', 'supervisors.id', '=','expenses.supervisor_id')->Where('date',$date)->Where('supervisor_id',$id)->Where('employee_id',1)->groupBy('date')->get()->first();
                                            $total = (($total_collection_per_day )-($total_transaction_per_day )) + (($total_collection_yesterday )-($total_transaction_yesterday ));
                                            $sum +=$total;
                                            $opening = \App\Models\Report::getOpening($date);
                                            ?>
                                            <td class="text-right">{{number_format($expense)}}</td>
                                        @endforeach
                                        <td class="text-right">{{number_format($total_expense_per_day )}}</td>
                                        <td class="text-right">{{number_format($total_supplier_receiving_per_day )}}</td>
                                        <td class="text-right">{{number_format($total_collection_per_day )}}</td>
                                        <td class="text-right">{{number_format($total_transaction_per_day )}}</td>
                                        <td class="text-right">{{number_format( (($total_collection_per_day )-($total_transaction_per_day )) )}}</td>
                                        <td class="text-right">{{number_format($opening) }}</td>
                                        <td class="text-right">{{number_format( (($total_collection_per_day )-($total_transaction_per_day )) + ($opening) )}}</td>
                                        <td class="text-right">{{number_format($total_gross_profit_per_day )}}</td>
                                        <td class="text-right">{{number_format(($total_gross_profit_per_day  )-($total_expense_per_day ))}}</td>
                                        <td class="text-right">{{number_format(($total_gross_profit_per_day  )-($total_expenses_for_all_per_day ))}}</td>

                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th colspan="2"></th>
                                    @foreach ($supervisors as $supervisor)
                                        <?php
                                        $total_expense_by_supervisor = \App\Models\Supervisor::getSumOfExpensesBySupervisorForSpecificDate($supervisor->id,$start_date,$end_date);
                                        ?>
                                        <td class="text-right">{{number_format($total_expense_by_supervisor)}}</td>
                                    @endforeach
                                    <?php
                                    $total_gross_profit_by_supervisor = \App\Models\Gross::getTotalGrossProfitBySupervisorForSpecificDate($start_date, $end_date);
                                    $total_collection_by_supervisor = \App\Models\Collection::getTotalCollectionToAllSupervisors($start_date, $end_date);
                                    $total_transaction_by_supervisor = \App\Models\TransactionMovement::getTotalTransactionToAllSupplier($start_date, $end_date);
                                    $total_supplier_receiving_by_supervisor = \App\Models\SupplierReceiving::getTotalSupplierReceivingToAllSuppliers($start_date, $end_date);
                                    $total_expenses_for_all_by_supervisor = \App\Models\Supervisor::getSumSupervisorExpensesByDateAsSupervisorOnly($start_date, $end_date);
                                    $total_expense_by_all_supervisor = \App\Models\Supervisor::getSumSupervisorExpensesPerDateGiven($start_date, $end_date);
                                    ?>
                                    <td class="text-right">{{number_format($total_expense_by_all_supervisor)}}</td>
                                    <td class="text-right">{{number_format($total_supplier_receiving_by_supervisor)}}</td>
                                    <td class="text-right">{{number_format($total_collection_by_supervisor)}}</td>
                                    <td class="text-right">{{number_format($total_transaction_by_supervisor)}}</td>
                                    <td class="text-right"></td>
                                    <td></td>
                                    <td class="text-right">{{number_format($total_gross_profit_by_supervisor)}}</td>
                                    <td class="text-right"></td>
                                    <td class="text-right">{{number_format($total_gross_profit_by_supervisor-$total_expense_by_all_supervisor)}}</td>
                                    <td class="text-right">{{number_format($total_gross_profit_by_supervisor-$total_expenses_for_all_by_supervisor)}}</td>
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



