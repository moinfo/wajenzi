@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading d-flex align-items-center justify-content-between">
            <span><i class="fa fa-inbox mr-2"></i>Site Stock Receipts</span>
            <div>
                <a href="{{ route('project_stock.index') }}" class="btn btn-sm btn-alt-secondary mr-2">
                    <i class="fa fa-boxes mr-1"></i> View Stock Items
                </a>
                <a href="{{ route('project_stock_receipts.create', $projectId ? ['project_id' => $projectId] : []) }}"
                   class="btn btn-sm btn-primary">
                    <i class="fa fa-plus mr-1"></i> New Receipt
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="block">
            <div class="block-content">
                <form method="get" class="mb-4">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label class="control-label">Filter by Project</label>
                            <select name="project_id" class="form-control" onchange="this.form.submit()">
                                <option value="">— All Projects —</option>
                                @foreach($projects as $p)
                                    <option value="{{ $p->id }}" {{ $projectId == $p->id ? 'selected' : '' }}>
                                        {{ $p->project_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>Receipt #</th>
                                <th>Project</th>
                                <th>Date</th>
                                <th>Supplier / Source</th>
                                <th class="text-center">Items</th>
                                <th>Received By</th>
                                <th>Notes</th>
                                <th style="width:100px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($receipts as $receipt)
                                <tr>
                                    <td>
                                        <a href="{{ route('project_stock_receipts.show', $receipt->id) }}">
                                            <code>{{ $receipt->receipt_number }}</code>
                                        </a>
                                    </td>
                                    <td>{{ $receipt->project->project_name ?? '—' }}</td>
                                    <td>{{ $receipt->receipt_date?->format('d M Y') }}</td>
                                    <td>{{ $receipt->supplier ?: '—' }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-secondary">{{ $receipt->items->count() }}</span>
                                    </td>
                                    <td>{{ $receipt->createdBy->name ?? '—' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($receipt->notes, 40) ?: '—' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('project_stock_receipts.show', $receipt->id) }}"
                                           class="btn btn-sm btn-alt-secondary">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <form method="post"
                                              action="{{ route('project_stock_receipts.delete', $receipt->id) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('Delete this receipt? Quantities will be reversed.')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-alt-danger">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        No stock receipts yet.
                                        <a href="{{ route('project_stock_receipts.create') }}">Create the first one.</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
