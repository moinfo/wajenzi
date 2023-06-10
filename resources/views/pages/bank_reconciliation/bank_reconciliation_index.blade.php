@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">BR
                <div class="float-right">
                    <a href="{{route('bank_deposit_report')}}" class="btn btn-rounded btn-outline-danger min-width-100 mb-10"><i class="si si-graph">&nbsp;</i>B</a>
                    <a href="{{route('supplier_targets')}}" class="btn btn-rounded btn-outline-warning min-width-100 mb-10"><i class="si si-graph">&nbsp;</i>ST</a>
                    <a href="{{route('supplier_targets_report')}}" class="btn btn-rounded btn-outline-warning min-width-100 mb-10"><i class="si si-graph">&nbsp;</i>STR</a>
                    <a href="{{route('transfer_reports')}}" class="btn btn-rounded btn-outline-success min-width-100 mb-10"><i class="si si-graph">&nbsp;</i>TR</a>
                    <a href="{{route('bank_withdraw_reports')}}" class="btn btn-rounded btn-outline-primary min-width-100 mb-10"><i class="si si-graph">&nbsp;</i>WR</a>
                    <a href="{{route('bank_deposit_reports')}}" class="btn btn-rounded btn-outline-danger min-width-100 mb-10"><i class="si si-graph">&nbsp;</i>DR</a>
                @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Add Bank Reconciliation"))
                        <button type="button" onclick="loadFormModal('bank_reconciliation_transfer_form', {className: 'BankReconciliation'}, 'Transfer', 'modal-md');" class="btn btn-rounded btn-outline-danger min-width-100 mb-10"><i class="si si-plus">&nbsp;</i>TR</button>
                        <button type="button" onclick="loadFormModal('bank_reconciliation_form', {className: 'BankReconciliation'}, 'Create New Bank Reconciliation', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-100 mb-10"><i class="si si-plus">&nbsp;</i>ND</button>
                        <button type="button" onclick="loadFormModal('bank_reconciliation_withdraw_form', {className: 'BankReconciliation'}, 'Create New Withdraw', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-100 mb-10"><i class="si si-plus">&nbsp;</i>NW</button>
                    @endif
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Bank Reconciliation</h3>
                    </div>
                    <div class="block-content">
                        @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Date Bank Reconciliation"))
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
                        @endif
                        <div class="row">
                            <div class="col-sm-2"></div>
                            <div class="col-sm-8">
                                <h4>Actual Deposit Summary</h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full"  data-ordering="false">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Efd</th>
                                            <th>Actual</th>
                                            <th>Supplier Excluded</th>
                                            <th>Balance</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @php
                                            $start_date = $_POST['start_date'] ?? date('Y-m-d');
                                            $end_date = $_POST['end_date'] ?? date('Y-m-d');
                                            $deposit_sum = 0;
                                            $deposit_excluded_total = 0;
                                            $balance_total = 0;

                                        @endphp
                                        @foreach($systems as $system)
                                            @php
                                                $deposit = \App\Models\BankReconciliation::getTotalDepositPerDayPerSystem($start_date,$end_date,$system->id);
                                                $deposit_excluded = \App\Models\BankReconciliation::getTotalDepositPerDayPerSystemExcluded($start_date,$end_date,$system->id);
                                                $balance = $deposit - $deposit_excluded;

                                                $deposit_excluded_total += $deposit_excluded;
                                                $balance_total += $balance;
                                                $deposit_sum += $deposit;
                                            @endphp
                                            <tr>
                                                <td>{{$loop->iteration}}</td>
                                                <td>{{$system->name}}</td>
                                                <td class="text-right">{{number_format($deposit,2)}}</td>
                                                <td class="text-right">{{number_format($balance,2)}}</td>
                                                <td class="text-right">{{number_format($deposit_excluded,2)}}</td>
                                            </tr>
                                        @endforeach
                                        @php
                                        $white = \App\Models\BankReconciliation::getTotalDepositWhitestar($start_date,$end_date,16) - \App\Models\BankReconciliation::getTotalDepositWhitestarAuto($start_date,$end_date,16);
                                        @endphp
                                        <tr>
                                            <td>5</td>
                                            <td>WHITESTAR</td>
                                            <td class="text-right">{{number_format($white)}}</td>
                                            <td class="text-right"></td>
                                            <td class="text-right"></td>
                                        </tr>
                                        </tbody>
                                        <tfoot>
                                        <tr>
                                            <th colspan="2" class="text-right">Total</th>
                                            <th class="text-right">{{number_format($deposit_sum,2)}}</th>
                                            <th class="text-right">{{number_format($balance_total,2)}}</th>
                                            <th class="text-right">{{number_format($deposit_excluded_total,2)}}</th>
                                        </tr>
                                        @php

                                            $deposit_out = \App\Models\BankReconciliation::getTotalDepositPerDayPerSystem($start_date,$end_date,5) ?? 0;
                                            $deposit_out_only = \App\Models\BankReconciliation::getTotalDepositPerDayPerSystemOnly($start_date,$end_date,5) ?? 0;
                                             $deposit_excluded_out = \App\Models\BankReconciliation::getTotalDepositPerDayPerSystemExcluded($start_date,$end_date,5) ?? 0;
                                             $deposit_excluded_out_only = \App\Models\BankReconciliation::getTotalDepositPerDayPerSystemExcludedOnly($start_date,$end_date,5) ?? 0;
                                        @endphp
                                        <tr>
                                            <td colspan="2" class="text-right">KIWANDANI</td>
                                            <td class="text-right">{{number_format($deposit_out,2)}}</td>
                                            <td class="text-right">{{number_format(($deposit_out)-($deposit_excluded_out),2)}}</td>
                                            <td class="text-right">{{number_format(($deposit_excluded_out),2)}}</td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="col-sm-2"></div>
                        </div>

                        <br>
                        <h4>BANK RECONCILIATION STATEMENT</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full"  data-ordering="false">
                                <thead>
                                <tr>
                                    <th width="40">#</th>
                                    @foreach($efdTransactions as $index => $efd)
                                        <th>{{$efd->name}}</th>
                                    @endforeach
                                    <th>Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $sale = new \App\Models\Sale();
                                $start_date = $_POST['start_date'] ?? date('Y-m-d');
                                $end_date = $_POST['end_date'] ?? date('Y-m-d');

                                ?>
                                <tr>
                                    <td>Receiving</td>
                                    @foreach($efds as $efd)
                                        @php
                                            $efd_id = $efd->id;
                                            $receiving = \App\Models\Report::getTotalDaysSalesBonge($start_date,$end_date,$efd->bonge_customer_id);
                                        @endphp
                                        <td class="text-right">{{number_format($receiving,2)}}</td>
                                    @endforeach
                                    @php
                                        $total_receiving = \App\Models\Receiving::getTotalReceivingPerDayPerSupervisor($start_date,$end_date,null);
                                    @endphp
                                    <td class="text-right">{{number_format($total_receiving,2)}}</td>
                                </tr>
                                <tr>
                                    <td>Balance</td>
                                    @foreach($efds as $efd)
                                        @php
                                            $efd_id = $efd->id;
                                            $receiving = \App\Models\Receiving::getTotalReceivingPerDayPerSupervisor($start_date,$end_date,$efd_id);
                                            $deposit = \App\Models\BankReconciliation::getTotalDepositPerDayPerSupervisor($start_date,$end_date,$efd_id);
                                        @endphp
                                        <td class="text-right">{{number_format($receiving-$deposit,2)}}</td>
                                    @endforeach
                                    @php
                                        $total_receiving = \App\Models\Receiving::getTotalReceivingPerDayPerSupervisor($start_date,$end_date,null);
                                        $total_deposit = \App\Models\BankReconciliation::getTotalDepositPerDayPerSupervisor($start_date,$end_date,null);

                                    @endphp
                                    <td class="text-right">{{number_format($total_receiving-$total_deposit,2)}}</td>
                                </tr>
                                <tr>
                                    <td>Deposits</td>
                                    @foreach($efds as $efd)

                                        <td class="text-right"></td>
                                    @endforeach
                                    <td class="text-right"></td>
                                </tr>
                                @php {{ $totalSum = 0; }} @endphp
                                @foreach(range(0, 30) as $rowIndex => $rowIndex)
                                    @php {{ $rowSum = 0; }} @endphp
                                    <tr>
                                        <td>{{$loop->index + 1}}</td>
                                        @foreach($efdTransactions as $columnIndex => $efd)
                                            @php {{
                                                    $val = isset($efd->transactions[$rowIndex]) ? $efd->transactions[$rowIndex]->debit : '';
                                                    $rowSum += is_numeric($val) ? $val : 0;
                                                }}
                                            @endphp
                                            <td class="text-right">
                                                {{ $val }}
                                            </td>
                                        @endforeach
                                        <td class="text-right">{{ number_format($rowSum) }}</td>
                                    </tr>

                                    @php {{ $totalSum += $rowSum; }} @endphp
                                @endforeach
                                <tr>
                                    <th></th>
                                    @foreach($efds as $efd)

                                        <th class="">{{$efd->name}}</th>
                                    @endforeach
                                    <th class="text-right"></th>
                                </tr>
                                <tr>
                                    <th>All Total</th>
                                    @foreach($efdTransactions as $footerIndex => $efd)
                                        <td class="text-right">
                                            {{ array_sum(array_column($efd->transactions->toArray(), 'debit')) }}
                                        </td>
                                    @endforeach
                                    <th class="text-right">{{ number_format($totalSum,2) }}</th>
                                </tr>
                                <tr>
                                    <th>Unspent Total</th>
                                    @foreach($efdTransactions_2 as $footerIndex => $efd)
                                        <td class="text-right">
                                            {{ array_sum(array_column($efd->transactions->toArray(), 'debit')) }}
                                        </td>
                                    @endforeach
                                    <th class="text-right"></th>
                                </tr>
                                <tr>
                                    <th>Actual Total</th>
                                    @foreach($efdTransactions_3 as $footerIndex => $efd)
                                        <td class="text-right">
                                            {{ array_sum(array_column($efd->transactions->toArray(), 'debit')) }}
                                        </td>
                                    @endforeach
                                    <th class="text-right"></th>
                                </tr>
                                </tbody>
                            </table>

                        </div>
                        <br>
                        <h4>SUPPLIERS STATEMENT</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full"  data-ordering="false">
                                <thead>
                                <tr>
                                    <th>EFDs/SUPPLIERS</th>
                                    <th>MASHINENI</th>
                                    @foreach($supplier_with_deposits as $supplier)
                                        <th>{{$supplier->name}}</th>
                                    @endforeach
                                    <th>Total</th>

                                </tr>
                                </thead>
                                <tbody>
                                @php
                                    @endphp
                                @foreach($efds as $efd)
                                    @php
                                        $efd_id = $efd->id;
                                    @endphp
                                    <tr>
                                        <td>{{$efd->name}}</td>
                                        @php
                                            $deposit_whitestar = \App\Models\BankReconciliation::getTotalDepositPerDayPerSupplierInWhitestar($start_date,$end_date,$efd_id);
                                        @endphp
                                        <td class="text-right">{{number_format($deposit_whitestar)}}</td>
                                        @foreach($supplier_with_deposits as $supplier)
                                            @php {{
                                            $supplier_id = $supplier->supplier_id;
                                            $deposit = \App\Models\BankReconciliation::getTotalDepositPerDayPerSupplier($start_date,$end_date,$efd_id,$supplier_id);

                                            }} @endphp
                                            <td class="text-right">{{number_format($deposit,2)}}</td>
                                        @endforeach
                                        @php {{
                                            $total_deposit = \App\Models\BankReconciliation::getTotalDepositPerDayPerSupplier($start_date,$end_date,$efd_id,null);

                                            }} @endphp
                                        <td class="text-right">{{number_format($total_deposit,2)}}</td>
                                    </tr>
                                @endforeach

                                </tbody>
                                <tfoot>
                                <tr>
                                    <td>Total</td>
                                    @php
                                        $total_deposit_whitestar = \App\Models\BankReconciliation::getTotalDepositPerDayPerSupplierInWhitestar($start_date,$end_date,null);
                                    @endphp
                                    <td class="text-right">{{number_format($total_deposit_whitestar)}}</td>
                                    @foreach($supplier_with_deposits as $supplier)
                                        @php {{
                                            $supplier_id = $supplier->supplier_id;
                                            $deposit = \App\Models\BankReconciliation::getTotalDepositPerDayPerSupplier($start_date,$end_date,null,$supplier_id);

                                            }} @endphp
                                        <td class="text-right">{{number_format($deposit,2)}}</td>
                                    @endforeach
                                    @php {{
                                            $total_deposit = \App\Models\BankReconciliation::getTotalDepositPerDayPerSupplier($start_date,$end_date,null,null);

                                            }} @endphp
                                    <td class="text-right">{{number_format($total_deposit,2)}}</td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                        <br>
                        <h4>SALES BANK DEPOSITED</h4>
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
                                    <th>EFD Name</th>
                                    <th>Bank Name</th>
                                    <th>Payment Type</th>
                                    <th>Credit</th>
                                    <th>Debit</th>
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
                                        <td class="font-w600">{{ $bank_reconciliation->efd }}</td>
                                        <td class="font-w600">{{ $bank_reconciliation->bank }}</td>
                                        <td class="font-w600">{{ $bank_reconciliation->payment_type }}</td>
                                        <td class="text-right">{{ number_format($bank_reconciliation->credit, 2) }}</td>
                                        <td class="text-right">{{ number_format($bank_reconciliation->debit, 2) }}</td>
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
                                            @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Edit Bank Reconciliation"))
                                                    <button type="button"
                                                            onclick="loadFormModal('bank_reconciliation_form', {className: 'BankReconciliation', id: {{$bank_reconciliation->id}}}, 'Edit {{$bank_reconciliation->efd}}', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endif

                                                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Delete Bank Reconciliation"))
                                                        <button type="button"
                                                                onclick="deleteModelItem('BankReconciliation', {{$bank_reconciliation->id}}, 'bank_reconciliation-tr-{{$bank_reconciliation->id}}');"
                                                                class="btn btn-sm btn-danger js-tooltip-enabled"
                                                                data-toggle="tooltip" title="Delete"
                                                                data-original-title="Delete">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    @endif

                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td colspan="8"></td>
                                    <td class="text-right">{{ number_format($total_credit, 2) }}</td>
                                    <td class="text-right">{{ number_format($total_debit, 2) }}</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                        <br>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection


