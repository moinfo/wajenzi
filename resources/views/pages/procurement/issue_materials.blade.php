@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">Issue Materials — {{ $project->name }}
            <div class="float-right">
                <a href="{{ route('stock_register', $project->id) }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Stock Register
                </a>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Issue Materials to Site</h3>
            </div>
            <div class="block-content">
                <form method="post" action="{{ route('stock_register.issue.store', $project->id) }}">
                    @csrf

                    <div class="table-responsive">
                        <table class="table table-bordered" id="issue-table">
                            <thead>
                                <tr>
                                    <th style="width: 35%;">Material</th>
                                    <th style="width: 10%;" class="text-center">Available</th>
                                    <th style="width: 15%;">Qty to Issue</th>
                                    <th style="width: 15%;">Location</th>
                                    <th style="width: 20%;">Notes</th>
                                    <th style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody id="issue-rows">
                                <tr class="issue-row" data-index="0">
                                    <td>
                                        <select name="items[0][inventory_id]" class="form-control inventory-select" required>
                                            <option value="">-- Select Material --</option>
                                            @foreach($inventories as $inv)
                                                <option value="{{ $inv->id }}"
                                                    data-available="{{ $inv->quantity_available }}"
                                                    data-unit="{{ $inv->boqItem?->unit }}">
                                                    {{ $inv->boqItem?->item_code }} — {{ Str::limit($inv->boqItem?->description ?? $inv->material?->name, 40) }}
                                                    ({{ number_format($inv->quantity_available, 2) }} {{ $inv->boqItem?->unit }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-center available-display">-</td>
                                    <td>
                                        <input type="number" name="items[0][quantity]" class="form-control qty-input"
                                            step="0.01" min="0.01" required>
                                    </td>
                                    <td>
                                        <input type="text" name="items[0][location]" class="form-control"
                                            placeholder="e.g. Block A, Floor 2">
                                    </td>
                                    <td>
                                        <input type="text" name="items[0][notes]" class="form-control"
                                            placeholder="Optional notes">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger remove-row" title="Remove">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-success" id="add-row">
                            <i class="fa fa-plus"></i> Add Row
                        </button>
                    </div>

                    <div class="mb-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-share"></i> Issue Materials
                        </button>
                        <a href="{{ route('stock_register', $project->id) }}" class="btn btn-secondary ml-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    let rowIndex = 0;

    // Update available display when select changes
    $(document).on('change', '.inventory-select', function() {
        var row = $(this).closest('tr');
        var selected = $(this).find(':selected');
        var available = selected.data('available') || '-';
        var unit = selected.data('unit') || '';
        row.find('.available-display').text(available !== '-' ? parseFloat(available).toFixed(2) + ' ' + unit : '-');
        row.find('.qty-input').attr('max', available !== '-' ? available : '');
    });

    // Add row
    $('#add-row').click(function() {
        rowIndex++;
        var firstRow = $('#issue-rows tr:first');
        var newRow = firstRow.clone();
        newRow.attr('data-index', rowIndex);
        newRow.find('select, input').each(function() {
            var name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace(/items\[\d+\]/, 'items[' + rowIndex + ']'));
            }
            $(this).val('');
        });
        newRow.find('.available-display').text('-');
        $('#issue-rows').append(newRow);
    });

    // Remove row (keep at least one)
    $(document).on('click', '.remove-row', function() {
        if ($('#issue-rows tr').length > 1) {
            $(this).closest('tr').remove();
        }
    });
});
</script>
@endsection
