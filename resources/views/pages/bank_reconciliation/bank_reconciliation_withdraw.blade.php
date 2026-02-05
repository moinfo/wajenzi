@extends('layouts.backend')

@section('content')

    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Bank Reconciliation
                <div class="float-right">
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">OFFICE BANK WITHDRAW</h3>
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
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-01')}}">

                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-t')}}">

                                                </div>

                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon3">Supplier</span>
                                                    </div>
                                                    <select name="supplier_id" id="input-supplier-id" class="form-control" aria-describedby="basic-addon3">
                                                        <option value="">All Suppliers</option>
                                                        @foreach ($suppliers as $supplier)
                                                            <option value="{{ $supplier->id }}"> {{ $supplier->name }} </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon3">EFD</span>
                                                    </div>
                                                    <select name="efd_id" id="input-efd-id" class="form-control" aria-describedby="basic-addon3">
                                                        <option value="">All EFD</option>
                                                        @foreach ($efds as $efd)
                                                            <option value="{{ $efd->id }}"> {{ $efd->name }} </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-2">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon3">Type</span>
                                                    </div>
                                                    <select name="payment_type" id="payment_type" class="form-control">
                                                        <option value="">ALL</option>

                                                        @foreach ($bank_reconciliation_payment_types as $bank_reconciliation_payment_type)
                                                            <option value="{{$bank_reconciliation_payment_type['name']}}"> {{ $bank_reconciliation_payment_type['name'] }} </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-1">
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
                                    <th>Description</th>
                                    <th>Supplier Name</th>
                                    <th>EFD Name</th>
                                    <th>Payment Type</th>
                                    <th>Credit</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $total_credit = 0;
                                $total_debit = 0;
                                $total_tax = 0;
                                $total_turn_over = 0;
                                ?>
                                @foreach($bank_reconciliations as $bank_reconciliation)
                                    <?php
                                    if ($bank_reconciliation->credit){
                                    $credit = $bank_reconciliation->credit;
                                    $total_credit += $credit;
                                    $debit = $bank_reconciliation->debit;
                                    $total_debit += $debit;

                                    ?>

                                    <tr id="bank_reconciliation-tr-{{$bank_reconciliation->id}}">
                                        <td class="text-center">
                                            {{$loop->iteration}}
                                        </td>
                                        <td class="font-w600">{{ $bank_reconciliation->date }}</td>
                                        <td class="font-w600">{{ $bank_reconciliation->description }}</td>
                                        <td class="font-w600">{{ $bank_reconciliation->supplier }}</td>
                                        <td class="font-w600">{{ $bank_reconciliation->efd }}</td>
                                        <td class="font-w600">{{ $bank_reconciliation->payment_type }}</td>
                                        <td class="text-right">{{ number_format($bank_reconciliation->credit, 2) }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                @can('Edit Bank Reconciliation')
                                                    <button type="button"
                                                            onclick="loadFormModal('bank_reconciliation_form', {className: 'BankReconciliation', id: {{$bank_reconciliation->id}}}, 'Edit {{$bank_reconciliation->efd}}', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan

                                                @can('Delete Bank Reconciliation')
                                                    <button type="button"
                                                            onclick="deleteModelItem('BankReconciliation', {{$bank_reconciliation->id}}, 'bank_reconciliation-tr-{{$bank_reconciliation->id}}');"
                                                            class="btn btn-sm btn-danger js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Delete"
                                                            data-original-title="Delete">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                @endcan

                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                    }
                                    ?>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td colspan="6"></td>
                                    <td class="text-right">{{ number_format($total_credit, 2) }}</td>
                                    <td></td>
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


