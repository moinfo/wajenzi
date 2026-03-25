{{-- project_material.blade.php (Show Material Detail) --}}
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">
                <a href="{{ route('project_materials') }}" class="btn btn-sm btn-secondary mr-2">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
                Material Details
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">{{ $material->name }}</h3>
                    </div>
                    <div class="block-content">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="block block-rounded">
                                    <div class="block-header block-header-default">
                                        <h3 class="block-title">Basic Information</h3>
                                    </div>
                                    <div class="block-content">
                                        <table class="table table-borderless">
                                            <tr>
                                                <td width="40%"><strong>Name:</strong></td>
                                                <td>{{ $material->name }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Description:</strong></td>
                                                <td>{{ $material->description ?? '-' }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Unit:</strong></td>
                                                <td>{{ $material->unit }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Current Price:</strong></td>
                                                <td class="text-success font-w600">TZS {{ number_format($material->current_price ?? 0, 2) }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="block block-rounded">
                                    <div class="block-header block-header-default">
                                        <h3 class="block-title">Inventory Summary</h3>
                                    </div>
                                    <div class="block-content">
                                        <table class="table table-borderless">
                                            <tr>
                                                <td width="40%"><strong>Total Inventory:</strong></td>
                                                <td class="font-w600">{{ number_format($totalQuantity ?? 0, 2) }} {{ $material->unit }}</td>
                                            </tr>

                                            <tr>
                                                <td><strong>Created:</strong></td>
                                                <td>{{ $material->created_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Updated:</strong></td>
                                                <td>{{ $material->updated_at->format('d/m/Y H:i') }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="block block-rounded mt-4">
                            <div class="block-header block-header-default">
                                <h3 class="block-title">Inventory Records</h3>
                            </div>
                            <div class="block-content">
                                @if($material->inventory && $material->inventory->count() > 0)
                                    <table class="table table-bordered table-striped table-vcenter">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Project</th>
                                                <th>Quantity</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($material->inventory as $inv)
                                                <tr>
                                                    <td>{{ $inv->id }}</td>
                                                    <td>{{ $inv->project?->project_name ?? '-' }}</td>
                                                    <td class="font-w600">{{ number_format($inv->quantity, 2) }} {{ $material->unit }}</td>
                                                    <td>{{ $inv->created_at->format('d/m/Y') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <p class="text-muted text-center">No inventory records found.</p>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4">
                            @can('Edit Material')
                                <button type="button" onclick="loadFormModal('project_material_form', {className: 'ProjectMaterial', id: {{ $material->id }}}, 'Edit Material', 'modal-md');" class="btn btn-primary">
                                    <i class="fa fa-pencil"></i> Edit Material
                                </button>
                            @endcan
                            @can('Delete Material')
                                <button type="button" onclick="deleteModelItem('ProjectMaterial', {{ $material->id }}, 'material-tr-{{ $material->id }}');" class="btn btn-danger">
                                    <i class="fa fa-times"></i> Delete Material
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
