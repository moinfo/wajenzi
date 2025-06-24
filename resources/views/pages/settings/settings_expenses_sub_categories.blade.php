@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">EXPENSES SUB CATEGORIES
                <div class="float-right">
                    @can('Add Expenses Sub Category')
                        <button type="button" onclick="loadFormModal('settings_expenses_sub_category_form', {className: 'ExpensesSubCategory'}, 'Create New Expense Category', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Expense Category</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">EXPENSES SUB CATEGORIES</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Expenses Sub Category</th>
                                <th>Expenses Category</th>
                                <th>IS Deducted</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($expenses_sub_categories as $expenses_sub_category)
                                <tr id="expenses_sub_category-tr-{{$expenses_sub_category->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $expenses_sub_category->name }}</td>
                                    <td class="font-w600">{{ $expenses_sub_category->expensesCategory->name ?? null}}</td>
                                    <td class="font-w600">{{ $expenses_sub_category->is_financial }}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Expenses Sub Category')
                                                <button type="button" onclick="loadFormModal('settings_expenses_sub_category_form', {className: 'ExpensesSubCategory', id: {{$expenses_sub_category->id}}}, 'Edit {{$expenses_sub_category->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                                @can('Delete Expenses Sub Category')
                                                    <button type="button" onclick="deleteModelItem('ExpensesSubCategory', {{$expenses_sub_category->id}}, 'expenses_sub_category-tr-{{$expenses_sub_category->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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
@endsection
