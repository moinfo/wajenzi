@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Expenses
                <div class="float-right">
                    <button type="button" onclick="loadFormModal('expense_form', {className: 'Expense'}, 'Create New Expenses', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Expenses</button>
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Expenses</h3>
                    </div>
                    <div class="block-content">
                        <p>This is a list containing all expenses</p>
                        <div class="table-responsive">
                            <table class="table table-striped table-vcenter">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Date</th>
                                    <th>Supervisor Name</th>
                                    <th>Expense Category Name</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($expenses as $expense)
                                    <tr id="expense-tr-{{$expense->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td class="font-w600">{{ $expense->date }}</td>
                                        <td class="font-w600">{{ $expense->supervisor->name }}</td>
                                        <td class="font-w600">{{ $expense->expensesCategory->name }}</td>
                                        <td class="d-none d-sm-table-cell">{{ $expense->description }}
                                        <td class="font-w600">{{ number_format($expense->amount, 2) }}</td>

                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button"
                                                        onclick="loadFormModal('expense_form', {className: 'Expense', id: {{$expense->id}}}, 'Edit {{$expense->name}}', 'modal-md');"
                                                        class="btn btn-sm btn-primary js-tooltip-enabled"
                                                        data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <button type="button"
                                                        onclick="deleteModelItem('Expense', {{$expense->id}}, 'expense-tr-{{$expense->id}}');"
                                                        class="btn btn-sm btn-danger js-tooltip-enabled"
                                                        data-toggle="tooltip" title="Delete"
                                                        data-original-title="Delete">
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


