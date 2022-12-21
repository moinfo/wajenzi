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
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full" data-ordering="false" data-sorting="false">
                        <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Supplier Name</th>
                            <th>Credit</th>
                            <th>Debit Bank</th>
                            <th>Debit Cash</th>
                            <th>Total Debit</th>
                            <th>Balance</th>
                            <th>System Supplier Type</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php
                        $total_credit_bonge = 0;
                        $total_debit_bonge = 0;
                        $total_balance_bonge = 0;
                        $total_both_debit_bonge = 0;
                        $total_debit_cash_bonge = 0;
                        @endphp
                        @foreach($suppliers_with_bonge as $supplier)
                            @php
                            if ($supplier->supplier_depend_on_system == 'WHITESTAR'){
                                $credit = \App\Models\Supplier::getWhitestarSupplierWithCredit($supplier->whitestar_supplier_id);
                                $debit_cash =  \App\Models\Supplier::getWhitestarSupplierWithDebitInWithdraw($supplier->whitestar_supplier_id);
                            }else{
                                 $credit = \App\Models\Supplier::getBongeSupplierWithCredit($supplier->whitestar_supplier_id);
                                 $debit_cash = 0;
                            }
                            $total_credit_bonge += $credit;
                            $debit = \App\Models\Supplier::getLemuruSupplierWithDebitWithoutTransfer($supplier->id) + \App\Models\Supplier::getLemuruSupplierWithDebitWithTransfer($supplier->id) + $supplier->debit;
                            $total_debit_bonge += $debit;
                            $total_debit_cash_bonge += $debit_cash;
                            $both_debit = $debit+$debit_cash;
                            $total_both_debit_bonge += $both_debit;
                            $balance = $credit - $both_debit;
                            $total_balance_bonge += $balance;
                            @endphp
                            @if($balance != 0)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$supplier->name}}</td>
                                <td class="text-right">{{number_format($credit)}}</td>
                                <td class="text-right">{{number_format($debit)}}</td>
                                <td class="text-right">{{number_format($debit_cash)}}</td>
                                <td class="text-right">{{number_format($both_debit)}}</td>
                                <td class="text-right">{{number_format($balance)}}</td>
                                <td>{{$supplier->supplier_depend_on_system}}</td>
                            </tr>
                            @endif
                        @endforeach
                        <tr>
                            <th></th>
                            <th>TOTAL</th>
                            <th class="text-right">{{number_format($total_credit_bonge)}}</th>
                            <th class="text-right">{{number_format($total_debit_bonge)}}</th>
                            <th class="text-right">{{number_format($total_debit_cash_bonge)}}</th>
                            <th class="text-right">{{number_format($total_both_debit_bonge)}}</th>
                            <th class="text-right">{{number_format($total_balance_bonge)}}</th>
                            <th></th>
                        </tr>
                        @php
                        $total_credit_whitestar = 0;
                        $total_debit_whitestar = 0;
                        $total_balance_whitestar = 0;
                        $total_both_debit_whitestar = 0;
                        $total_debit_cash_whitestar = 0;
                        @endphp
                        @foreach($suppliers_with_whitestar as $supplier)
                            @php
                            if ($supplier->supplier_depend_on_system == 'WHITESTAR'){
                                $credit = \App\Models\Supplier::getWhitestarSupplierWithCredit($supplier->whitestar_supplier_id);
                                $debit_cash = \App\Models\Supplier::getWhitestarSupplierWithDebitInCash($supplier->whitestar_supplier_id);
                            }else{
                                 $credit = \App\Models\Supplier::getBongeSupplierWithCredit($supplier->whitestar_supplier_id);
                                 $debit_cash = 0;
                            }
                            $total_credit_whitestar += $credit;
                            $debit = \App\Models\Supplier::getLemuruSupplierWithDebitWithoutTransfer($supplier->id) + \App\Models\Supplier::getLemuruSupplierWithDebitWithTransfer($supplier->id) + $supplier->debit;
                            $total_debit_whitestar += $debit;
                            $total_debit_cash_whitestar += $debit_cash;
                            $both_debit = $debit+$debit_cash;
                            $total_both_debit_whitestar += $both_debit;
                            $balance = $credit - $both_debit;
                            $total_balance_whitestar += $balance;
                            @endphp
                            @if($balance != 0)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$supplier->name}}</td>
                                <td class="text-right">{{number_format($credit)}}</td>
                                <td class="text-right">{{number_format($debit)}}</td>
                                <td class="text-right">{{number_format($debit_cash)}}</td>
                                <td class="text-right">{{number_format($both_debit)}}</td>
                                <td class="text-right">{{number_format($balance)}}</td>
                                <td>{{$supplier->supplier_depend_on_system}}</td>
                            </tr>
                            @endif
                        @endforeach
                        <tr>
                            <th></th>
                            <th>TOTAL</th>
                            <th class="text-right">{{number_format($total_credit_whitestar)}}</th>
                            <th class="text-right">{{number_format($total_debit_whitestar)}}</th>
                            <th class="text-right">{{number_format($total_debit_cash_whitestar)}}</th>
                            <th class="text-right">{{number_format($total_both_debit_whitestar)}}</th>
                            <th class="text-right">{{number_format($total_balance_whitestar)}}</th>
                            <th></th>
                        </tr>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th></th>
                            <th>ALL TOTAL</th>
                            <th class="text-right">{{number_format($total_credit_bonge+$total_credit_whitestar)}}</th>
                            <th class="text-right">{{number_format($total_debit_bonge+$total_debit_whitestar)}}</th>
                            <th class="text-right">{{number_format($total_debit_cash_bonge+$total_debit_cash_whitestar)}}</th>
                            <th class="text-right">{{number_format($total_both_debit_bonge+$total_both_debit_whitestar)}}</th>
                            <th class="text-right">{{number_format($total_balance_bonge+$total_balance_whitestar)}}</th>
                            <th></th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
