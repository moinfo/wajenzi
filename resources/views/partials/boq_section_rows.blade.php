{{-- Recursive partial: renders a section header, its items, subtotal, then recurses into children --}}
@php $depth = $depth ?? 0; @endphp

{{-- Section header row --}}
<tr style="background-color: rgba(52, 144, 220, {{ 0.10 + ($depth * 0.05) }});">
    <td colspan="5" style="padding: 6px 8px 6px {{ 15 + ($depth * 20) }}px; font-weight: bold; font-size: 13px;">
        {{ $section->name }}
        @if($section->description)
            <small class="text-muted"> — {{ $section->description }}</small>
        @endif
    </td>
    <td class="text-right" style="padding: 6px 8px; font-weight: bold; font-size: 12px; color: #555;">
        {{ number_format($section->subtotal, 2) }}
    </td>
    <td class="text-center" style="padding: 4px;">
        <div class="btn-group btn-group-xs">
            @can('Add BOQ Item')
                <button type="button"
                    onclick="loadFormModal('project_boq_item_form', {className: 'ProjectBoqItem', boq_id: {{ $section->boq_id }}, section_id: {{ $section->id }}}, 'Add Item to {{ $section->name }}', 'modal-md');"
                    class="btn btn-xs btn-success" title="Add Item">
                    <i class="fa fa-plus"></i>
                </button>
                <button type="button"
                    onclick="loadFormModal('boq_section_form', {className: 'ProjectBoqSection', boq_id: {{ $section->boq_id }}, parent_id: {{ $section->id }}}, 'Add Sub-section', 'modal-md');"
                    class="btn btn-xs btn-info" title="Add Sub-section">
                    <i class="fa fa-indent"></i>
                </button>
            @endcan
            @can('Edit BOQ Item')
                <button type="button"
                    onclick="loadFormModal('boq_section_form', {className: 'ProjectBoqSection', id: {{ $section->id }}}, 'Edit Section', 'modal-md');"
                    class="btn btn-xs btn-primary" title="Edit">
                    <i class="fa fa-pencil"></i>
                </button>
            @endcan
            @can('Delete BOQ Item')
                <button type="button"
                    onclick="deleteModelItem('ProjectBoqSection', {{ $section->id }}, 'section-tr-{{ $section->id }}');"
                    class="btn btn-xs btn-danger" title="Delete">
                    <i class="fa fa-times"></i>
                </button>
            @endcan
        </div>
    </td>
</tr>

{{-- Items in this section --}}
@foreach($section->items as $item)
    <tr id="boq-item-tr-{{ $item->id }}">
        <td class="text-center" style="padding: 4px 6px; font-size: 11px; color: #888;">{{ $item->item_code }}</td>
        <td style="padding: 4px 8px 4px {{ 15 + ($depth * 20) }}px;">
            {{ $item->description }}
            @if($item->specification) <small class="text-muted">({{ $item->specification }})</small> @endif
            @if($item->item_type == 'labour')
                <span class="badge badge-warning" style="font-size: 9px; padding: 2px 5px; vertical-align: middle;">LABOUR</span>
            @endif
        </td>
        <td class="text-right" style="padding: 4px 6px;">{{ number_format($item->quantity, 2) }}</td>
        <td style="padding: 4px 6px;">{{ $item->unit }}</td>
        <td class="text-right" style="padding: 4px 6px;">{{ number_format($item->unit_price, 2) }}</td>
        <td class="text-right" style="padding: 4px 6px; font-weight: 500;">{{ number_format($item->total_price, 2) }}</td>
        <td class="text-center" style="padding: 3px;">
            <div class="btn-group btn-group-xs">
                @can('Edit BOQ Item')
                    <button type="button"
                        onclick="loadFormModal('project_boq_item_form', {className: 'ProjectBoqItem', id: {{ $item->id }}}, 'Edit Item', 'modal-md');"
                        class="btn btn-xs btn-primary" title="Edit">
                        <i class="fa fa-pencil"></i>
                    </button>
                @endcan
                @can('Delete BOQ Item')
                    <button type="button"
                        onclick="deleteModelItem('ProjectBoqItem', {{ $item->id }}, 'boq-item-tr-{{ $item->id }}');"
                        class="btn btn-xs btn-danger" title="Delete">
                        <i class="fa fa-times"></i>
                    </button>
                @endcan
            </div>
        </td>
    </tr>
@endforeach

{{-- Recurse into children --}}
@foreach($section->childrenRecursive as $child)
    @include('partials.boq_section_rows', ['section' => $child, 'depth' => $depth + 1])
@endforeach

{{-- Section subtotal row --}}
@if($section->items->count() > 0 || $section->childrenRecursive->count() > 0)
<tr style="background-color: rgba(52, 144, 220, 0.05); border-top: 1px solid #ccc;">
    <td colspan="5" class="text-right" style="padding: 4px 8px; font-weight: bold; font-size: 11px; color: #666;">
        Subtotal — {{ $section->name }}:
    </td>
    <td class="text-right" style="padding: 4px 6px; font-weight: bold; font-size: 12px;">{{ number_format($section->subtotal, 2) }}</td>
    <td></td>
</tr>
@endif