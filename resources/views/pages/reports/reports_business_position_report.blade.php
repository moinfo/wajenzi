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
                                        <th class="text-center" colspan="2">IN</th>
                                        <th class="text-center" colspan="2">OUT</th>
                                    </tr>
                                    <tr>
                                        <td>Item</td>
                                        <td>Amount</td>
                                        <td>Item</td>
                                        <td>Amount</td>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                use Illuminate\Support\Facades\DB;
                                $start_date = $_POST['start_date'] ?? date('Y-m-d');
                                $end_date = $_POST['end_date'] ?? date('Y-m-d');
                                $cash = \App\Models\Report::getTotalCashForSpecificDate($start_date,$end_date);
                                $credit = \App\Models\Report::getTotalCreditForSpecificDate($start_date,$end_date);
                                $inventory = \App\Models\Report::getTotalInventoryForSpecificDate($start_date,$end_date);
                                $capital = \App\Models\Report::getTotalCapitalForSpecificDate($start_date,$end_date);
                                $supplier_credit = \App\Models\Report::getTotalSupplierBalance($end_date);
                                $supplier_credit1 = \App\Models\SupplierReceiving::getAllSupplierReceivingAmount($end_date);
                                $supplier_credit2 = \App\Models\TransactionMovement::getAllSupplierTransactionAmount($end_date);
                                $total_in = $cash + $credit + $inventory;
                                $total_out = $supplier_credit + $capital;
                                $difference = $total_in - $total_out
                                ?>
                                    <tr>
                                        <td>Cash</td>
                                        <td class="text-right">{{number_format($cash)}}</td>
                                        <td>Supplier Credit</td>
                                        <td class="text-right">{{number_format($supplier_credit)}}</td>
                                    </tr>
                                    <tr>
                                        <td>Credit</td>
                                        <td class="text-right">{{number_format($credit)}}</td>
                                        <td>Capital</td>
                                        <td class="text-right">{{number_format($capital)}}</td>
                                    </tr>
                                    <tr>
                                        <td>Inventory</td>
                                        <td class="text-right">{{number_format($inventory)}}</td>
                                        <td></td>
                                        <td class="text-right"></td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th  class="text-right" colspan="2">{{number_format($total_in)}}</th>
                                        <th class="text-right" colspan="2">{{number_format($total_out)}}</th>
                                    </tr>
                                    <tr>
                                        <th class="text-right" colspan="4">{{number_format($difference)}}</th>
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



