{{-- Section header row --}}
<tr style="background: {{ $depth === 0 ? 'var(--m-blue-0)' : 'var(--m-gray-0)' }};">
    <td colspan="7" class="m-fw-600" style="padding-left: {{ 1 + ($depth * 1.5) }}rem;">
        <i class="fas fa-folder{{ $depth === 0 ? '' : '-open' }} me-1" style="color: var(--m-blue-6);"></i>
        {{ $section->name }}
        @if($section->description)
            <span class="m-dimmed" style="font-weight: 400; font-size: 0.8125rem;"> - {{ $section->description }}</span>
        @endif
    </td>
</tr>

{{-- Items in this section --}}
@foreach($section->items->sortBy('sort_order') as $item)
    @php $counter++; @endphp
    <tr>
        <td style="padding-left: {{ 1 + (($depth + 1) * 1.5) }}rem;">{{ $counter }}</td>
        <td>{{ $item->description }}</td>
        <td><span class="m-badge m-badge-{{ $item->item_type === 'material' ? 'blue' : 'gray' }}">{{ ucfirst($item->item_type ?? '-') }}</span></td>
        <td>{{ $item->unit }}</td>
        <td class="text-end">{{ number_format($item->quantity ?? 0, 2) }}</td>
        <td class="text-end">{{ number_format($item->unit_price ?? 0, 2) }}</td>
        <td class="text-end m-fw-600">{{ number_format($item->total_price ?? 0, 2) }}</td>
    </tr>
@endforeach

{{-- Child sections (recursive) --}}
@foreach($section->children->sortBy('sort_order') as $childSection)
    @include('client.partials.boq_section', ['section' => $childSection, 'depth' => $depth + 1, 'counter' => $counter])
@endforeach
