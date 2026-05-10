@extends('layouts.backend')
@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Currencies</h1>
            <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">Settings</li>
                    <li class="breadcrumb-item active">Currencies</li>
                </ol>
            </nav>
        </div>
    </div>
</div>
<div class="content">
    <div class="block block-rounded">
        <div class="block-header block-header-default">
            <h3 class="block-title">Currencies &amp; Exchange Rates</h3>
            <div class="block-options">
                @can('Add Currency')
                <button type="button" class="btn btn-alt-primary btn-sm"
                    onclick="loadFormModal('currency_form', {className: 'Currency'}, 'New Currency', 'modal-md')">
                    <i class="fa fa-plus"></i> Add Currency
                </button>
                @endcan
            </div>
        </div>
        <div class="block-content block-content-full">
            <div class="alert alert-info fs-sm mb-3">
                <i class="fa fa-info-circle me-1"></i>
                <strong>Rate to USD:</strong> Enter how many units of this currency equal 1 USD.
                Example: TZS = 2640 &nbsp;|&nbsp; USD = 1.0 &nbsp;|&nbsp; EUR = 0.92
            </div>
            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Symbol</th>
                        <th class="text-end">Rate to USD</th>
                        <th class="text-center">Base</th>
                        <th class="text-center">Active</th>
                        <th class="text-center" style="width:100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($objects as $object)
                    <tr>
                        <td><span class="badge bg-primary">{{ $object->code ?: $object->symbol }}</span></td>
                        <td>{{ $object->name }}</td>
                        <td>{{ $object->symbol }}</td>
                        <td class="text-end fw-semibold">{{ number_format($object->rate_to_usd, 4) }}</td>
                        <td class="text-center">
                            @if($object->is_base === 'YES')
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($object->is_active)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-danger">No</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @can('Edit Currency')
                            <button type="button" class="btn btn-sm btn-alt-secondary"
                                onclick="loadFormModal('currency_form', {className: 'Currency', id: {{ $object->id }}}, 'Edit Currency', 'modal-md')">
                                <i class="fa fa-pencil-alt"></i>
                            </button>
                            @endcan
                            @can('Delete Currency')
                            <button type="button" class="btn btn-sm btn-alt-danger"
                                onclick="deleteModelItem('Currency', {{ $object->id }})">
                                <i class="fa fa-trash"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
