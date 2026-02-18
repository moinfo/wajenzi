@extends('layouts.backend')

@section('content')
    <!-- Hero -->
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Activity Templates</h1>
                <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">Settings</li>
                        <li class="breadcrumb-item active" aria-current="page">Activity Templates</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <!-- END Hero -->

    <!-- Page Content -->
    <div class="content">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Activity Templates</h3>
                <div class="block-options">
                    @can('Add Activity Template')
                    <button type="button" class="btn btn-alt-primary btn-sm"
                        onclick="loadFormModal('settings_activity_template_form', {className: 'ProjectActivityTemplate'}, 'New Activity Template', 'modal-lg')">
                        <i class="fa fa-plus"></i> New Template
                    </button>
                    @endcan
                </div>
            </div>
            <div class="block-content block-content-full">
                <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th style="width: 70px;">Code</th>
                            <th>Name</th>
                            <th>Phase</th>
                            <th>Discipline</th>
                            <th class="text-center" style="width: 80px;">Days</th>
                            <th style="width: 90px;">Predecessor</th>
                            <th>Role</th>
                            <th class="text-center" style="width: 70px;">Active</th>
                            <th class="text-center" style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($objects as $object)
                            <tr>
                                <td class="text-center">{{ $object->sort_order }}</td>
                                <td><span class="badge bg-primary">{{ $object->activity_code }}</span></td>
                                <td>{{ $object->name }}</td>
                                <td>{{ $object->phase }}</td>
                                <td>{{ $object->discipline }}</td>
                                <td class="text-center">{{ $object->duration_days }}</td>
                                <td>
                                    @if($object->predecessor_code)
                                        <span class="badge bg-secondary">{{ $object->predecessor_code }}</span>
                                    @else
                                        <span class="text-muted">Start</span>
                                    @endif
                                </td>
                                <td>{{ $object->role->name ?? '-' }}</td>
                                <td class="text-center">
                                    @if($object->is_active)
                                        <span class="badge bg-success">Yes</span>
                                    @else
                                        <span class="badge bg-danger">No</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @can('Edit Activity Template')
                                    <button type="button" class="btn btn-sm btn-alt-secondary"
                                        onclick="loadFormModal('settings_activity_template_form', {className: 'ProjectActivityTemplate', id: {{ $object->id }}}, 'Edit Activity Template', 'modal-lg')">
                                        <i class="fa fa-pencil-alt"></i>
                                    </button>
                                    @endcan
                                    @can('Delete Activity Template')
                                    <button type="button" class="btn btn-sm btn-alt-danger"
                                        onclick="deleteModelItem('ProjectActivityTemplate', {{ $object->id }})">
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
    <!-- END Page Content -->
@endsection
