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
            <div class="content-heading">Expenses
                <div class="float-right">
                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Add Expense"))
                        <button type="button" onclick="loadFormModal('expense_form', {className: 'Expense'}, 'Create New Expenses', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Expenses</button>
                    @endif
                </div>
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-gd-lake">
                        <h3 class="block-title">All Expenses</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="expenses_search" action="{{route('expenses_search')}}" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-2">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Start</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-2">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon2">End</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon4">Category</span>
                                                    </div>
                                                    <select name="expenses_category_id" id="input-expenses-category-id" class="form-control" aria-describedby="basic-addon4">
                                                        <option value="">All</option>
                                                        @foreach ($expense_categories as $expense_category)
                                                            <option value="{{ $expense_category->id }}"> {{ $expense_category->name }} </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon4">Sub Category</span>
                                                    </div>
                                                    <select name="expenses_sub_category_id" id="input-expenses-sub-category-id" class="form-control" aria-describedby="basic-addon4">
                                                        <option value="">All</option>
                                                        @foreach ($expense_sub_categories as $expense_sub_category)
                                                            <option value="{{ $expense_sub_category->id }}"> {{ $expense_sub_category->name }} </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-2">
                                                <div>
                                                    <button type="submit" name="submit"  class="btn btn-sm btn-primary">Show</button>
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
                                    <th>Date</th>
{{--                                    <th>Supervisor Name</th>--}}
                                    <th>Expenses Sub Category</th>
                                    <th>Expenses Category</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Attachment</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $sum = 0;
//                                dump($expenses);
                                ?>
                                @foreach($expenses as $expense)
                                    <?php
                                    $sum += $expense->amount;
                                    ?>
                                    <tr id="expense-tr-{{$expense->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td class="font-w600">{{ $expense->date }}</td>
{{--                                        <td class="font-w600">{{ $expense->supervisor->name ?? $expense->supervisor_name}}</td>--}}
                                        <td class="font-w600">{{ $expense->expensesSubCategory->name ?? $expense->sub_category }}</td>
                                        <td class="font-w600">{{ $expense->expensesSubCategory->expensesCategory->name ?? $expense->category }}</td>
                                        <td class="d-none d-sm-table-cell">{{ $expense->description }}
                                        <td class="font-w600">{{ number_format($expense->amount, 2) }}</td>
                                        <td class="text-center">
                                            @if($expense->file != null)
                                                <a href="{{ url("$expense->file") }}">Attachment</a>
                                            @else
                                                No File
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Edit Expense"))
                                                    <button type="button"
                                                            onclick="loadFormModal('expense_form', {className: 'Expense', id: {{$expense->id}}}, 'Edit Expenses', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endif

                                                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Delete Expense"))
                                                        <button type="button"
                                                                onclick="deleteModelItem('Expense', {{$expense->id}}, 'expense-tr-{{$expense->id}}');"
                                                                class="btn btn-sm btn-danger js-tooltip-enabled"
                                                                data-toggle="tooltip" title="Delete"
                                                                data-original-title="Delete">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    @endif

                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td class="text-right text-dark" colspan="6"><b>{{number_format($sum,2)}}</b></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection


