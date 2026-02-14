@extends('layouts.client')

@section('title', 'BOQ - ' . $project->project_name)

@section('content')
    <div style="margin-bottom: var(--m-md);">
        <a href="{{ route('client.dashboard') }}" class="m-dimmed m-text-xs" style="text-decoration: none;">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
        <h1 class="m-title m-title-3" style="margin-top: var(--m-sm); margin-bottom: 0;">{{ $project->project_name }}</h1>
    </div>

    @include('client.partials.project_tabs')

    @forelse($boqs as $boq)
        <div class="m-paper mb-3">
            <div class="m-paper-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h5 style="margin: 0;"><i class="fas fa-list-ol me-2" style="color: var(--m-blue-6);"></i>Bill of Quantities{{ $boq->type ? ' - ' . ucfirst($boq->type) : '' }}</h5>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span class="m-badge m-badge-{{ $boq->status === 'APPROVED' ? 'teal' : 'gray' }}">
                        {{ $boq->status ?? 'Draft' }}
                    </span>
                    <span class="m-fw-700" style="color: var(--m-blue-6);">
                        TZS {{ number_format($boq->total_amount ?? 0, 2) }}
                    </span>
                </div>
            </div>
            <div style="padding: 0;">
                <div class="table-responsive">
                    <table class="m-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">#</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th>Unit</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $counter = 0; @endphp
                            @foreach($boq->sections->where('parent_id', null)->sortBy('sort_order') as $section)
                                @include('client.partials.boq_section', ['section' => $section, 'depth' => 0, 'counter' => $counter])
                            @endforeach

                            @foreach($boq->items->where('section_id', null)->sortBy('sort_order') as $item)
                                @php $counter++; @endphp
                                <tr>
                                    <td>{{ $counter }}</td>
                                    <td>{{ $item->description }}</td>
                                    <td><span class="m-badge m-badge-{{ $item->item_type === 'material' ? 'blue' : 'gray' }}">{{ ucfirst($item->item_type ?? '-') }}</span></td>
                                    <td>{{ $item->unit }}</td>
                                    <td class="text-end">{{ number_format($item->quantity ?? 0, 2) }}</td>
                                    <td class="text-end">{{ number_format($item->unit_price ?? 0, 2) }}</td>
                                    <td class="text-end m-fw-600">{{ number_format($item->total_price ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background: var(--m-gray-0);">
                                <td colspan="6" class="text-end m-fw-700">Grand Total</td>
                                <td class="text-end m-fw-700" style="color: var(--m-blue-6);">TZS {{ number_format($boq->total_amount ?? 0, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="m-paper">
            <div class="m-paper-body" style="text-align: center; padding: 3rem var(--m-lg);">
                <i class="fas fa-list-ol" style="font-size: 2.5rem; color: var(--m-gray-3); margin-bottom: var(--m-md);"></i>
                <h3 class="m-title m-title-4" style="margin-bottom: 0.25rem;">No Bill of Quantities</h3>
                <p class="m-text-sm m-dimmed" style="margin: 0;">No BOQ has been created for this project yet.</p>
            </div>
        </div>
    @endforelse
@endsection
