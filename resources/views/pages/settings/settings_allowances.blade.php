@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Allowances
                <div class="float-right">
                    <button type="button" onclick="loadFormModal('settings_allowance_form', {className: 'Allowance'}, 'Create New Allowance', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Recruitment Request</button>
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Staff Allowances</h3>
                    </div>
                    <div class="block-content">
                        <p>This is a list containing all the possible allowances that staff can be subscribed to</p>
                        <table class="table table-striped table-vcenter">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Name</th>
                                <th class="d-none d-sm-table-cell" style="width: 30%;">Description</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($allowances as $allowance)
                            <tr id="allowance-tr-{{$allowance->id}}">
                                <td class="text-center">
                                    {{$loop->index + 1}}
                                </td>
                                <td class="font-w600">{{ $allowance->name }}</td>
                                <td class="d-none d-sm-table-cell">{{ $allowance->description }}
                                </td>
                                <td class="text-center" >
                                    <div class="btn-group">
                                        <button type="button" onclick="loadFormModal('settings_allowance_form', {className: 'Allowance', id: {{$allowance->id}}}, 'Edit {{$allowance->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <button type="button" onclick="deleteModelItem('Allowance', {{$allowance->id}}, 'allowance-tr-{{$allowance->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                            <i class="fa fa-times"></i>
                                        </button>
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
@endsection
