{{-- project_documents.blade.php --}}
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Project Documents
                <div class="float-right">
                    @can('Add Document')
                        <button type="button" onclick="loadFormModal('project_document_form', {className: 'ProjectDocument'}, 'Upload New Document', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Document</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Documents</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form name="document_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Project</span>
                                                    </div>
                                                    <select name="project_id" id="input-project" class="form-control">
                                                        <option value="">All Projects</option>
                                                        @foreach ($projects as $project)
                                                            <option value="{{ $project->id }}">{{ $project->project_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Type</span>
                                                    </div>
                                                    <select name="document_type" id="input-document-type" class="form-control">
                                                        <option value="">All Types</option>
                                                        <option value="contract">Contract</option>
                                                        <option value="drawing">Drawing</option>
                                                        <option value="report">Report</option>
                                                        <option value="other">Other</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-2">
                                                <div>
                                                    <button type="submit" name="submit" class="btn btn-sm btn-primary">Show</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Project</th>
                                    <th>Type</th>
                                    <th>File Name</th>
                                    <th>Size</th>
                                    <th>Uploaded By</th>
                                    <th>Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($documents as $document)
                                    <tr id="document-tr-{{$document->id}}">
                                        <td class="text-center">{{$loop->index + 1}}</td>
                                        <td>{{ $document->project->project_name }}</td>
                                        <td>{{ ucfirst($document->document_type) }}</td>
                                        <td>{{ $document->file_name }}</td>
                                        <td>{{ number_format($document->file_size / 1024, 2) }} KB</td>
                                        <td>{{ $document->uploader->name }}</td>
                                        <td>
                                            @if($document->status == 'active')
                                                <div class="badge badge-success">{{ $document->status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $document->status}}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-info" href="{{ url($document->file_path) }}" target="_blank">
                                                    <i class="fa fa-download"></i>
                                                </a>
                                                @can('Edit Document')
                                                    <button type="button"
                                                            onclick="loadFormModal('project_document_form', {className: 'ProjectDocument', id: {{$document->id}}}, 'Edit Document', 'modal-md');"
                                                            class="btn btn-sm btn-primary">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete Document')
                                                    <button type="button"
                                                            onclick="deleteModelItem('ProjectDocument', {{$document->id}}, 'document-tr-{{$document->id}}');"
                                                            class="btn btn-sm btn-danger">
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
    </div>
@endsection
