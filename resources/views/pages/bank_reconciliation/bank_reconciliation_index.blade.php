@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Bank Reconciliation
                <div class="float-right">
                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Add Bank Reconciliation"))
                        <button type="button" onclick="loadFormModal('bank_reconciliation_form', {className: 'BankReconciliation'}, 'Create New Bank Reconciliation', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Deposit</button>
                        <button type="button" onclick="loadFormModal('bank_reconciliation_withdraw_form', {className: 'BankReconciliation'}, 'Create New Withdraw', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Withdraw</button>
                    @endif
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Bank Reconciliation</h3>
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
                                                    <select name="payment_type" id="payment_type" class="form-control" required>
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
                                            $receiving = \App\Models\Receiving::getTotalReceivingPerDayPerSupervisor($start_date,$end_date,$efd_id);
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
                                @foreach(range(0, $maxTransactions - 1) as $rowIndex => $rowIndex)
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
                        <h4>RECEIVING</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full"  data-ordering="false">
                                <thead>
                                <tr>
                                    <th>EFDs/SUPPLIERS</th>
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
                                    <th>Description</th>
                                    <th>Supplier Name</th>
                                    <th>EFD Name</th>
                                    <th>Payment Type</th>
                                    <th>Credit</th>
                                    <th>Debit</th>
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
                                        <td class="font-w600">{{ $bank_reconciliation->description }}</td>
                                        <td class="font-w600">{{ $bank_reconciliation->supplier }}</td>
                                        <td class="font-w600">{{ $bank_reconciliation->efd }}</td>
                                        <td class="font-w600">{{ $bank_reconciliation->payment_type }}</td>
                                        <td class="text-right">{{ number_format($bank_reconciliation->credit, 2) }}</td>
                                        <td class="text-right">{{ number_format($bank_reconciliation->debit, 2) }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
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
                                    <td colspan="6"></td>
                                    <td class="text-right">{{ number_format($total_credit, 2) }}</td>
                                    <td class="text-right">{{ number_format($total_debit, 2) }}</td>
                                    <td></td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                        <br>
                        <h4>OFFICE BANK DEPOSITED</h4>
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
                                    <th>Debit</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $start_date = '2010-01-01';
                                $end_date = $_POST['end_date'] ?? date('Y-m-d');
                                $efd_id = $_POST['efd_id'] ?? null;
                                $supplier_id = $_POST['supplier_id'] ?? null;
                                $payment_type = 'OFFICE';

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
                                        <td class="font-w600">{{ $bank_reconciliation->description }}</td>
                                        <td class="font-w600">{{ $bank_reconciliation->supplier }}</td>
                                        <td class="font-w600">{{ $bank_reconciliation->efd }}</td>
                                        <td class="font-w600">{{ $bank_reconciliation->payment_type }}</td>
                                        <td class="text-right">{{ number_format($bank_reconciliation->credit, 2) }}</td>
                                        <td class="text-right">{{ number_format($bank_reconciliation->debit, 2) }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
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
                                    <td colspan="6"></td>
                                    <td class="text-right">{{ number_format($total_credit, 2) }}</td>
                                    <td class="text-right">{{ number_format($total_debit, 2) }}</td>
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


