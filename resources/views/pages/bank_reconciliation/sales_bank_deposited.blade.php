@extends('layouts.backend')

@section('content')

    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Bank Reconciliation
                <div class="float-right">
                        <a href="{{route('bank_reconciliation')}}" type="button" title="Back"  class="btn btn-rounded btn-default min-width-100 mb-10"><i class="si si-arrow-left">&nbsp;</i>Back</a>

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Sales BanK Deposit</h3>
                    </div>
                    <div class="block-content">
                        @can('Date Sales Bank Deposited')
                            <div class="row no-print m-t-10">
                                <div class="class col-md-12">
                                    <div class="class card-box">
                                        <form  name="collection_search" action="{{route('bank_reconciliation_sales_bank_deposited')}}" id="filter-form" method="post" autocomplete="off">
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
                        @endcan
                        <div class="table-responsive">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                            <thead>
                                            <tr>
                                                <th class="text-center" style="width: 100px;">#</th>
                                                <th>Date</th>
                                                <th>REFERENCE</th>
                                                <th>Description</th>
                                                <th>Type</th>
                                                <th>Supplier Name</th>
                                                <th>Beneficiary</th>
                                                <th>Account Details</th>
                                                <th>Wakala</th>
                                                <th>EFD Name</th>
                                                <th>Payment Type</th>
                                                <th>Payment Mode</th>
                                                <th>Credit</th>
                                                <th>Debit</th>
                                                <th>Slip Presentation</th>
                                                <th>Status</th>
                                                <th class="text-center" style="width: 100px;">Actions</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $start_date = $_POST['start_date'] ?? date('Y-m-d');
                                            $end_date = $_POST['end_date'] ?? date('Y-m-d');
                                            $efd_id = $_POST['efd_id'] ?? null;
                                            $supplier_id = $_POST['supplier_id'] ?? null;
                                            $payment_type = 'SALES';

                                            // dump($_GET);
                                            // dump($_GET);
                                            //                                dump($_GET['end_date']);

                                            $bank_reconciliations = \App\Models\BankReconciliation::getAll($start_date,$end_date,$efd_id,$supplier_id,$payment_type);
                                            $total_credit = 0;
                                            $total_debit = 0;
                                            $total_tax = 0;
                                            $total_turn_over = 0;
                                            ?>
                                            @foreach($bank_reconciliations as $bank_reconciliation)
                                                    <?php
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
                                                    <td class="font-w600">{{ $bank_reconciliation->reference }}</td>
                                                    <td class="font-w600">{{ $bank_reconciliation->description }}</td>
                                                    <td class="font-w600">{{ $bank_reconciliation->type }}</td>
                                                    <td class="font-w600">{{ $bank_reconciliation->supplier }}</td>
                                                    <td class="font-w600">{{ $bank_reconciliation->beneficiary }}</td>
                                                    <td class="font-w600">{{ $bank_reconciliation->account_name.' - '.$bank_reconciliation->account_number }}</td>
                                                    <td class="font-w600">{{ $bank_reconciliation->wakala }}</td>
                                                    <td class="font-w600">{{ $bank_reconciliation->efd }}</td>
                                                    <td class="font-w600">{{ $bank_reconciliation->bank }}</td>
                                                    <td class="font-w600">{{ $bank_reconciliation->payment_type }}</td>
                                                    <td class="text-right">{{ number_format($bank_reconciliation->credit, 2) }}</td>
                                                    <td class="text-right">{{ number_format($bank_reconciliation->debit, 2) }}</td>
                                                    <td class="font-w600">{{ $bank_reconciliation->slip_presentation }}</td>
                                                    <td>
                                                        @if($bank_reconciliation->credit)
                                                            @if($bank_reconciliation->status == 'PENDING')
                                                                <div class="badge badge-warning">{{ $bank_reconciliation->status}}</div>
                                                            @elseif($bank_reconciliation->status == 'APPROVED')
                                                                <div class="badge badge-primary">{{ $bank_reconciliation->status}}</div>
                                                            @elseif($bank_reconciliation->status == 'REJECTED')
                                                                <div class="badge badge-danger">{{ $bank_reconciliation->status}}</div>
                                                            @elseif($bank_reconciliation->status == 'PAID')
                                                                <div class="badge badge-primary">{{ $bank_reconciliation->status}}</div>
                                                            @elseif($bank_reconciliation->status == 'COMPLETED')
                                                                <div class="badge badge-success">{{ $bank_reconciliation->status}}</div>
                                                            @else
                                                                <div class="badge badge-secondary">{{ $bank_reconciliation->status}}</div>
                                                            @endif
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="btn-group">
                                                            @if($bank_reconciliation->credit)
                                                                <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{route('bank_reconciliations',['id' => $bank_reconciliation->id,'document_type_id'=>12])}}"><i class="fa fa-eye"></i></a>
                                                            @endif
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
                                            @endforeach
                                            </tbody>
                                            <tfoot>
                                            <tr>
                                                <td colspan="11"></td>
                                                <td class="text-right">{{ number_format($total_credit, 2) }}</td>
                                                <td class="text-right">{{ number_format($total_debit, 2) }}</td>
                                                <td></td>
                                                <td></td>
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
            </div>
        </div>
    </div>

@endsection


