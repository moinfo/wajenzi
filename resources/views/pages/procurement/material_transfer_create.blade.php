@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">New Material Transfer
                <div class="float-right">
                    <a href="{{ route('material_transfers') }}" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back</a>
                </div>
            </div>

            <div class="block">
                <div class="block-content">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="get" action="{{ route('material_transfer.create') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-5">
                                <label class="control-label required">Source Project (where materials are now)</label>
                                <select name="from_project_id" class="form-control" onchange="this.form.submit()" required>
                                    <option value="">— Select source site —</option>
                                    @foreach($projects as $p)
                                        <option value="{{ $p->id }}" {{ $fromProjectId == $p->id ? 'selected' : '' }}>{{ $p->project_name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Pick first to load BOQ items with available stock.</small>
                            </div>
                        </div>
                    </form>

                    @if($fromProjectId)
                        <form method="post" action="{{ route('material_transfer.store') }}">
                            @csrf
                            <input type="hidden" name="from_project_id" value="{{ $fromProjectId }}">

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label required">Destination Project</label>
                                        <select name="to_project_id" class="form-control" required>
                                            <option value="">— Select destination —</option>
                                            @foreach($projects as $p)
                                                @if($p->id != $fromProjectId)
                                                    <option value="{{ $p->id }}" {{ old('to_project_id') == $p->id ? 'selected' : '' }}>{{ $p->project_name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label required">Transfer Date</label>
                                        <input type="date" name="transfer_date" class="form-control" value="{{ old('transfer_date', date('Y-m-d')) }}" required>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label">Expected Arrival Date</label>
                                        <input type="date" name="expected_arrival_date" class="form-control" value="{{ old('expected_arrival_date') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label">Linked Material Request <small class="text-muted">(optional)</small></label>
                                        <select name="material_request_id" class="form-control">
                                            <option value="">— None —</option>
                                            @foreach($pendingMaterialRequests as $mr)
                                                <option value="{{ $mr->id }}" {{ old('material_request_id') == $mr->id ? 'selected' : '' }}>
                                                    {{ $mr->request_number }} ({{ $mr->project->project_name ?? '' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Pick the supervisor's request being fulfilled by this transfer.</small>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label">Vehicle / Plate</label>
                                        <input type="text" name="vehicle_info" class="form-control" value="{{ old('vehicle_info') }}" placeholder="e.g. T123 ABC, lorry">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="control-label">Cost Expense Sub-Category</label>
                                        <select name="expenses_sub_category_id" class="form-control">
                                            <option value="">— Select to record costs as expense —</option>
                                            @foreach($expensesSubCategories as $sub)
                                                <option value="{{ $sub->id }}" {{ old('expenses_sub_category_id') == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Required to post loading/offloading/transport as an expense on destination project.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Loading Cost</label>
                                        <input type="number" step="0.01" min="0" name="loading_cost" class="form-control" value="{{ old('loading_cost', 0) }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Offloading Cost</label>
                                        <input type="number" step="0.01" min="0" name="offloading_cost" class="form-control" value="{{ old('offloading_cost', 0) }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Transportation Cost</label>
                                        <input type="number" step="0.01" min="0" name="transportation_cost" class="form-control" value="{{ old('transportation_cost', 0) }}">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="control-label">Notes</label>
                                        <input type="text" name="notes" class="form-control" value="{{ old('notes') }}">
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <h5 class="d-flex align-items-center justify-content-between">
                                Materials to transfer
                                <a href="{{ route('project_stock.index', ['project_id' => $fromProjectId]) }}" target="_blank" class="btn btn-sm btn-alt-secondary">
                                    <i class="fa fa-boxes mr-1"></i> Manage Site Stock
                                </a>
                            </h5>

                            @if($sourceItems->isEmpty() && $sourceStockItems->isEmpty())
                                <div class="alert alert-warning">
                                    No BOQ items with available stock and no free-stock items at this source project.
                                    <a href="{{ route('project_stock.index', ['project_id' => $fromProjectId]) }}">Add free-stock items</a> to enable custom transfers.
                                </div>
                            @else
                                {{-- Pass source stock items to JS --}}
                                <script>
                                    var sourceStockItems = @json($sourceStockItems->map(fn($s) => [
                                        'id'          => $s->id,
                                        'item_code'   => $s->item_code,
                                        'description' => $s->description,
                                        'unit'        => $s->unit,
                                        'available'   => (float)$s->quantity_on_hand,
                                    ]));
                                </script>

                                <div class="table-responsive">
                                    <table class="table table-bordered" id="items-table">
                                        <thead>
                                        <tr>
                                            <th style="width:28%;">Source Item</th>
                                            <th style="width:20%;">Free-Stock Item <small class="text-muted">(if custom)</small></th>
                                            <th>Description</th>
                                            <th style="width:10%;">Qty</th>
                                            <th style="width:8%;">Unit</th>
                                            <th style="width:10%;">Available</th>
                                            <th style="width:50px;"></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr class="item-row">
                                            <td>
                                                <select name="items[0][source_boq_item_id]" class="form-control source-select boq-select">
                                                    <option value="">— Custom / Free-Stock —</option>
                                                    @foreach($sourceItems as $bi)
                                                        <option value="{{ $bi->id }}"
                                                                data-description="{{ $bi->description }}"
                                                                data-unit="{{ $bi->unit }}"
                                                                data-available="{{ max(0, ((float)$bi->quantity_received) - ((float)$bi->quantity_used)) }}">
                                                            {{ $bi->item_code }} — {{ \Illuminate\Support\Str::limit($bi->description, 35) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="stock-cell">
                                                <select name="items[0][source_stock_item_id]" class="form-control stock-select">
                                                    <option value="">— None / New item —</option>
                                                    @foreach($sourceStockItems as $si)
                                                        <option value="{{ $si->id }}"
                                                                data-description="{{ $si->description }}"
                                                                data-unit="{{ $si->unit }}"
                                                                data-available="{{ (float)$si->quantity_on_hand }}">
                                                            {{ $si->item_code }} — {{ \Illuminate\Support\Str::limit($si->description, 30) }}
                                                            ({{ number_format($si->quantity_on_hand, 2) }} {{ $si->unit }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td><input type="text" name="items[0][description]" class="form-control description" required></td>
                                            <td><input type="number" step="0.01" min="0.01" name="items[0][quantity]" class="form-control quantity" required></td>
                                            <td><input type="text" name="items[0][unit]" class="form-control unit" required></td>
                                            <td><span class="available-display text-muted">—</span></td>
                                            <td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="fa fa-times"></i></button></td>
                                        </tr>
                                        </tbody>
                                    </table>
                                    <button type="button" id="add-row" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Add Row</button>
                                </div>

                                <div class="text-right mt-4">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fa fa-paper-plane mr-1"></i> Submit Transfer for Approval
                                    </button>
                                </div>
                            @endif
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js_after')
    <script>
        (function () {
            let rowIndex = 1;

            function updateAvailable(row) {
                const boqSelect   = row.querySelector('.boq-select');
                const stockSelect = row.querySelector('.stock-select');
                const avail       = row.querySelector('.available-display');

                if (boqSelect && boqSelect.value) {
                    // BOQ item selected — hide/disable stock dropdown
                    row.querySelector('.stock-cell').style.opacity = '0.4';
                    stockSelect.value = '';
                    stockSelect.disabled = true;
                    const opt = boqSelect.options[boqSelect.selectedIndex];
                    row.querySelector('.description').value = opt.dataset.description || '';
                    row.querySelector('.unit').value = opt.dataset.unit || '';
                    avail.textContent = parseFloat(opt.dataset.available || 0).toFixed(2);
                    avail.style.color = '';
                } else {
                    // Custom — enable stock dropdown
                    row.querySelector('.stock-cell').style.opacity = '1';
                    stockSelect.disabled = false;

                    if (stockSelect && stockSelect.value) {
                        const opt = stockSelect.options[stockSelect.selectedIndex];
                        row.querySelector('.description').value = opt.dataset.description || '';
                        row.querySelector('.unit').value = opt.dataset.unit || '';
                        const qty = parseFloat(opt.dataset.available || 0);
                        avail.textContent = qty.toFixed(2) + ' (free-stock)';
                        avail.style.color = qty > 0 ? '#28a745' : '#dc3545';
                    } else {
                        avail.textContent = '— new item';
                        avail.style.color = '#6c757d';
                    }
                }
            }

            function bindRow(row) {
                row.querySelector('.boq-select')?.addEventListener('change', () => updateAvailable(row));
                row.querySelector('.stock-select')?.addEventListener('change', () => updateAvailable(row));
                updateAvailable(row);
            }

            document.querySelectorAll('.item-row').forEach(bindRow);

            document.getElementById('add-row')?.addEventListener('click', function () {
                const tbody = document.querySelector('#items-table tbody');
                const first = tbody.querySelector('.item-row');
                const clone = first.cloneNode(true);
                clone.querySelectorAll('input, select').forEach(el => {
                    el.name = el.name.replace(/items\[\d+\]/, 'items[' + rowIndex + ']');
                    if (el.tagName === 'SELECT') { el.selectedIndex = 0; el.disabled = false; }
                    else el.value = '';
                });
                clone.querySelector('.available-display').textContent = '—';
                clone.querySelector('.stock-cell').style.opacity = '1';
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
