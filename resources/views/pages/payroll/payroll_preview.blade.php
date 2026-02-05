@extends('layouts.backend')

@section('content')
<div class="wajenzi-dashboard">
    <div class="container-fluid">
        <div class="content-heading d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Payroll Preview</h2>
            <div>
                <a href="{{ route('payroll_administration') }}" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back to Payroll Administration
                </a>
            </div>
        </div>
        <div>
            <div class="block block-themed">
                <div class="block-header bg-wajenzi-gradient">
                    <h3 class="block-title">Current Month Payroll Preview</h3>
                </div>
                @include('forms.payroll_form')
            </div>
        </div>
    </div>
</div>
@endsection
