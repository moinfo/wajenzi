@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <i class="fa fa-money-bill-wave"></i> Process Payment
            <div class="float-right">
                <a href="{{ route('labor.payments.index') }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="block">
                    <div class="block-header block-header-default bg-success">
                        <h3 class="block-title text-white">Payment Processing</h3>
                    </div>
                    <div class="block-content">
                        <form action="{{ route('labor.payments.process', $phase->id) }}" method="POST">
                            @csrf

                            <div class="alert alert-info">
                                <h5><i class="fa fa-info-circle"></i> Payment Details</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Contract:</strong> {{ $phase->contract?->contract_number }}</p>
                                        <p><strong>Artisan:</strong> {{ $phase->contract?->artisan?->name }}</p>
                                        <p><strong>Phase:</strong> {{ $phase->phase_name }} ({{ $phase->percentage }}%)</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Milestone:</strong> {{ $phase->milestone_description }}</p>
                                        <p><strong>Amount:</strong>
                                            <span class="h4 text-success">
                                                {{ number_format($phase->amount, 2) }} {{ $phase->contract?->currency }}
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="payment_reference">Payment Reference <span class="text-danger">*</span></label>
                                <input type="text" name="payment_reference" id="payment_reference" class="form-control"
                                    required placeholder="e.g., Bank Transfer Ref: TRX123456789">
                                <small class="text-muted">Enter bank transfer reference, cheque number, or mobile money transaction ID</small>
                            </div>

                            <div class="form-group">
                                <label for="notes">Payment Notes</label>
                                <textarea name="notes" id="notes" class="form-control" rows="3"
                                    placeholder="Any additional notes about this payment..."></textarea>
                            </div>

                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <a href="{{ route('labor.payments.index') }}" class="btn btn-secondary btn-block">
                                        Cancel
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-success btn-block"
                                        onclick="return confirm('Confirm payment of {{ number_format($phase->amount, 2) }} {{ $phase->contract?->currency }}?')">
                                        <i class="fa fa-check"></i> Confirm Payment
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Artisan Bank Details</h3>
                    </div>
                    <div class="block-content">
                        @if($phase->contract?->artisan)
                            <p><strong>Name:</strong> {{ $phase->contract->artisan->name }}</p>
                            <p><strong>Phone:</strong> {{ $phase->contract->artisan->phone ?? 'N/A' }}</p>
                            @if($phase->contract->artisan->nmb_account)
                                <p><strong>NMB Account:</strong> {{ $phase->contract->artisan->nmb_account }}</p>
                            @endif
                            @if($phase->contract->artisan->crdb_account)
                                <p><strong>CRDB Account:</strong> {{ $phase->contract->artisan->crdb_account }}</p>
                            @endif
                            @if($phase->contract->artisan->account_name)
                                <p><strong>Account Name:</strong> {{ $phase->contract->artisan->account_name }}</p>
                            @endif
                        @else
                            <p class="text-muted">No bank details available</p>
                        @endif
                    </div>
                </div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Contract Summary</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td>Total Contract:</td>
                                <td class="text-right">{{ number_format($phase->contract?->total_amount, 0) }}</td>
                            </tr>
                            <tr>
                                <td>Already Paid:</td>
                                <td class="text-right text-success">{{ number_format($phase->contract?->amount_paid, 0) }}</td>
                            </tr>
                            <tr>
                                <td>This Payment:</td>
                                <td class="text-right text-primary">{{ number_format($phase->amount, 0) }}</td>
                            </tr>
                            <tr class="border-top">
                                <td><strong>Balance After:</strong></td>
                                <td class="text-right"><strong>{{ number_format($phase->contract?->balance_amount - $phase->amount, 0) }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
