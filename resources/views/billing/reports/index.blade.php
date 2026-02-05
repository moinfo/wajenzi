@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            <div class="row">
                <div class="col-md-6">
                    <h1>Billing Reports</h1>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{ route('billing.dashboard') }}" class="btn btn-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Billing
                    </a>
                </div>
            </div>
        </div>

        <!-- Reports Cards -->
        <div class="row">
            <div class="col-md-4">
                <div class="block block-themed">
                    <div class="block-content block-content-full text-center">
                        <div class="py-30">
                            <div class="mb-20">
                                <i class="fa fa-chart-line fa-4x text-primary"></i>
                            </div>
                            <h4>Sales Report</h4>
                            <p class="text-muted">View detailed sales summary with trends and top clients</p>
                            <a href="{{ route('billing.reports.sales') }}" class="btn btn-primary">
                                <i class="fa fa-chart-bar"></i> View Sales Report
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="block block-themed">
                    <div class="block-content block-content-full text-center">
                        <div class="py-30">
                            <div class="mb-20">
                                <i class="fa fa-money-bill fa-4x text-success"></i>
                            </div>
                            <h4>Payment Report</h4>
                            <p class="text-muted">Track payments received and payment methods</p>
                            <a href="#" class="btn btn-success">
                                <i class="fa fa-money-bill"></i> View Payment Report
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="block block-themed">
                    <div class="block-content block-content-full text-center">
                        <div class="py-30">
                            <div class="mb-20">
                                <i class="fa fa-clock fa-4x text-warning"></i>
                            </div>
                            <h4>Aging Report</h4>
                            <p class="text-muted">View outstanding invoices and aging analysis</p>
                            <a href="#" class="btn btn-warning">
                                <i class="fa fa-clock"></i> View Aging Report
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection