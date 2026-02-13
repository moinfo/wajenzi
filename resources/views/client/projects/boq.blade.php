@extends('layouts.client')

@section('title', 'BOQ - ' . $project->project_name)

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <a href="{{ route('client.dashboard') }}" class="text-muted text-decoration-none" style="font-size: 0.8125rem;">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
            <h4 class="fw-bold mt-2 mb-0">{{ $project->project_name }}</h4>
        </div>
    </div>

    @include('client.partials.project_tabs')

    @forelse($boqs as $boq)
        <div class="portal-card mb-3">
            <div class="portal-card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list-ol me-2"></i>Bill of Quantities{{ $boq->type ? ' - ' . ucfirst($boq->type) : '' }}</h5>
                <div>
                    <span class="status-badge {{ $boq->status === 'APPROVED' ? 'success' : 'secondary' }}">
                        {{ $boq->status ?? 'Draft' }}
                    </span>
                    <span class="ms-2 fw-bold" style="color: #2563EB;">
                        TZS {{ number_format($boq->total_amount ?? 0, 2) }}
                    </span>
                </div>
            </div>
            <div class="portal-card-body p-0">
                <div class="table-responsive">
                    <table class="portal-table">
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
                            {{-- Render sections recursively --}}
                            @foreach($boq->sections->where('parent_id', null)->sortBy('sort_order') as $section)
                                @include('client.partials.boq_section', ['section' => $section, 'depth' => 0, 'counter' => $counter])
                            @endforeach

                            {{-- Unsectioned items --}}
                            @foreach($boq->items->where('section_id', null)->sortBy('sort_order') as $item)
                                @php $counter++; @endphp
                                <tr>
                                    <td>{{ $counter }}</td>
                                    <td>{{ $item->description }}</td>
                                    <td><span class="status-badge {{ $item->item_type === 'material' ? 'info' : 'secondary' }}">{{ ucfirst($item->item_type ?? '-') }}</span></td>
                                    <td>{{ $item->unit }}</td>
                                    <td class="text-end">{{ number_format($item->quantity ?? 0, 2) }}</td>
                                    <td class="text-end">{{ number_format($item->unit_price ?? 0, 2) }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($item->total_price ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background: var(--wajenzi-gray-50);">
                                <td colspan="6" class="text-end fw-bold">Grand Total</td>
                                <td class="text-end fw-bold" style="color: #2563EB;">TZS {{ number_format($boq->total_amount ?? 0, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="portal-card">
            <div class="portal-card-body text-center py-5">
                <i class="fas fa-list-ol fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Bill of Quantities</h5>
                <p class="text-muted mb-0">No BOQ has been created for this project yet.</p>
            </div>
        </div>
    @endforelse
@endsection
