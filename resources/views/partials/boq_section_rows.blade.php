{{-- Recursive partial: renders a section header, its items, subtotal, then recurses into children --}}
@php $depth = $depth ?? 0; @endphp

{{-- Section header row --}}
<tr style="background-color: rgba(52, 144, 220, {{ 0.08 + ($depth * 0.04) }});">
    <td colspan="2" style="padding-left: {{ 20 + ($depth * 25) }}px;">
        <strong>{{ $section->name }}</strong>
        @if($section->description)
            <small class="text-muted d-block">{{ $section->description }}</small>
        @endif
    </td>
    <td colspan="4"></td>
    <td class="text-center">
        <div class="btn-group btn-group-sm">
            @can('Add BOQ Item')
                <button type="button"
                    onclick="loadFormModal('project_boq_item_form', {className: 'ProjectBoqItem', boq_id: {{ $section->boq_id }}, section_id: {{ $section->id }}}, 'Add Item to {{ $section->name }}', 'modal-md');"
                    class="btn btn-sm btn-success" title="Add Item">
                    <i class="fa fa-plus"></i>
                </button>
            @endcan
            @can('Add BOQ Item')
                <button type="button"
                    onclick="loadFormModal('boq_section_form', {className: 'ProjectBoqSection', boq_id: {{ $section->boq_id }}, parent_id: {{ $section->id }}}, 'Add Sub-section', 'modal-md');"
                    class="btn btn-sm btn-info" title="Add Sub-section">
                    <i class="fa fa-indent"></i>
                </button>
            @endcan
            @can('Edit BOQ Item')
                <button type="button"
                    onclick="loadFormModal('boq_section_form', {className: 'ProjectBoqSection', id: {{ $section->id }}}, 'Edit Section', 'modal-md');"
                    class="btn btn-sm btn-primary" title="Edit Section">
                    <i class="fa fa-pencil"></i>
                </button>
            @endcan
            @can('Delete BOQ Item')
                <button type="button"
                    onclick="deleteModelItem('ProjectBoqSection', {{ $section->id }}, 'section-tr-{{ $section->id }}');"
                    class="btn btn-sm btn-danger" title="Delete Section">
                    <i class="fa fa-times"></i>
                </button>
            @endcan
        </div>
    </td>
</tr>

{{-- Items in this section --}}
@foreach($section->items as $item)
    <tr id="boq-item-tr-{{ $item->id }}">
        <td class="text-center" style="padding-left: {{ 30 + ($depth * 25) }}px;">{{ $item->item_code }}</td>
        <td style="padding-left: {{ 30 + ($depth * 25) }}px;">
            {{ $item->description }}
            @if($item->specification)
                <small class="text-muted d-block">{{ $item->specification }}</small>
            @endif
            @if($item->item_type == 'labour')
                <span class="badge badge-warning">Labour</span>
            @else
                <span class="badge badge-info">Material</span>
            @endif
        </td>
        <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
        <td>{{ $item->unit }}</td>
        <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
        <td class="text-right">{{ number_format($item->total_price, 2) }}</td>
        <td class="text-center">
            <div class="btn-group btn-group-sm">
                @can('Edit BOQ Item')
                    <button type="button"
                        onclick="loadFormModal('project_boq_item_form', {className: 'ProjectBoqItem', id: {{ $item->id }}}, 'Edit Item', 'modal-md');"
                        class="btn btn-sm btn-primary">
                        <i class="fa fa-pencil"></i>
                    </button>
                @endcan
                @can('Delete BOQ Item')
                    <button type="button"
                        onclick="deleteModelItem('ProjectBoqItem', {{ $item->id }}, 'boq-item-tr-{{ $item->id }}');"
                        class="btn btn-sm btn-danger">
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
<tr style="background-color: rgba(52, 144, 220, {{ 0.04 + ($depth * 0.02) }}); font-weight: bold;">
    <td colspan="5" class="text-right" style="padding-left: {{ 20 + ($depth * 25) }}px;">
        Subtotal — {{ $section->name }}:
    </td>
    <td class="text-right">{{ number_format($section->subtotal, 2) }}</td>
    <td></td>
</tr>
