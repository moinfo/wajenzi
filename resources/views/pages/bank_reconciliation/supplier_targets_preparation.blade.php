@php use App\Models\SupplierTargetPreparation; @endphp
<style>
    /* Card Styles */
    .report-section {
        margin-bottom: 2rem;
    }

    .report-card {
        background: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        margin-bottom: 1.5rem;
    }

    .report-card-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #edf2f7;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Filter Section */
    .filter-section {
        background: #f8fafc;
        padding: 1.5rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
    }

    /* Table Improvements */
    .table-section {
        background: #fff;
        padding: 1rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }

    .summary-table th {
        background: #f8fafc;
    }

    .table-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2d3748;
        margin-bottom: 1rem;
    }

    /* Stats and Totals */
    .stats-row {
        background: #edf2f7;
        font-weight: 600;
    }

    /* Action Buttons */
    .action-btn {
        padding: 0.4rem 1rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
    }
</style>
<!-- Additional styles for tables -->
<style>
    .table thead th {
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }

    .table-hover tbody tr:hover {
        background-color: #f7fafc;
    }

    .text-success { color: #0f766e !important; }
    .text-danger { color: #dc2626 !important; }

    .bg-light { background-color: #f8fafc !important; }
    .bg-light-subtle { background-color: #f1f5f9 !important; }
</style>
<style>
    .beneficiary-block {
        padding: 0.5rem 0;
    }

    .beneficiary-block:not(:last-child) {
        border-bottom: 1px solid #e2e8f0;
    }

    .amount-block {
        padding: 0.5rem 0;
        font-weight: 600;
    }

    .amount-block:not(:last-child) {
        border-bottom: 1px solid #e2e8f0;
    }

    .btn-group .btn {
        padding: 0.25rem 0.5rem;
    }

    .btn-group .btn i {
        font-size: 1rem;
    }

    .align-middle {
        vertical-align: middle !important;
    }
</style>
<style>
    .beneficiary-details {
        padding: 10px 0;
    }

    .acc-name {
        font-weight: 600;
        margin-bottom: 5px;
    }

    .bank-details {
        line-height: 1.6;
    }

    .align-middle {
        vertical-align: middle !important;
    }

    .text-right {
        text-align: right !important;
    }

    .table td {
        padding: 0.75rem !important;
    }

    /* Set fixed width for amount column to align numbers */
    .table th:nth-child(3),
    .table td:nth-child(3) {
        width: 200px;
        min-width: 200px;
    }

    /* Ensure consistent spacing in bank details */
    .bank-details div {
        padding: 2px 0;
    }

    /* Add subtle separation between accounts */
    tr:not(:last-child) td {
        border-bottom: 1px solid #e2e8f0;
    }
</style>
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <!-- Header Section -->
            <div class="report-section">
                <div class="report-card-header">
                    <h4 class="mb-0">Supplier Target Preparation</h4>
                    @can('Add Supplier Target Preparation')
                        <button type="button"
                                onclick="loadFormModal('supplier_target_preparation_form', {className: 'SupplierTargetPreparation'}, 'Create New Target Preparation', 'modal-lg');"
                                class="btn btn-primary action-btn">
                            <i class="si si-plus mr-1"></i>New Target Preparation
                        </button>
                    @endcan
                </div>
            </div>

            <!-- Filter Section -->
            @can('Date Supplier Targets Report')
                <div class="filter-section">
                    <div class="class card-box">
                        <form  name="collection_search" action="" id="filter-form" method="post" autocomplete="off">
                            @csrf
                            <div class="row">
                                <div class="class col-md-5">
                                    <div class="input-group mb-3">
                                        <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">

                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1">Date</span>
                                        </div>
                                        <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">

                                    </div>

                                </div>
                                <div class="class col-md-4">
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

                                <div class="class col-md-1">
                                    <div>
                                        <button type="submit" name="submit"  class="btn btn-sm btn-primary">Show</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan

            <!-- Summary Tables Section -->
            <div class="row">
                <!-- Target Summary -->
                <div class="col-12 table-section">
                    <h5 class="table-title">Target Summary</h5>
                    <div class="table-responsive">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full"  data-ordering="false">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Supplier</th>
                                            <th>Beneficiary</th>
                                            <th>Target</th>
                                            <th>Targeted</th>
                                            <th>Balance</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @php
                                            $total_target = 0;
                                            $total_difference = 0;
                                            $total_deposited = 0;
                                            $total_transfers = 0;
                                            $total_targeted = 0;
                                            $total_balance = 0;
                                            $efd_id = null;
                                        @endphp
                                        @foreach($supplier_target_preparations as $supplier_targets_report)
                                            @php
                                                $total_target += $supplier_targets_report->total_target;
                                                $targeted = SupplierTargetPreparation::where('supplier_target_id', $supplier_targets_report->id)
                                                    ->whereBetween('date', [$start_date, $end_date])
                                                    ->sum('amount');
                                                $total_targeted += $targeted;
                                                $balance = $supplier_targets_report->total_target - $targeted;
                                                $total_balance += $balance;
                                                @endphp
                                            <tr id="supplier_targets_report-tr-{{$supplier_targets_report->id}}">
                                                <td class="text-center">
                                                    {{$loop->iteration}}
                                                </td>
                                                <td class="font-w600">{{ $supplier_targets_report->supplier_name }}</td>
                                                <td class="font-w600">{{ $supplier_targets_report->beneficiary_name }}</td>
                                                <td class="text-right">{{ number_format($supplier_targets_report->total_target, 2) }}</td>
                                                <td class="text-right">{{ number_format($targeted, 2) }}</td>
                                                <td class="text-right">{{ number_format(($supplier_targets_report->total_target - $targeted), 2) }}</td>
                                            </tr>
                                        @endforeach

                                        </tbody>
                                        <tfoot>
                                        <tr>
                                            <th class="text-right" colspan="3">TOTAL</th>
                                            <th class="text-right">{{number_format($total_target)}}</th>
                                            <th class="text-right">{{number_format($total_targeted)}}</th>
                                            <th class="text-right">{{number_format($total_balance)}}</th>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="col-sm-2"></div>
                        </div>
                    </div>
                </div>

                <!-- EFD Analysis -->
                <div class="col-12 table-section">
                    <h5 class="table-title">EFD Analysis</h5>
                    <div class="table-responsive">
                        @php
                            // Get only EFDs with bonge sales
                            $efdsWithSales = [];
                            $totalBongeSales = 0;

                            // Get beneficiaries with amounts
                            $beneficiariesWithAmount = DB::table('beneficiaries as b')
                                ->select('b.*')
                                ->join('supplier_targets as st', 'st.beneficiary_id', '=', 'b.id')
                                ->join('supplier_target_preparations as stp', 'stp.supplier_target_id', '=', 'st.id')
                                ->where('st.type', 'TARGET')
                                ->whereBetween('stp.date', [$start_date, $end_date])
                                ->groupBy('b.id', 'b.name')
                                ->having(DB::raw('SUM(stp.amount)'), '>', 0)
                                ->get();
                        @endphp

                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th class="bg-light" style="width: 200px;">EFD NAME</th>
                                <th class="text-center">BONGE SALES</th>
                                @foreach($beneficiariesWithAmount as $beneficiary)
                                    <th class="text-center">{{ $beneficiary->name }}</th>
                                @endforeach
                                <th class="text-center bg-light">TOTAL</th>
                                <th class="text-center bg-light">BALANCE</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php
                                $columnTotals = array_fill(0, count($beneficiariesWithAmount) + 1, 0);
                                $totalBongeSales = 0;
                                $totalBalance = 0;
                            @endphp

                            @foreach($efds as $efd)
                                @php
                                    $bongeSales = \App\Models\Report::getTotalDaysSalesBonge($start_date, $end_date, $efd->bonge_customer_id);
                                    if($bongeSales <= 0) continue;

                                    $rowTotal = 0;
                                    $totalBongeSales += $bongeSales;
                                @endphp
                                <tr>
                                    <td class="font-weight-bold">{{ $efd->name }}</td>
                                    <td class="text-right">{{ number_format($bongeSales, 2) }}</td>

                                    @foreach($beneficiariesWithAmount as $index => $beneficiary)
                                        @php
                                            $amount = DB::table('supplier_target_preparations as stp')
                                                ->join('supplier_targets as st', 'st.id', '=', 'stp.supplier_target_id')
                                                ->where('st.beneficiary_id', $beneficiary->id)
                                                ->where('stp.efd_id', $efd->id)
                                                ->where('st.type', 'TARGET')
                                                ->whereBetween('stp.date', [$start_date, $end_date])
                                                ->sum('stp.amount');
                                            $rowTotal += $amount;
                                            $columnTotals[$index] += $amount;
                                        @endphp
                                        <td class="text-right">{{ $amount > 0 ? number_format($amount, 2) : '' }}</td>
                                    @endforeach

                                    <td class="text-right bg-light font-weight-bold">{{ number_format($rowTotal, 2) }}</td>

                                    @php
                                        $balance = $bongeSales - $rowTotal;
                                        $totalBalance += $balance;
                                    @endphp
                                    <td class="text-right {{ $balance > 0 ? 'text-success' : 'text-danger' }} font-weight-bold">
                                        {{ number_format($balance, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                            <tr class="bg-light">
                                <th>TOTAL</th>
                                <th class="text-right">{{ number_format($totalBongeSales, 2) }}</th>
                                @foreach($columnTotals as $total)
                                    <th class="text-right">{{ number_format($total, 2) }}</th>
                                @endforeach
                                <th class="text-right">{{ number_format(array_sum($columnTotals), 2) }}</th>
                                <th class="text-right {{ $totalBalance > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($totalBalance, 2) }}
                                </th>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>



                <!-- Beneficiary Details Analysis -->
                <div class="col-12 table-section">
                    <h5 class="table-title">Beneficiary Account Details Analysis</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th style="width: 200px;">EFD NAME</th>
                                <th>BENEFICIARY DETAILS</th>
                                <th class="text-right" style="width: 200px;">AMOUNT</th>
                                <th class="text-center" style="width: 120px;">ACTIONS</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($efds as $efd)
                                @php
                                    $bongeSales = \App\Models\Report::getTotalDaysSalesBonge($start_date, $end_date, $efd->bonge_customer_id);
                                    if($bongeSales <= 0) continue;

                                    // First get all beneficiaries with their total amounts for this EFD
                                    $beneficiariesWithAmounts = DB::table('supplier_target_preparations as stp')
                                        ->select([
                                            'st.beneficiary_id',
                                            'b.name as beneficiary_name',
                                            DB::raw('SUM(stp.amount) as total_amount')
                                        ])
                                        ->join('supplier_targets as st', 'st.id', '=', 'stp.supplier_target_id')
                                        ->join('beneficiaries as b', 'b.id', '=', 'st.beneficiary_id')
                                        ->where('stp.efd_id', $efd->id)
                                        ->where('st.type', 'TARGET')
                                        ->whereBetween('stp.date', [$start_date, $end_date])
                                        ->groupBy('st.beneficiary_id', 'b.name')
                                        ->having(DB::raw('SUM(stp.amount)'), '>', 0)
                                        ->get();

                                    if($beneficiariesWithAmounts->isEmpty()) continue;

                                    // Then get bank accounts for each beneficiary
                                    foreach($beneficiariesWithAmounts as $beneficiary) {
                                        $beneficiary->accounts = DB::table('beneficiary_accounts as ba')
                                            ->select('banks.name as bank_name', 'ba.account')
                                            ->join('banks', 'banks.id', '=', 'ba.bank_id')
                                            ->where('ba.beneficiary_id', $beneficiary->beneficiary_id)
                                            ->get();
                                    }
                                @endphp

                                @foreach($beneficiariesWithAmounts as $beneficiary)
                                    <tr data-efd="{{ $efd->id }}" data-row-id="{{ $loop->index }}">
                                        <td class="efd-name font-weight-bold">{{ $efd->name }}</td>
                                        <td>
                                            <div class="beneficiary-details">
                                                <div class="acc-name">ACC NAME: {{ $beneficiary->beneficiary_name }}</div>
                                                <div class="bank-details ml-3">
                                                    @foreach($beneficiary->accounts as $account)
                                                        <div>{{ $account->bank_name }}: {{ $account->account }}</div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </td>
                                        <td class="amount text-right align-middle">{{ number_format($beneficiary->total_amount, 2) }}</td>
                                        <td class="text-center align-middle">
                                            <div class="btn-group">
                                                <button type="button"
                                                        class="btn btn-sm btn-success mr-1"
                                                        onclick="shareDetailsWhatsApp('{{ $efd->id }}', '{{ $loop->index }}')"
                                                        title="Share on WhatsApp">
                                                    <i class="fab fa-whatsapp"></i>
                                                </button>
                                                <button type="button"
                                                        class="btn btn-sm btn-info"
                                                        onclick="copyDetailsToClipboard('{{ $efd->id }}', '{{ $loop->index }}')"
                                                        title="Copy to Clipboard">
                                                    <i class="fa fa-copy"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Detailed List -->
                <div class="col-12 table-section">
                    <h5 class="table-title">Preparation Details</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Date</th>
                                <th>Supplier Target Preparation</th>
                                <th>Efd</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($supplier_target_preparation_lists as $supplier_target_preparation)

                                <tr id="collection-tr-{{$supplier_target_preparation->id}}">
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$supplier_target_preparation->date}}</td>
                                    <td>{{$supplier_target_preparation->supplierTarget->supplier->name ?? null}} {{ number_format($supplier_target_preparation->supplierTarget->amount)}}</td>
                                    <td>{{$supplier_target_preparation->efd->name ?? null}}</td>
                                    <td>{{$supplier_target_preparation->description}}</td>
                                    <td class="text-right">{{number_format($supplier_target_preparation->amount)}}</td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            @can('Delete Supplier Target Preparation')
                                                <button type="button"
                                                        onclick="deleteModelItem('SupplierTargetPreparation', {{$supplier_target_preparation->id}}, 'collection-tr-{{$supplier_target_preparation->id}}');"
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

                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
<script>
    function shareDetailsWhatsApp(efdId, rowId) {
        const row = document.querySelector(`tr[data-efd="${efdId}"][data-row-id="${rowId}"]`);
        if (!row) return;

        const efdName = row.querySelector('.efd-name').textContent.trim();
        const accName = row.querySelector('.acc-name').textContent.trim();
        const bankDetails = Array.from(row.querySelectorAll('.bank-details div'))
            .map(div => div.textContent.trim())
            .join('\n');
        const amount = row.querySelector('.amount').textContent.trim();

        let message = `EFD Details Report\n\n`;
        message += `EFD: ${efdName}\n`;
        message += `${accName}\n`;
        message += `${bankDetails}\n`;
        message += `Amount: ${amount}`;

        const encodedMessage = encodeURIComponent(message);
        window.open(`https://wa.me/?text=${encodedMessage}`, '_blank');
    }

    function copyDetailsToClipboard(efdId, rowId) {
        const row = document.querySelector(`tr[data-efd="${efdId}"][data-row-id="${rowId}"]`);
        if (!row) return;

        const efdName = row.querySelector('.efd-name').textContent.trim();
        const accName = row.querySelector('.acc-name').textContent.trim();
        const bankDetails = Array.from(row.querySelectorAll('.bank-details div'))
            .map(div => div.textContent.trim())
            .join('\n');
        const amount = row.querySelector('.amount').textContent.trim();

        let text = `EFD Details Report\n\n`;
        text += `EFD: ${efdName}\n`;
        text += `${accName}\n`;
        text += `${bankDetails}\n`;
        text += `Amount: ${amount}`;

        navigator.clipboard.writeText(text).then(() => {
            const button = row.querySelector('.btn-info');
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="fa fa-check"></i>';
            setTimeout(() => {
                button.innerHTML = originalHtml;
            }, 2000);
        }).catch(err => {
            console.error('Failed to copy text: ', err);
        });
    }
</script>
