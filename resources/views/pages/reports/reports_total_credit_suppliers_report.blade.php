@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">Reports
        </div>
        <div>
            <div class="block block-themed">
                <div class="block-header bg-gd-dusk">
                    <h3 class="block-title">Total Credit Supplier Report</h3>
                </div>
                <div class="block-content">
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Supplier Name</th>
                            <th>Credit</th>
                            <th>Debit</th>
                            <th>Balance</th>
                            <th>System Supplier Type</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php
                        $total_credit = 0;
                        $total_debit = 0;
                        $total_balance = 0;
                        @endphp
                        @foreach($suppliers as $supplier)
                            @php
                            if ($supplier->supplier_depend_on_system == 'WHITESTAR'){
                                $credit = \App\Models\Supplier::getWhitestarSupplierWithCredit($supplier->whitestar_supplier_id);
                                $debit_cash = \App\Models\Supplier::getWhitestarSupplierWithDebitInCash($supplier->whitestar_supplier_id);
                            }else{
                                 $credit = \App\Models\Supplier::getBongeSupplierWithCredit($supplier->whitestar_supplier_id);
                            }
                            $total_credit += $credit;
                            $debit = \App\Models\Supplier::getLemuruSupplierWithDebitWithoutTransfer($supplier->id) + \App\Models\Supplier::getLemuruSupplierWithDebitWithTransfer($supplier->id) + $supplier->debit;
                            $total_debit += $debit;
                            $balance = $credit - $debit;
                            $total_balance += $balance;
                            @endphp
                            @if($balance != 0)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$supplier->name}}</td>
                                <td class="text-right">{{number_format($credit)}}</td>
                                <td class="text-right">{{number_format($debit)}}</td>
                                <td class="text-right">{{number_format($balance)}}</td>
                                <td>{{$supplier->supplier_depend_on_system}}</td>
                            </tr>
                            @endif
                        @endforeach

                        </tbody>
                        <tfoot>
                        <tr>
                            <td></td>
                            <td></td>
                            <td class="text-right">{{number_format($total_credit)}}</td>
                            <td class="text-right">{{number_format($total_debit)}}</td>
                            <td class="text-right">{{number_format($total_balance)}}</td>
                            <td></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
