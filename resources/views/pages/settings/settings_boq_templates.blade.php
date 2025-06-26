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
                                <th>Specifications</th>
                                <th>Measurements</th>
                                <th>Created By</th>
                                <th class="text-center">Status</th>
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
                                            @if($template->buildingType->parent_id)
                                                {{-- This is a child building type --}}
                                                <div>
                                                    <small class="text-muted">{{ $template->buildingType->parent->name ?? 'Unknown Parent' }}</small>
                                                    <br>
                                                    <span class="badge badge-secondary">
                                                        <i class="fas fa-level-up-alt fa-rotate-90 mr-1"></i>
                                                        {{ $template->buildingType->name }}
                                                    </span>
                                                </div>
                                            @else
                                                {{-- This is a parent building type --}}
                                                <span class="badge badge-primary">
                                                    <i class="fas fa-building mr-1"></i>
                                                    {{ $template->buildingType->name }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>
                                            @if($template->roof_type)
                                                <span class="badge badge-light">{{ ucwords(str_replace('_', ' ', $template->roof_type)) }}</span>
                                            @endif
                                            @if($template->no_of_rooms)
                                                <span class="badge badge-light">{{ $template->no_of_rooms }} Room{{ $template->no_of_rooms == '1' ? '' : 's' }}</span>
                                            @endif
                                            @if(!$template->roof_type && !$template->no_of_rooms)
                                                <span class="text-muted">-</span>
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        <small>
                                            @if($template->square_metre || $template->run_metre)
                                                @if($template->square_metre)
                                                    <span class="text-info">{{ number_format($template->square_metre, 2) }} SQM</span>
                                                @endif
                                                @if($template->square_metre && $template->run_metre)
                                                    <br>
                                                @endif
                                                @if($template->run_metre)
                                                    <span class="text-success">{{ number_format($template->run_metre, 2) }} RM</span>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </small>
                                    </td>
                                    <td>{{ $template->creator->name ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($template->is_active)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
{{--                                            @can('View BOQ Template')--}}
                                                <button type="button" onclick="viewTemplate({{$template->id}});" class="btn btn-sm btn-info js-tooltip-enabled" data-toggle="tooltip" title="View Details">
                                                    <i class="fa fa-eye"></i>
                                                </button>
{{--                                            @endcan--}}
                                            @can('View BOQ Template')
                                                <button type="button" onclick="viewTemplateReport({{$template->id}});" class="btn btn-sm btn-success js-tooltip-enabled" data-toggle="tooltip" title="Template Report">
                                                    <i class="fa fa-table"></i>
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
            loadFormModal('boq_template_details', {className: 'BoqTemplate', id: templateId}, 'Template Details', 'modal-xl');
        }

        function viewTemplateReport(templateId) {
            // Open template report in a new window/tab
            window.open('{{ route("hr_settings_boq_template_report", ["templateId" => ":templateId"]) }}'.replace(':templateId', templateId), '_blank');
        }
    </script>
@endsection
