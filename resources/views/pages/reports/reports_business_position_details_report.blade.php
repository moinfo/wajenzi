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
                        <h3 class="block-title">Busines Position Report</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="gross_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Start Date</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">
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
                                        <th>#</th>
                                        <th>System</th>
                                        <th>Cash</th>
                                        <th>Credit</th>
                                        <th>Inventory</th>
                                        <th>Total</th>
                                        <th>Supplier Credit</th>
                                        <th>Capital</th>
                                        <th>Total</th>
                                        <th>Difference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                use Illuminate\Support\Facades\DB;
                                $start_date = $_POST['start_date'] ?? date('Y-m-d');
                                $end_date = $_POST['end_date'] ?? date('Y-m-d');

                                    $systems = \App\Models\System::all();
                                $total_cash = 0;
                                $total_credit = 0;
                                $total_inventory = 0;
                                $total_capital = 0;
                                $total_supplier_credit = 0;
                                $total_total_out = 0;
                                $total_difference = 0;
                                $total_total_in = 0;
                                ?>
                                @foreach($systems as $system)
                                    <?php
                                        $system_id = $system->id;
                                    $cash = \App\Models\SystemCash::getTotalCashForSystem($start_date,$end_date,$system_id);
                                    $credit = \App\Models\SystemCredit::getTotalCreditForSystem($start_date,$end_date,$system_id);
                                    $inventory = \App\Models\SystemInventory::getTotalInventoryForSystem($start_date,$end_date,$system_id);
                                    $capital = \App\Models\SystemCapital::getTotalCapitalForSystem($start_date,$end_date,$system_id);
                                    $supplier_credit = \App\Models\SupplierReceiving::getSystemSupplierReceivingAmount($system_id,$end_date) - \App\Models\TransactionMovement::getSystemSupplierTransactionAmount($system_id,$end_date);
                                    $total_in = $cash + $credit + $inventory;
                                    $total_out = $supplier_credit + $capital;
                                    $difference = $total_in - $total_out;
                                    $total_cash += $cash;
                                    $total_credit += $credit;
                                    $total_inventory += $inventory;
                                    $total_capital  += $capital ;
                                    $total_supplier_credit += $supplier_credit;
                                    $total_total_out  += $total_out ;
                                    $total_total_in  += $total_in;
                                    $total_difference += $difference;
                                    ?>
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$system->name}}</td>
                                        <td class="text-right">{{number_format($cash)}}</td>
                                        <td class="text-right">{{number_format($credit)}}</td>
                                        <td class="text-right">{{number_format($inventory)}}</td>
                                        <td class="text-right">{{number_format($total_in)}}</td>
                                        <td class="text-right">{{number_format($supplier_credit)}}</td>
                                        <td class="text-right">{{number_format($capital)}}</td>
                                        <td class="text-right">{{number_format($total_out)}}</td>
                                        <td class="text-right">{{number_format($difference)}}</td>

                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th></th>
                                        <th></th>
                                        <th class="text-right">{{number_format($total_cash)}}</th>
                                        <th class="text-right">{{number_format($total_credit)}}</th>
                                        <th class="text-right">{{number_format($total_inventory)}}</th>
                                        <th class="text-right">{{number_format($total_total_in)}}</th>
                                        <th class="text-right">{{number_format($total_supplier_credit)}}</th>
                                        <th class="text-right">{{number_format($total_capital)}}</th>
                                        <th class="text-right">{{number_format($total_total_out)}}</th>
                                        <th class="text-right">{{number_format($total_difference)}}</th>
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



