{{-- Recursive partial: renders a template section header, its items, subtotal, then recurses into children --}}
@php $depth = $depth ?? 0; @endphp

{{-- Section header row --}}
<tr style="background-color: rgba(52, 144, 220, {{ 0.10 + ($depth * 0.05) }});">
    <td colspan="4" style="padding: 6px 8px 6px {{ 15 + ($depth * 20) }}px; font-weight: bold; font-size: 13px;">
        {{ $section->name }}
        @if($section->description)
            <small class="text-muted"> — {{ $section->description }}</small>
        @endif
    </td>
    <td></td>
    <td class="text-right" style="padding: 6px 8px; font-weight: bold; font-size: 12px; color: #555;">
        {{ number_format($section->subtotal, 2) }}
    </td>
</tr>

{{-- Items in this section --}}
@foreach($section->items as $item)
    @php $counter++; @endphp
    <tr @if($item->item_type == 'labour') style="background-color: #fefce8;" @endif>
        <td class="text-center" style="padding: 4px 6px; font-size: 11px; color: #888;">{{ $counter }}</td>
        <td style="padding: 4px 8px 4px {{ 15 + ($depth * 20) }}px;">
            {{ $item->description }}
            @if($item->specification) <small class="text-muted">({{ $item->specification }})</small> @endif
            @if($item->item_type == 'labour')
                <span class="badge badge-warning" style="font-size: 9px; padding: 2px 5px; vertical-align: middle;">LABOUR</span>
            @endif
        </td>
        <td style="padding: 4px 6px;">{{ $item->unit }}</td>
        <td class="text-right" style="padding: 4px 6px;">{{ number_format($item->quantity, 2) }}</td>
        <td class="text-right" style="padding: 4px 6px;">{{ number_format($item->unit_price, 2) }}</td>
        <td class="text-right" style="padding: 4px 6px; font-weight: 500;">{{ number_format($item->total_price, 2) }}</td>
    </tr>
@endforeach

{{-- Recurse into children --}}
@foreach($section->childrenRecursive as $child)
    @include('partials.boq_template_section_rows', ['section' => $child, 'depth' => $depth + 1])
@endforeach

{{-- Section subtotal row --}}
@if($section->items->count() > 0 || $section->childrenRecursive->count() > 0)
<tr style="background-color: rgba(52, 144, 220, 0.05); border-top: 1px solid #ccc;">
    <td colspan="5" class="text-right" style="padding: 4px 8px; font-weight: bold; font-size: 11px; color: #666;">
        Subtotal — {{ $section->name }}:
    </td>
    <td class="text-right" style="padding: 4px 6px; font-weight: bold; font-size: 12px;">
        {{ number_format($section->subtotal, 2) }}
    </td>
</tr>
@endif
