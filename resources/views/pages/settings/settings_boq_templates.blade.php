@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">BOQ Templates
                <div class="float-right">
                    @can('Add BOQ Template')
                        <button type="button" onclick="loadFormModal('settings_boq_template_form', {className: 'BoqTemplate'}, 'Create New Template', 'modal-lg');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Template</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">BOQ Templates</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>Name</th>
                                <th>Building Type</th>
                                <th>Description</th>
                                <th>Created By</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Created</th>
                                <th class="text-center" style="width: 120px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($boq_templates as $template)
                                <tr id="template-tr-{{$template->id}}">
                                    <td class="text-center">{{$loop->index + 1}}</td>
                                    <td class="font-w600">{{ $template->name }}</td>
                                    <td>
                                        @if($template->buildingType)
                                            <span class="badge badge-primary">{{ $template->buildingType->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>{{ Str::limit($template->description ?? '-', 40) }}</td>
                                    <td>{{ $template->creator->name ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($template->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $template->created_at->format('M d, Y') }}</td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            @can('View BOQ Template')
                                                <button type="button" onclick="viewTemplate({{$template->id}});" class="btn btn-sm btn-info js-tooltip-enabled" data-toggle="tooltip" title="View Details">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                            @endcan
                                            @can('Edit BOQ Template')
                                                <button type="button" onclick="loadFormModal('settings_boq_template_form', {className: 'BoqTemplate', id: {{$template->id}}}, 'Edit {{$template->name}}', 'modal-lg');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan
                                            @can('Delete BOQ Template')
                                                <button type="button" onclick="deleteModelItem('BoqTemplate', {{$template->id}}, 'template-tr-{{$template->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete">
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

    <script>
        function viewTemplate(templateId) {
            // Load template details modal
            loadFormModal('boq_template_details', {id: templateId}, 'Template Details', 'modal-xl');
        }
    </script>
@endsection