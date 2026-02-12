@extends('layouts.backend')

@section('css')
<style>
    .supplier-card {
        border: 2px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }
    .supplier-card:hover { border-color: #007bff; box-shadow: 0 2px 8px rgba(0,123,255,0.15); }
    .supplier-card.selected { border-color: #28a745; background-color: #f0fdf4; box-shadow: 0 2px 8px rgba(40,167,69,0.2); }
    .supplier-card .price { font-size: 1.4rem; font-weight: 700; color: #333; }
    .supplier-card .supplier-name { font-size: 1rem; font-weight: 600; margin-bottom: 2px; }
    .badge-lowest-price { background: #28a745; color: #fff; font-size: 10px; padding: 3px 8px; border-radius: 4px; }
    .comparison-table th { white-space: nowrap; font-size: 12px; }
    .comparison-table td { font-size: 12px; }
    .price-lowest { color: #28a745; font-weight: 700; }
    .price-highest { color: #dc3545; }
    .summary-stat { text-align: center; padding: 12px; }
    .summary-stat .value { font-size: 1.5rem; font-weight: 700; }
    .summary-stat .label { font-size: 11px; color: #6c757d; text-transform: uppercase; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            Quotation Comparison — {{ $materialRequest->request_number }}
            <small class="text-muted">{{ $materialRequest->project?->name ?? '' }}</small>
            <div class="float-right">
                <a href="{{ route('supplier_quotations.by_request', $materialRequest->id) }}"
                    class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <form action="{{ route('quotation_comparison.store') }}" method="POST" id="comparison-form">
            @csrf
            <input type="hidden" name="material_request_id" value="{{ $materialRequest->id }}">

            <!-- Price Summary Bar -->
            <div class="block mb-3">
                <div class="block-content py-2">
                    <div class="row">
                        <div class="col border-right">
                            <div class="summary-stat">
                                <div class="label">Quotations</div>
                                <div class="value">{{ $quotations->count() }}</div>
                            </div>
                        </div>
                        <div class="col border-right">
                            <div class="summary-stat">
                                <div class="label">Lowest</div>
                                <div class="value text-success">{{ number_format($analysis['lowest']->grand_total ?? 0, 2) }}</div>
                            </div>
                        </div>
                        <div class="col border-right">
                            <div class="summary-stat">
                                <div class="label">Average</div>
                                <div class="value">{{ number_format($analysis['average'] ?? 0, 2) }}</div>
                            </div>
                        </div>
                        <div class="col border-right">
                            <div class="summary-stat">
                                <div class="label">Highest</div>
                                <div class="value text-danger">{{ number_format($analysis['highest']->grand_total ?? 0, 2) }}</div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="summary-stat">
                                <div class="label">Potential Savings</div>
                                <div class="value text-primary">{{ number_format($analysis['variance'] ?? 0, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Per-Item Comparison Table -->
            @if(count($itemPriceMatrix) > 0)
            <div class="block mb-3">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Item-by-Item Price Comparison</h3>
                </div>
                <div class="block-content p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover mb-0 comparison-table">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th style="padding: 8px;">Item</th>
                                    <th style="padding: 8px;">Description</th>
                                    <th class="text-right" style="padding: 8px;">Qty</th>
                                    <th style="padding: 8px;">Unit</th>
                                    @foreach($quotations as $q)
                                        <th class="text-right" style="padding: 8px; min-width: 120px;">
                                            {{ $q->supplier?->name ?? 'N/A' }}
                                            <br><small class="text-muted">{{ $q->quotation_number }}</small>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($materialRequest->items as $mrItem)
                                    @php
                                        $prices = [];
                                        foreach ($quotations as $q) {
                                            $qItem = $itemPriceMatrix[$mrItem->id][$q->id] ?? null;
                                            $prices[$q->id] = $qItem ? $qItem->unit_price : null;
                                        }
                                        $validPrices = array_filter($prices, fn($p) => $p !== null);
                                        $minPrice = count($validPrices) > 0 ? min($validPrices) : null;
                                        $maxPrice = count($validPrices) > 0 ? max($validPrices) : null;
                                    @endphp
                                    <tr>
                                        <td style="padding: 6px 8px;" class="font-w600">{{ $mrItem->boqItem->item_code ?? '-' }}</td>
                                        <td style="padding: 6px 8px;">{{ Str::limit($mrItem->boqItem->description ?? $mrItem->description ?? '-', 35) }}</td>
                                        <td style="padding: 6px 8px;" class="text-right">{{ number_format($mrItem->quantity_approved ?? $mrItem->quantity_requested, 2) }}</td>
                                        <td style="padding: 6px 8px;">{{ $mrItem->unit }}</td>
                                        @foreach($quotations as $q)
                                            @php $price = $prices[$q->id]; @endphp
                                            <td class="text-right {{ $price === $minPrice && $minPrice !== $maxPrice ? 'price-lowest' : ($price === $maxPrice && $minPrice !== $maxPrice ? 'price-highest' : '') }}" style="padding: 6px 8px;">
                                                {{ $price !== null ? number_format($price, 2) : '-' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot style="background: #f8f9fa;">
                                <tr>
                                    <td colspan="4" class="text-right font-w600" style="padding: 8px;">Subtotal</td>
                                    @foreach($quotations as $q)
                                        <td class="text-right font-w600" style="padding: 8px;">{{ number_format($q->total_amount, 2) }}</td>
                                    @endforeach
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-right" style="padding: 6px 8px;">VAT (18%)</td>
                                    @foreach($quotations as $q)
                                        <td class="text-right" style="padding: 6px 8px;">{{ number_format($q->vat_amount, 2) }}</td>
                                    @endforeach
                                </tr>
                                <tr style="background: #e8f5e9;">
                                    <td colspan="4" class="text-right font-w700" style="padding: 8px;"><strong>Grand Total</strong></td>
                                    @foreach($quotations as $q)
                                        <td class="text-right font-w700" style="padding: 8px;">
                                            <strong>{{ number_format($q->grand_total, 2) }}</strong>
                                        </td>
                                    @endforeach
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Select Winning Supplier -->
            <div class="block mb-3">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Select Winning Supplier</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        @foreach($quotations as $index => $quotation)
                        <div class="col-md-4 mb-3">
                            <div class="supplier-card {{ $index === 0 ? '' : '' }}"
                                 data-quotation-id="{{ $quotation->id }}"
                                 onclick="selectQuotation({{ $quotation->id }})">

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <div class="supplier-name">{{ $quotation->supplier?->name ?? 'N/A' }}</div>
                                        <small class="text-muted">{{ $quotation->quotation_number }}</small>
                                    </div>
                                    <div>
                                        @if($index === 0)
                                            <span class="badge-lowest-price mr-2">LOWEST</span>
                                        @endif
                                        <input type="radio" name="selected_quotation_id"
                                               value="{{ $quotation->id }}" class="quotation-radio"
                                               id="quotation-{{ $quotation->id }}" required>
                                    </div>
                                </div>

                                <div class="price mb-2">{{ number_format($quotation->grand_total, 2) }}</div>

                                <div class="row small text-muted">
                                    <div class="col-6"><i class="fa fa-truck"></i> {{ $quotation->delivery_time_days ?? 'N/A' }} days</div>
                                    <div class="col-6"><i class="fa fa-calendar"></i> Valid: {{ $quotation->valid_until?->format('M d') ?? 'N/A' }}</div>
                                </div>
                                @if($quotation->payment_terms)
                                <div class="mt-1 small text-muted"><i class="fa fa-credit-card"></i> {{ $quotation->payment_terms }}</div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Justification & Submit -->
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Recommendation</h3>
                </div>
                <div class="block-content">
                    <div class="form-group">
                        <label for="recommendation_reason" class="control-label required">
                            Justification for selected supplier
                        </label>
                        <textarea name="recommendation_reason" id="recommendation_reason"
                            class="form-control" rows="3" required minlength="10"
                            placeholder="Explain why the selected quotation is recommended (price, delivery, reliability, terms, etc.)"></textarea>
                        <small class="text-muted">Minimum 10 characters</small>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-check"></i> Create Comparison & Submit for Approval
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
    function selectQuotation(quotationId) {
        document.querySelectorAll('.supplier-card').forEach(function(card) {
            card.classList.remove('selected');
        });
        var card = document.querySelector('[data-quotation-id="' + quotationId + '"]');
        if (card) card.classList.add('selected');
        var radio = document.getElementById('quotation-' + quotationId);
        if (radio) radio.checked = true;
    }

    document.getElementById('comparison-form').addEventListener('submit', function(e) {
        var selected = document.querySelector('input[name="selected_quotation_id"]:checked');
        if (!selected) {
            e.preventDefault();
            alert('Please select a winning supplier');
            return false;
        }
        var reason = document.getElementById('recommendation_reason').value.trim();
        if (reason.length < 10) {
            e.preventDefault();
            alert('Please provide a justification (minimum 10 characters)');
            return false;
        }
    });
</script>
@endsection
