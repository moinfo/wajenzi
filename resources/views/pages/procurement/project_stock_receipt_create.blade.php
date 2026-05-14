@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading d-flex align-items-center justify-content-between">
            <span><i class="fa fa-inbox mr-2"></i>New Stock Receipt</span>
            <a href="{{ route('project_stock_receipts.index') }}" class="btn btn-sm btn-secondary">
                <i class="fa fa-arrow-left mr-1"></i> Back
            </a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- Step 1: pick project to load its stock items --}}
        <form method="get" action="{{ route('project_stock_receipts.create') }}" class="mb-0">
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Step 1 — Select Receiving Site</h3>
                </div>
                <div class="block-content pb-3">
                    <div class="row">
                        <div class="col-md-5">
                            <label class="control-label required">Project / Site</label>
                            <select name="project_id" class="form-control" onchange="this.form.submit()" required>
                                <option value="">— Select project —</option>
                                @foreach($projects as $p)
                                    <option value="{{ $p->id }}" {{ $projectId == $p->id ? 'selected' : '' }}>
                                        {{ $p->project_name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select first to load existing stock items for this site.</small>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @if($projectId)
        <form method="post" action="{{ route('project_stock_receipts.store') }}">
            @csrf
            <input type="hidden" name="project_id" value="{{ $projectId }}">

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Step 2 — Receipt Details</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="control-label required">Receipt Date</label>
                                <input type="date" name="receipt_date" class="form-control"
                                       value="{{ old('receipt_date', date('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label">Supplier / Source</label>
                                <input type="text" name="supplier" class="form-control"
                                       value="{{ old('supplier') }}"
                                       placeholder="e.g. ABC Hardware, Head Office, Project X">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label class="control-label">Notes</label>
                                <input type="text" name="notes" class="form-control"
                                       value="{{ old('notes') }}"
                                       placeholder="Optional notes (delivery note #, LPO reference…)">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Step 3 — Items Received</h3>
                </div>
                <div class="block-content">
                    <p class="text-muted mb-3">
                        For each item, either pick an <strong>existing stock item</strong> at this site (qty will be added to it),
                        or leave blank to <strong>create a new stock item</strong>.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="items-table">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width:30%;">Existing Stock Item <small class="text-muted">(optional)</small></th>
                                    <th>Description</th>
                                    <th style="width:10%;">Unit</th>
                                    <th style="width:12%;">Qty Received</th>
                                    <th style="width:12%;">Current On-Hand</th>
                                    <th style="width:50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="item-row">
                                    <td>
                                        <select name="items[0][stock_item_id]" class="form-control stock-select">
                                            <option value="">— New item —</option>
                                            @foreach($stockItems as $si)
                                                <option value="{{ $si->id }}"
                                                        data-description="{{ $si->description }}"
                                                        data-unit="{{ $si->unit }}"
                                                        data-onhand="{{ $si->quantity_on_hand }}">
                                                    {{ $si->item_code }} — {{ $si->description }}
                                                    ({{ number_format($si->quantity_on_hand, 2) }} {{ $si->unit }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="items[0][description]" class="form-control item-description" required placeholder="e.g. Steel Rebar 16mm"></td>
                                    <td><input type="text" name="items[0][unit]" class="form-control item-unit" required placeholder="kg"></td>
                                    <td><input type="number" step="0.01" min="0.01" name="items[0][quantity]" class="form-control item-qty" required></td>
                                    <td><span class="onhand-display text-muted">—</span></td>
                                    <td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="fa fa-times"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <button type="button" id="add-row" class="btn btn-sm btn-alt-primary">
                        <i class="fa fa-plus mr-1"></i> Add Row
                    </button>

                    <div class="text-right mt-4">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fa fa-check mr-1"></i> Save Receipt &amp; Update Stock
                        </button>
                    </div>
                </div>
            </div>
        </form>
        @endif
    </div>
</div>
@endsection

@section('js_after')
<script>
(function () {
    let rowIndex = 1;

    function bindRow(row) {
        const select = row.querySelector('.stock-select');
        if (!select) return;

        select.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt.value) {
                row.querySelector('.item-description').value = opt.dataset.description || '';
                row.querySelector('.item-unit').value        = opt.dataset.unit        || '';
                const oh = parseFloat(opt.dataset.onhand || 0);
                row.querySelector('.onhand-display').textContent = oh.toFixed(2) + ' ' + (opt.dataset.unit || '');
                row.querySelector('.onhand-display').style.color = '#28a745';
            } else {
                row.querySelector('.item-description').value = '';
                row.querySelector('.item-unit').value        = '';
                row.querySelector('.onhand-display').textContent = '— new item';
                row.querySelector('.onhand-display').style.color = '#6c757d';
            }
        });
    }

    document.querySelectorAll('.item-row').forEach(bindRow);

    document.getElementById('add-row')?.addEventListener('click', function () {
        const tbody = document.querySelector('#items-table tbody');
        const first = tbody.querySelector('.item-row');
        const clone = first.cloneNode(true);
        clone.querySelectorAll('input').forEach(el => {
            el.name  = el.name.replace(/items\[\d+\]/, 'items[' + rowIndex + ']');
            el.value = '';
        });
        clone.querySelectorAll('select').forEach(el => {
            el.name = el.name.replace(/items\[\d+\]/, 'items[' + rowIndex + ']');
            el.selectedIndex = 0;
        });
        clone.querySelector('.onhand-display').textContent = '—';
        clone.querySelector('.onhand-display').style.color = '';
        tbody.appendChild(clone);
        bindRow(clone);
        rowIndex++;
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('.remove-row')) {
            const rows = document.querySelectorAll('#items-table .item-row');
            if (rows.length > 1) e.target.closest('.item-row').remove();
        }
    });
})();
</script>
@endsection
