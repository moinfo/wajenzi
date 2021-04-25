@extends('layouts.backend')
@section('css_before')
    <!-- Page JS Plugins CSS -->
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables/dataTables.bootstrap4.css') }}">
@endsection

@section('js_after')
    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>

    <!-- Page JS Code -->
    <script src="{{ asset('js/pages/tables_datatables.js') }}"></script>

    <script>
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd'
        });
    </script>
@endsection
@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    <button type="button" onclick="loadFormModal('settings_deduction_form', {className: 'Deduction'}, 'Create New Deduction', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Deduction</button>
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Deductions</h3>
                    </div>
                    <div class="block-content">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Name</th>
                                    <th>Nature</th>
                                    <th>Abbreviation</th>
                                    <th class="d-none d-sm-table-cell" style="width: 30%;">Description</th>
                                    <th>Registration Number</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($deductions as $deduction)
                                    <tr id="deduction-tr-{{$deduction->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td class="font-w600">{{ $deduction->name }}</td>
                                        <td class="font-w600">{{ $deduction->nature }}</td>
                                        <td class="font-w600">{{ $deduction->abbreviation }}</td>
                                        <td class="d-none d-sm-table-cell">{{ $deduction->description }}
                                        </td>
                                        <td class="font-w600">{{ $deduction->registration_number }}</td>
                                        <td class="text-center" >
                                            <div class="btn-group">
                                                <button type="button" onclick="loadFormModal('settings_deduction_form', {className: 'Deduction', id: {{$deduction->id}}}, 'Edit {{$deduction->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <button type="button" onclick="deleteModelItem('deduction', {{$deduction->id}}, 'deduction-tr-{{$deduction->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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
    </div>
@endsection
