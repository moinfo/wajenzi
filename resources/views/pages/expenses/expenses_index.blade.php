@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Expenses
                <div class="float-right">
                    @can('Add Expense')
                        <button type="button" onclick="loadFormModal('expense_form', {className: 'Expense'}, 'Create New Expenses', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Expenses</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Expenses</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="expenses_search" action="{{route('expenses')}}" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Start</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon2">End</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon4">Category</span>
                                                    </div>
                                                    <select name="expenses_category_id" id="input-expenses-category-id" class="form-control" aria-describedby="basic-addon4">
                                                        <option >All</option>
                                                        @foreach ($expense_categories as $expense_category)
                                                            <option value="{{ $expense_category->id }}"> {{ $expense_category->name }} </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon4">Sub Category</span>
                                                    </div>
                                                    <select name="expenses_sub_category_id" id="input-expenses-sub-category-id" class="form-control" aria-describedby="basic-addon4">
                                                        <option>All</option>
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
                        <br/>
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
{{--                                    <th>Payment Type</th>--}}
                                    <th>Attachment</th>
                                    <th scope="col">Approvals</th>
                                    <th scope="col">Status</th>
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
//                                    $payment_type = $expense->payment_type_id == '1' ? 'System' : 'Office';
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
{{--                                        <td>{{$payment_type}}</td>--}}
                                        <td class="text-center">
                                            @if($expense->file != null)
                                                <a href="{{ url("$expense->file") }}">Attachment</a>
                                            @else
                                                No File
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <!-- Approval status summary component -->
                                            <x-ringlesoft-approval-status-summary :model="$expense" />
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $approvalStatus = $expense->approvalStatus?->status ?? 'PENDING';
                                                $statusClass = [
                                                    'Pending' => 'warning',
                                                    'Submitted' => 'info',
                                                    'Approved' => 'success',
                                                    'Rejected' => 'danger',
                                                    'Paid' => 'primary',
                                                    'Completed' => 'success',
                                                    'Discarded' => 'danger',
                                                ][$approvalStatus] ?? 'secondary';

                                                $statusIcon = [
                                                    'Pending' => '<i class="fas fa-clock"></i>',
                                                    'Submitted' => '<i class="fas fa-paper-plane"></i>',
                                                    'Approved' => '<i class="fas fa-check"></i>',
                                                    'Rejected' => '<i class="fas fa-times"></i>',
                                                    'Paid' => '<i class="fas fa-money-bill"></i>',
                                                    'Completed' => '<i class="fas fa-check-circle"></i>',
                                                    'Discarded' => '<i class="fas fa-trash"></i>',
                                                ][$approvalStatus] ?? '<i class="fas fa-question-circle"></i>';
                                            @endphp
                                            <span class="badge badge-{{ $statusClass }} badge-pill" style="font-size: 0.9em; padding: 6px 10px;">
                                                {!! $statusIcon !!} {{ $approvalStatus }}
                                            </span>

                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{route('expense',['id' => $expense->id,'document_type_id'=>5])}}"><i class="fa fa-eye"></i></a>
                                            @can('Edit Expense')
                                                    <button type="button"
                                                            onclick="loadFormModal('expense_form', {className: 'Expense', id: {{$expense->id}}}, 'Edit Expenses', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan

                                                    @can('Delete Expense')
                                                        <button type="button"
                                                                onclick="deleteModelItem('Expense', {{$expense->id}}, 'expense-tr-{{$expense->id}}');"
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
                                    <td class="text-right text-dark" colspan="6"><b>{{number_format($sum,2)}}</b></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
{{--                                    <td></td>--}}
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


