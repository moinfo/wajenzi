@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading d-flex align-items-center justify-content-between">
            <span><i class="fa fa-boxes mr-2"></i>Site Free-Stock Inventory</span>
            <div>
                <a href="{{ route('project_stock_receipts.index', $projectId ? ['project_id' => $projectId] : []) }}"
                   class="btn btn-sm btn-alt-secondary mr-2">
                    <i class="fa fa-inbox mr-1"></i> Stock Receipts
                </a>
                <a href="{{ route('project_stock_receipts.create', $projectId ? ['project_id' => $projectId] : []) }}"
                   class="btn btn-sm btn-alt-success mr-2">
                    <i class="fa fa-download mr-1"></i> Receive Goods
                </a>
                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addStockModal">
                    <i class="fa fa-plus mr-1"></i> Add Item
                </button>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="block">
            <div class="block-content">
                <!-- Project filter -->
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
                                <th>Code</th>
                                <th>Project</th>
                                <th>Description</th>
                                <th>Unit</th>
                                <th class="text-right">Qty on Hand</th>
                                <th>Notes</th>
                                <th style="width:120px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                <tr>
                                    <td><code>{{ $item->item_code }}</code></td>
                                    <td>{{ $item->project->project_name ?? '—' }}</td>
                                    <td>{{ $item->description }}</td>
                                    <td>{{ $item->unit }}</td>
                                    <td class="text-right {{ $item->quantity_on_hand <= 0 ? 'text-danger' : '' }}">
                                        {{ number_format($item->quantity_on_hand, 2) }}
                                    </td>
                                    <td>{{ $item->notes ?? '—' }}</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-alt-secondary edit-btn"
                                            data-id="{{ $item->id }}"
                                            data-description="{{ $item->description }}"
                                            data-unit="{{ $item->unit }}"
                                            data-qty="{{ $item->quantity_on_hand }}"
                                            data-notes="{{ $item->notes }}">
                                            <i class="fa fa-pencil"></i>
                                        </button>
                                        <form method="post" action="{{ route('project_stock.delete', $item->id) }}" class="d-inline"
                                              onsubmit="return confirm('Delete this stock item?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-alt-danger"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">No stock items found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="{{ route('project_stock.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Free-Stock Item</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="control-label required">Project / Site</label>
                        <select name="project_id" class="form-control" required>
                            <option value="">— Select project —</option>
                            @foreach($projects as $p)
                                <option value="{{ $p->id }}" {{ $projectId == $p->id ? 'selected' : '' }}>
                                    {{ $p->project_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="control-label required">Description</label>
                        <input type="text" name="description" class="form-control" required placeholder="e.g. Steel Rebar 16mm">
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="control-label required">Unit</label>
                                <input type="text" name="unit" class="form-control" required placeholder="kg, m³, pcs…">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="control-label required">Opening Qty on Hand</label>
                                <input type="number" step="0.01" min="0" name="quantity_on_hand" class="form-control" value="0" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional note">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editStockModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" id="editStockForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Stock Item</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="control-label required">Description</label>
                        <input type="text" name="description" id="edit-description" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="control-label required">Unit</label>
                                <input type="text" name="unit" id="edit-unit" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="control-label required">Qty on Hand</label>
                                <input type="number" step="0.01" min="0" name="quantity_on_hand" id="edit-qty" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Notes</label>
                        <input type="text" name="notes" id="edit-notes" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js_after')
<script>
    document.querySelectorAll('.edit-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.dataset.id;
            document.getElementById('edit-description').value = this.dataset.description;
            document.getElementById('edit-unit').value = this.dataset.unit;
            document.getElementById('edit-qty').value = this.dataset.qty;
            document.getElementById('edit-notes').value = this.dataset.notes || '';
            document.getElementById('editStockForm').action = '/project-stock/' + id + '/update';
            $('#editStockModal').modal('show');
        });
    });
</script>
@endsection
