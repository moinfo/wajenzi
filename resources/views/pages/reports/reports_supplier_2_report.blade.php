@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Reports
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-gd-dusk">
                        <h3 class="block-title">Supplier 2 Report</h3>
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
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon3">Suppliers</span>
                                                    </div>
                                                    <select name="supplier_id" id="input-supplier-id" class="form-control select2" aria-describedby="basic-addon3">
                                                        <option value="">Select Supplier</option>
                                                        @foreach ($suppliers as $supplier)
                                                            <option value="{{ $supplier->id }}"> {{ $supplier->name }} </option>
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
                        @php
                                $start_date = $_POST['start_date'] ?? date('Y-m-01');
                                $end_date = $_POST['end_date'] ?? date('Y-m-d');
                                $today_date = date('Y-m-d');
                                $supplier_id = $_POST['supplier_id'] ?? 0;
                                $supplier_name = \App\Models\Supplier::getSupplierName($supplier_id);
                                $bonge_id = \App\Models\Supplier::getBongeSupplierId($supplier_id);
                                $current_balance = \App\Models\BankReconciliation::getSupplierCurrentBalanceWithoutCharges($supplier_id,$end_date,$bonge_id) ?? 0;
                                $opening_balance = \App\Models\BankReconciliation::getSupplierOpeningBalanceWithoutCharges($supplier_id,$start_date,$bonge_id) ?? 0;
                                $transactions = \App\Models\BankReconciliation::getSupplier2Transactions($start_date,$end_date,$supplier_id,$bonge_id);
                                $total_credit = 0;
                                $total_debit = 0;
                                $total_transfer_in = 0;
                        @endphp
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full"  data-ordering="false">
                                <thead>
                                <tr>
                                    <td colspan="3">Date: <b class="float-right">{{$start_date}} - {{$end_date}}</b></td>
                                    <td colspan="2">Supplier: <b class="float-right">{{$supplier_name}}</b></td>
                                    <td colspan="2">Current Balance: <b class="float-right">{{number_format($current_balance,2)}}</b></td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-right">Opening Balance:</td>
                                    <td class="text-right">{{number_format($opening_balance,2)}}</td>
                                </tr>
                                    <tr>
                                        <td>#</td>
                                        <td>date</td>
                                        <td>Description</td>
{{--                                        <td>Efd</td>--}}
                                        <td>Credit</td>
                                        <td>Debit</td>
{{--                                        <td>Charge</td>--}}
                                        <td>Transfer In</td>
{{--                                        <td>Transfer Out</td>--}}
                                        <td>Balance</td>
                                    </tr>
                                </thead>
                                <tbody>

                                @foreach($transactions as $transaction)
                                    @php
                                        $opening_balance -= $transaction->debit;
                                        $opening_balance += $transaction->transfer_in;
                                        $opening_balance += $transaction->credit;
                                        $efd = \App\Models\Efd::where('id',$transaction->efd_id)->get()->first()['name'];
                                        $receiving_id = $transaction->receiving_id;
                                        $receiving_items = \App\Models\Report::getReceivingItems($receiving_id);
                                        $items_name = implode(', ', array_column($receiving_items, 'name'));
                                        $total_credit += $transaction->credit;
                                        $total_debit += $transaction->debit;
                                        $total_transfer_in += $transaction->transfer_in;
                                    @endphp
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$transaction->date}}</td>
                                        <td>{{$transaction->description. '  '. $transaction->bank_name . '  '.$items_name }}</td>
{{--                                        <td>{{$efd}}</td>--}}
                                        <td class="text-right">{{number_format($transaction->credit,2)}}</td>
                                        <td class="text-right">{{number_format($transaction->debit,2)}}</td>
{{--                                        <td class="text-right">{{number_format($transaction->amount,2)}}</td>--}}
                                        <td class="text-right">{{number_format($transaction->transfer_in,2)}}</td>
{{--                                        <td class="text-right">{{number_format($transaction->transfer_out,2)}}</td>--}}
                                        <td class="text-right">{{number_format($opening_balance,2)}}</td>
                                    </tr>

                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th colspan="3">Total</th>
                                    <th class="text-right">{{number_format($total_credit,2)}}</th>
                                    <th class="text-right">{{number_format($total_debit,2)}}</th>
                                    <th class="text-right">{{number_format($total_transfer_in,2)}}</th>
                                    <th class="text-right"></th>
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




