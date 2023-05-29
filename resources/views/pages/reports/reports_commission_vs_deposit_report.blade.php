@extends('layouts.backend')
@section('css_before')
    <!-- Page JS Plugins CSS -->
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables/dataTables.bootstrap4.css') }}">
@endsection

@section('js_after')

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
                        <h3 class="block-title">Commission VS Deposit Report</h3>
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
                                    <th class="text-center">#</th>
                                    <th>Supplier</th>
                                    <th>Commission</th>
                                    <th>Deposit</th>
                                    <th>Difference</th>
                                </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $start_date = $_POST['start_date'] ?? date('Y-m-01');
                                        $end_date = $_POST['end_date'] ?? date('Y-m-d');
                                        $commission_total = 0;
                                        $deposit_total = 0;
                                        $difference_total = 0;
                                    @endphp
                                    @foreach($suppliers as $supplier)
                                        @php
                                            $supplier_id = $supplier->id;
                                            $commission = App\Models\SupplierTarget::getTotalSupplierCommissionWithDeposit($supplier_id,$start_date,$end_date);
                                            $commission_total += $commission;
                                            $deposit = App\Models\BankReconciliation::getTotalSupplierDepositByCommission($supplier_id,$start_date,$end_date);
                                            $deposit_total += $deposit;
                                            $difference = $commission - $deposit;
                                            $difference_total += $difference;

                                        @endphp
                                        @if($commission != 0)
                                        <tr>
                                            <td>{{$loop->iteration}}</td>
                                            <td>{{$supplier->name}}</td>
                                            <td class="text-right">{{number_format($commission)}}</td>
                                            <td class="text-right">{{number_format($deposit)}}</td>
                                            <td class="text-right">{{number_format($difference)}}</td>
                                        </tr>
                                        @endif
                                    @endforeach
                                <foot>
                                    <tr>
                                        <th colspan="2">TOTAL</th>
                                        <th style="display: none"></th>
                                        <th class="text-right">{{number_format($commission_total)}}</th>
                                        <th class="text-right">{{number_format($deposit_total)}}</th>
                                        <th class="text-right">{{number_format($difference_total)}}</th>
                                    </tr>
                                </foot>

                                </tbody>
                                <tfoot>

                                </tfoot>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection



