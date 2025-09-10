@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">
            <h1>Billing Dashboard</h1>
        </div>

        <!-- Key Metrics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="block block-themed">
                    <div class="block-content block-content-full">
                        <div class="py-20 text-center">
                            <div class="mb-10">
                                <i class="fa fa-file-invoice fa-3x text-primary"></i>
                            </div>
                            <div class="font-size-h3 font-weight-bold text-dark">{{ number_format($totalInvoices) }}</div>
                            <div class="font-size-sm font-weight-medium text-muted text-uppercase">Total Invoices</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="block block-themed">
                    <div class="block-content block-content-full">
                        <div class="py-20 text-center">
                            <div class="mb-10">
                                <i class="fa fa-users fa-3x text-success"></i>
                            </div>
                            <div class="font-size-h3 font-weight-bold text-dark">{{ number_format($totalClients) }}</div>
                            <div class="font-size-sm font-weight-medium text-muted text-uppercase">Active Clients</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="block block-themed">
                    <div class="block-content block-content-full">
                        <div class="py-20 text-center">
                            <div class="mb-10">
                                <i class="fa fa-chart-line fa-3x text-info"></i>
                            </div>
                            <div class="font-size-h3 font-weight-bold text-dark">TZS {{ number_format($totalRevenue, 2) }}</div>
                            <div class="font-size-sm font-weight-medium text-muted text-uppercase">Total Revenue</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="block block-themed">
                    <div class="block-content block-content-full">
                        <div class="py-20 text-center">
                            <div class="mb-10">
                                <i class="fa fa-exclamation-triangle fa-3x text-warning"></i>
                            </div>
                            <div class="font-size-h3 font-weight-bold text-dark">TZS {{ number_format($outstandingAmount, 2) }}</div>
                            <div class="font-size-sm font-weight-medium text-muted text-uppercase">Outstanding</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Invoices -->
            <div class="col-md-6">
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Recent Invoices</h3>
                        <div class="block-options">
                            <a href="{{ route('billing.invoices.index') }}" class="btn btn-sm btn-primary">
                                View All
                            </a>
                        </div>
                    </div>
                    <div class="block-content">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Client</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentInvoices as $invoice)
                                        <tr>
                                            <td>
                                                <a href="{{ route('billing.invoices.show', $invoice) }}">
                                                    {{ $invoice->document_number }}
                                                </a>
                                            </td>
                                            <td>{{ $invoice->client->company_name }}</td>
                                            <td>{{ $invoice->currency_code }} {{ number_format($invoice->total_amount, 2) }}</td>
                                            <td>
                                                <span class="badge badge-{{ $invoice->status_color }} badge-sm">
                                                    {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No invoices found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overdue Invoices -->
            <div class="col-md-6">
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Overdue Invoices</h3>
                        <div class="block-options">
                            <a href="{{ route('billing.invoices.index', ['status' => 'overdue']) }}" class="btn btn-sm btn-danger">
                                View All
                            </a>
                        </div>
                    </div>
                    <div class="block-content">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Client</th>
                                        <th>Due Date</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($overdueInvoices as $invoice)
                                        <tr>
                                            <td>
                                                <a href="{{ route('billing.invoices.show', $invoice) }}">
                                                    {{ $invoice->document_number }}
                                                </a>
                                            </td>
                                            <td>{{ $invoice->client->company_name }}</td>
                                            <td>
                                                {{ $invoice->due_date->format('d/m/Y') }}
                                                <small class="text-danger">
                                                    ({{ $invoice->due_date->diffForHumans() }})
                                                </small>
                                            </td>
                                            <td>{{ $invoice->currency_code }} {{ number_format($invoice->balance_amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-success">No overdue invoices</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Payments -->
            <div class="col-md-6">
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Recent Payments</h3>
                        <div class="block-options">
                            <a href="{{ route('billing.payments.index') }}" class="btn btn-sm btn-success">
                                View All
                            </a>
                        </div>
                    </div>
                    <div class="block-content">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>Payment #</th>
                                        <th>Invoice</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentPayments as $payment)
                                        <tr>
                                            <td>{{ $payment->payment_number }}</td>
                                            <td>
                                                <a href="{{ route('billing.invoices.show', $payment->document) }}">
                                                    {{ $payment->document->document_number }}
                                                </a>
                                            </td>
                                            <td>{{ $payment->document->currency_code }} {{ number_format($payment->amount, 2) }}</td>
                                            <td>
                                                <span class="badge badge-light">
                                                    {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                                                </span>
                                            </td>
                                            <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No payments found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice Status Breakdown -->
            <div class="col-md-6">
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Invoice Status Breakdown</h3>
                    </div>
                    <div class="block-content">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Count</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($statusBreakdown as $status)
                                        <tr>
                                            <td>
                                                @php
                                                    $statusColors = [
                                                        'draft' => 'secondary',
                                                        'pending' => 'warning',
                                                        'sent' => 'info',
                                                        'viewed' => 'primary',
                                                        'accepted' => 'success',
                                                        'rejected' => 'danger',
                                                        'partial_paid' => 'warning',
                                                        'paid' => 'success',
                                                        'overdue' => 'danger',
                                                        'cancelled' => 'dark',
                                                        'void' => 'dark'
                                                    ];
                                                @endphp
                                                <span class="badge badge-{{ $statusColors[$status->status] ?? 'secondary' }}">
                                                    {{ ucfirst(str_replace('_', ' ', $status->status)) }}
                                                </span>
                                            </td>
                                            <td>{{ number_format($status->count) }}</td>
                                            <td>TZS {{ number_format($status->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Revenue Chart -->
        <div class="row">
            <div class="col-12">
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Monthly Revenue Trend</h3>
                    </div>
                    <div class="block-content">
                        <canvas id="revenueChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Quick Actions</h3>
                    </div>
                    <div class="block-content">
                        <div class="row">
                            <div class="col-md-3 text-center mb-3">
                                <a href="{{ route('billing.invoices.create') }}" class="btn btn-success btn-lg btn-block">
                                    <i class="fa fa-plus fa-2x mb-2"></i><br>
                                    Create Invoice
                                </a>
                            </div>
                            <div class="col-md-3 text-center mb-3">
                                <a href="{{ route('billing.quotations.create') }}" class="btn btn-info btn-lg btn-block">
                                    <i class="fa fa-file-alt fa-2x mb-2"></i><br>
                                    Create Quote
                                </a>
                            </div>
                            <div class="col-md-3 text-center mb-3">
                                <a href="{{ route('billing.clients.create') }}" class="btn btn-primary btn-lg btn-block">
                                    <i class="fa fa-user-plus fa-2x mb-2"></i><br>
                                    Add Client
                                </a>
                            </div>
                            <div class="col-md-3 text-center mb-3">
                                <a href="{{ route('billing.reports.sales') }}" class="btn btn-secondary btn-lg btn-block">
                                    <i class="fa fa-chart-bar fa-2x mb-2"></i><br>
                                    View Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const monthlyData = @json($monthlyRevenue);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyData.map(item => item.month),
            datasets: [{
                label: 'Monthly Revenue (TZS)',
                data: monthlyData.map(item => item.revenue),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'TZS ' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Revenue: TZS ' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush

@endsection