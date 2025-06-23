@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">
                Purchase Details
                <div class="float-right">
                    <a href="{{ route('purchases') }}" class="btn btn-rounded btn-outline-secondary min-width-125 mb-10">
                        <i class="fas fa-arrow-left"></i> Back to Purchases
                    </a>
                </div>
            </div>

            <!-- Purchase Details Card -->
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Purchase Information</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 30%;">Supplier</th>
                                        <td>{{ $purchase->supplier->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Item</th>
                                        <td>{{ $purchase->item->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Tax Invoice</th>
                                        <td>{{ $purchase->tax_invoice }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 30%;">Amount (Excl. VAT)</th>
                                        <td>{{ number_format($purchase->amount_vat_exc, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>VAT Amount</th>
                                        <td>{{ number_format($purchase->vat_amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Total Amount</th>
                                        <td>{{ number_format($purchase->total_amount, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 30%;">Date</th>
                                        <td>{{ $purchase->date }}</td>
                                    </tr>
                                    <tr>
                                        <th>Invoice Date</th>
                                        <td>{{ $purchase->invoice_date }}</td>
                                    </tr>
                                    <tr>
                                        <th>Purchase Type</th>
                                        <td>{{ $purchase->purchase_type == 1 ? 'VAT' : 'Exempt' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 30%;">Is Expense</th>
                                        <td>{{ $purchase->is_expense }}</td>
                                    </tr>
                                    <tr>
                                        <th>Created By</th>
                                        <td>{{ $purchase->user->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td>
                                            @if($purchase->status == 'PENDING')
                                                <div class="badge badge-warning">{{ $purchase->status}}</div>
                                            @elseif($purchase->status == 'APPROVED')
                                                <div class="badge badge-primary">{{ $purchase->status}}</div>
                                            @elseif($purchase->status == 'REJECTED')
                                                <div class="badge badge-danger">{{ $purchase->status}}</div>
                                            @elseif($purchase->status == 'PAID')
                                                <div class="badge badge-primary">{{ $purchase->status}}</div>
                                            @elseif($purchase->status == 'COMPLETED')
                                                <div class="badge badge-success">{{ $purchase->status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $purchase->status}}</div>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($purchase->file)
                    <div class="row mt-4">
                        <div class="col-12">
                            <a href="{{ url($purchase->file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-file-pdf"></i> View Document
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Approval Component -->
            <x-ringlesoft-approval-actions :model="$purchase" />
            
        </div>
    </div>
@endsection