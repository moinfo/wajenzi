@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Sales Summary Report</h1>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('billing.dashboard') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Billing
                    </a>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="block block-themed">
            <div class="block-header">
                <h3 class="block-title">Report Filters</h3>
            </div>
            <div class="block-content">
                <form method="GET" action="{{ route('billing.reports.sales') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>From Date</label>
                                <input type="date" name="from_date" class="form-control" 
                                       value="{{ $from_date }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>To Date</label>
                                <input type="date" name="to_date" class="form-control" 
                                       value="{{ $to_date }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Group By</label>
                                <select name="group_by" class="form-control">
                                    <option value="month" {{ $group_by == 'month' ? 'selected' : '' }}>Month</option>
                                    <option value="week" {{ $group_by == 'week' ? 'selected' : '' }}>Week</option>
                                    <option value="day" {{ $group_by == 'day' ? 'selected' : '' }}>Day</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="form-control btn btn-primary">
                                    <i class="fa fa-search"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
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
                                <i class="fa fa-chart-line fa-3x text-info"></i>
                            </div>
                            <div class="font-size-h3 font-weight-bold text-dark">TZS {{ number_format($totalSales, 2) }}</div>
                            <div class="font-size-sm font-weight-medium text-muted text-uppercase">Total Sales</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="block block-themed">
                    <div class="block-content block-content-full">
                        <div class="py-20 text-center">
                            <div class="mb-10">
                                <i class="fa fa-money-bill fa-3x text-success"></i>
                            </div>
                            <div class="font-size-h3 font-weight-bold text-dark">TZS {{ number_format($totalPaid, 2) }}</div>
                            <div class="font-size-sm font-weight-medium text-muted text-uppercase">Total Paid</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="block block-themed">
                    <div class="block-content block-content-full">
                        <div class="py-20 text-center">
                            <div class="mb-10">
                                <i class="fa fa-clock fa-3x text-warning"></i>
                            </div>
                            <div class="font-size-h3 font-weight-bold text-dark">TZS {{ number_format($totalOutstanding, 2) }}</div>
                            <div class="font-size-sm font-weight-medium text-muted text-uppercase">Outstanding</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Sales Data Table -->
            <div class="col-md-8">
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Sales by {{ ucfirst($group_by) }}</h3>
                    </div>
                    <div class="block-content">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Period</th>
                                        <th>Invoice Count</th>
                                        <th>Total Sales</th>
                                        <th>Total Paid</th>
                                        <th>Outstanding</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($salesData as $data)
                                        <tr>
                                            <td>{{ $data->period }}</td>
                                            <td>{{ number_format($data->invoice_count) }}</td>
                                            <td>TZS {{ number_format($data->total_sales, 2) }}</td>
                                            <td>TZS {{ number_format($data->total_paid, 2) }}</td>
                                            <td>TZS {{ number_format($data->total_outstanding, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No sales data found for the selected period</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Clients -->
            <div class="col-md-4">
                <div class="block block-themed">
                    <div class="block-header">
                        <h3 class="block-title">Top Clients</h3>
                    </div>
                    <div class="block-content">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Invoices</th>
                                        <th>Sales</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topClients as $client)
                                        <tr>
                                            <td>{{ $client->client_name }}</td>
                                            <td>{{ number_format($client->invoice_count) }}</td>
                                            <td>TZS {{ number_format($client->total_sales, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">No client data found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection