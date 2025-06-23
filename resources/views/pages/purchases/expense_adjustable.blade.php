@extends('layouts.backend')


@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Adjustment Expenses
                <div class="float-right">
                    @can('Add Adjustment Expense')
                        <button type="button" onclick="loadFormModal('adjustable_expense_form', {className: 'AdjustmentExpense'}, 'Create New Adjustment Expense', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New AdjustmentExpense</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Adjustment Expenses</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="supplier_receiving_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Start Date</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-01')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon2">End Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">
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
                                    <th>Amount</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php
                                $total_amount = 0;
                                @endphp
                                @foreach($adjustable_expenses as $adjustable_expense)
                                    @php
                                        $total_amount += $adjustable_expense->amount;
                                    @endphp
                                    <tr id="adjustable_expense-tr-{{$adjustable_expense->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td class="font-w600">{{ $adjustable_expense->date }}</td>
                                        <td class="text-right">{{ number_format($adjustable_expense->amount, 2) }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                            @can('Edit Adjustment Expense')
                                                    <button type="button"
                                                            onclick="loadFormModal('adjustable_expense_form', {className: 'AdjustmentExpense', id: {{$adjustable_expense->id}}}, 'Edit Adjustable Expenses', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan

                                                    @can('Delete Adjustment Expense')
                                                        <button type="button"
                                                                onclick="deleteModelItem('AdjustmentExpense', {{$adjustable_expense->id}}, 'adjustable_expense-tr-{{$adjustable_expense->id}}');"
                                                                class="btn btn-sm btn-danger js-tooltip-enabled"
                                                                data-toggle="tooltip" title="Delete"
                                                                data-original-title="Delete">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    @endcan

                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2">Total</td>
                                        <td class="text-right">{{number_format($total_amount)}}</td>
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

