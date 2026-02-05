@extends('layouts.backend')

@section('content')

    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Transfers
                <div class="float-right">
                    <a href="{{route('reports_transaction_movement_report')}}" class="btn btn-rounded btn-outline-secondary min-width-125 mb-10"><i class="si si-arrow-left">&nbsp;</i>Back</a>
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Transfers</h3>
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
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">

                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">

                                                </div>

                                            </div>


                                            <div class="class col-md-1">
                                                <div>
                                                    <button type="submit" name="submit"  class="btn btn-sm btn-primary">Show</button>
                                                </div>
                                            </div>
                                            @php
                                                $start_date = $_POST['start_date'] ?? date('Y-m-d');
                                                $end_date = $_POST['end_date'] ?? date('Y-m-d');

                                                $kassim = \App\Models\BankReconciliation::getTotalTransferBalance($start_date,$end_date,42);
                                                $lailat = \App\Models\BankReconciliation::getTotalTransferBalance($start_date,$end_date,88);
                                                $l_e_crdb = \App\Models\BankReconciliation::getTotalTransferBalance($start_date,$end_date,195);
                                                $l_e_nbc = \App\Models\BankReconciliation::getTotalTransferBalance($start_date,$end_date,196);
                                                $k_h_nbc = \App\Models\BankReconciliation::getTotalTransferBalance($start_date,$end_date,197);
                                            @endphp
                                            <div class="class col-md-2" title="Kassim Haji Kiwandani">
                                                K.H.K Balance: {{number_format(abs($kassim))}}
                                            </div>
                                            <div class="class col-md-2" title="Lailat Mvungi">
                                                L.M Balance: {{number_format(abs($lailat))}}
                                            </div>
                                            <div class="class col-md-2" title="{{settings('ORGANIZATION_NAME')}} CRDB">
                                                L.E CRDB Balance: {{number_format(abs($l_e_crdb))}}
                                            </div>
                                            <div class="class col-md-2" title="{{settings('ORGANIZATION_NAME')}} NBC">
                                                L.E NBC Balance: {{number_format(abs($l_e_nbc))}}
                                            </div>
                                            <div class="class col-md-2" title="Kassim Haji NBC">
                                                K.H NBC Balance: {{number_format(abs($k_h_nbc))}}
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full"  data-ordering="false">
                                        <thead>
                                        <tr>
                                            <th class="text-center" style="width: 100px;">#</th>
                                            <th>Date</th>
                                            <th>Supplier From</th>
                                            <th>Supplier To</th>
                                            <th>Beneficiary</th>
                                            <th>Description</th>
                                            <th>Payment Type</th>
                                            <th>Debit</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php

                                        $total_credit = 0;
                                        $total_debit = 0;
                                        $total_tax = 0;
                                        $total_turn_over = 0;
                                        $total_supplier_to_amount = 0;
                                        ?>
                                        @foreach($bank_reconciliations as $bank_reconciliation)
                                            <?php
                                                $supplier = \App\Models\BankReconciliation::getOnlyTransferedTo($bank_reconciliation->date,$bank_reconciliation->reference);
                                            $supplier_to = $supplier->supplier;
                                            $supplier_to_amount = $bank_reconciliation->debit_amount;
                                            $total_supplier_to_amount += abs($supplier_to_amount);
                                            ?>
                                            <tr>
                                                <td class="text-center">
                                                    {{$loop->iteration}}
                                                </td>
                                                <td class="font-w600">{{ $bank_reconciliation->supplier_id }}</td>
                                                <td class="font-w600">{{ $bank_reconciliation->supplier }}</td>
                                                <td class="font-w600">{{ $supplier_to }}</td>
                                                <td class="font-w600">{{ $bank_reconciliation->beneficiary }}</td>
                                                <td class="font-w600">{{ $supplier->description }}</td>
                                                <td class="font-w600">{{ $bank_reconciliation->payment_type }}</td>
                                                <td class="text-right">{{ number_format(abs($supplier_to_amount), 2) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                        <tfoot>
                                        <tr>
                                            <td style="display: none"></td>
                                            <td style="display: none"></td>
                                            <td style="display: none"></td>
                                            <td style="display: none"></td>
                                            <td style="display: none"></td>
                                            <td style="display: none"></td>
                                            <td colspan="7"></td>
                                            <td class="text-right">{{ number_format($total_supplier_to_amount, 2) }}</td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection


