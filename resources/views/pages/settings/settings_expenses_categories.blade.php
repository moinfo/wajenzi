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
            <div class="content-heading">EXPENSES CATEGORIES
                <div class="float-right">
                    <button type="button" onclick="loadFormModal('settings_expenses_category_form', {className: 'ExpensesCategory'}, 'Create New Expense Category', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Expense Category</button>
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">EXPENSES CATEGORIES</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Name</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($expenses_categories as $expenses_category)
                                <tr id="expenses_category-tr-{{$expenses_category->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $expenses_category->name }}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            <button type="button" onclick="loadFormModal('settings_expenses_category_form', {className: 'ExpensesCategory', id: {{$expenses_category->id}}}, 'Edit {{$expenses_category->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                <i class="fa fa-pencil"></i>
                                            </button>
{{--                                            <button type="button" onclick="deleteModelItem('ExpensesCategory', {{$expenses_category->id}}, 'expenses_category-tr-{{$expenses_category->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">--}}
{{--                                                <i class="fa fa-times"></i>--}}
{{--                                            </button>--}}
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
