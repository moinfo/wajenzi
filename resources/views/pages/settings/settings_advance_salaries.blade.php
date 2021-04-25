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
                    <button type="button" onclick="loadFormModal('settings_advance_salary_form', {className: 'AdvanceSalary'}, 'Create New AdvanceSalary', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New AdvanceSalary</button>
                </div>
            </div>
            <div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Advance Salaries</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Date</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $sum = 0;
                            ?>
                            @foreach($advance_salaries as $advance_salary)
                                <?php
                                    $sum += $advance_salary->amount;
                                ?>
                                <tr id="advance_salary-tr-{{$advance_salary->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $advance_salary->date }}</td>
                                    <td class="font-w600">{{ $advance_salary->staff->name }}</td>
                                    <td class="font-w600">{{ $advance_salary->description}}</td>
                                    <td class="text-right">{{ number_format($advance_salary->amount) }}
                                    </td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            <button type="button" onclick="loadFormModal('settings_advance_salary_form', {className: 'AdvanceSalary', id: {{$advance_salary->id}}}, 'Edit {{$advance_salary->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            <button type="button" onclick="deleteModelItem('AdvanceSalary', {{$advance_salary->id}}, 'advance_salary-tr-{{$advance_salary->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="text-right">{{number_format($sum)}}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
