{{-- project_expenses.blade.php (Project Costs) --}}
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Project Costs
                <div class="float-right">
                    @can('Add Project Cost')
                        <button type="button" onclick="loadFormModal('project_expense_form', {className: 'ProjectExpense'}, 'Create New Cost', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Cost</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Project Costs</h3>
                    </div>
                    <div class="block-content">
                        <!-- Filter Section -->
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form name="expense_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-2">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Start Date</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker" value="{{ request('start_date', date('Y-m-01')) }}">
                                                </div>
                                            </div>
                                            <div class="class col-md-2">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">End Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker" value="{{ request('end_date', date('Y-m-d')) }}">
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Project</span>
                                                    </div>
                                                    <select name="project_id" id="input-project" class="form-control">
                                                        <option value="">All Projects</option>
                                                        @foreach ($projects as $project)
                                                            <option value="{{ $project->id }}" {{ request('project_id') == $project->id ? 'selected' : '' }}>{{ $project->project_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Category</span>
                                                    </div>
                                                    <select name="cost_category_id" id="input-cost-category" class="form-control">
                                                        <option value="">All Categories</option>
                                                        @foreach ($costCategories as $category)
                                                            <option value="{{ $category->id }}" {{ request('cost_category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                                        @endforeach
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

                        <!-- Summary Stats -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="block block-rounded bg-primary text-white text-center p-3">
                                    <div class="font-size-h4 font-w600">{{ $expenses->count() }}</div>
                                    <div class="font-size-sm">Total Records</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="block block-rounded bg-success text-white text-center p-3">
                                    <div class="font-size-h4 font-w600">TZS {{ number_format($total_amount, 2) }}</div>
                                    <div class="font-size-sm">Total Cost Amount</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="block block-rounded bg-info text-white text-center p-3">
                                    <div class="font-size-h4 font-w600">{{ $expenses->unique('project_id')->count() }}</div>
                                    <div class="font-size-sm">Projects with Costs</div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 60px;">Cost ID</th>
                                    <th>Project ID</th>
                                    <th>Project Name</th>
                                    <th>Cost Category</th>
                                    <th>Cost Description</th>
                                    <th>Cost Date</th>
                                    <th class="text-right">Cost Amount (TZS)</th>
                                    <th>Remarks</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($expenses as $expense)
                                    <tr id="expense-tr-{{ $expense->id }}">
                                        <td class="text-center">{{ $expense->id }}</td>
                                        <td>{{ $expense->project->document_number ?? '-' }}</td>
                                        <td>{{ $expense->project->project_name ?? '-' }}</td>
                                        <td>
                                            @if($expense->costCategory)
                                                <span class="badge badge-info">{{ $expense->costCategory->name }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ Str::limit($expense->description, 50) }}</td>
                                        <td>{{ $expense->expense_date->format('d/m/Y') }}</td>
                                        <td class="text-right font-w600">{{ number_format($expense->amount, 2) }}</td>
                                        <td>{{ Str::limit($expense->remarks, 30) ?? '-' }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                @can('Edit Project Cost')
                                                    <button type="button"
                                                            onclick="loadFormModal('project_expense_form', {className: 'ProjectExpense', id: {{ $expense->id }}}, 'Edit Cost', 'modal-md');"
                                                            class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete Project Cost')
                                                    <button type="button"
                                                            onclick="deleteModelItem('ProjectExpense', {{ $expense->id }}, 'expense-tr-{{ $expense->id }}');"
                                                            class="btn btn-sm btn-danger" title="Delete">
                                                        <i class="fa fa-times"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">No project costs found</td>
                                    </tr>
                                @endforelse
                                </tbody>
                                <tfoot>
                                <tr class="table-active">
                                    <td colspan="6" class="text-right"><strong>Total:</strong></td>
                                    <td class="text-right"><strong>TZS {{ number_format($expenses->sum('amount'), 2) }}</strong></td>
                                    <td colspan="2"></td>
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
