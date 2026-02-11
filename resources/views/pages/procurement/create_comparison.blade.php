@extends('layouts.backend')

@section('css')
<style>
    .quotation-card {
        border: 2px solid #dee2e6;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 15px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .quotation-card:hover {
        border-color: #007bff;
        box-shadow: 0 4px 12px rgba(0,123,255,0.15);
    }
    .quotation-card.selected {
        border-color: #28a745;
        background-color: #f8fff8;
        box-shadow: 0 4px 12px rgba(40,167,69,0.2);
    }
    .quotation-card.lowest {
        border-color: #28a745;
    }
    .quotation-card .price {
        font-size: 1.5rem;
        font-weight: bold;
    }
    .quotation-card .badge-lowest {
        position: absolute;
        top: -10px;
        right: 10px;
    }
    .analysis-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 10px;
        padding: 20px;
    }
    .analysis-card h4 {
        margin-bottom: 15px;
    }
    .analysis-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid rgba(255,255,255,0.2);
    }
    .analysis-item:last-child {
        border-bottom: none;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            Create Quotation Comparison
            <div class="float-right">
                <a href="{{ route('supplier_quotations.by_request', $materialRequest->id) }}"
                    class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Back to Quotations
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Left: Quotations Selection -->
            <div class="col-md-8">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Select Winning Quotation</h3>
                    </div>
                    <div class="block-content">
                        <form action="{{ route('quotation_comparison.store') }}" method="POST" id="comparison-form">
                            @csrf
                            <input type="hidden" name="material_request_id" value="{{ $materialRequest->id }}">

                            <div class="row">
                                @foreach($quotations as $index => $quotation)
                                <div class="col-md-6">
                                    <div class="quotation-card position-relative {{ $index === 0 ? 'lowest' : '' }}"
                                         data-quotation-id="{{ $quotation->id }}"
                                         onclick="selectQuotation({{ $quotation->id }})">
                                        @if($index === 0)
                                            <span class="badge badge-success badge-lowest">Lowest Price</span>
                                        @endif
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="mb-1">{{ $quotation->supplier?->name }}</h5>
                                                <small class="text-muted">{{ $quotation->quotation_number }}</small>
                                            </div>
                                            <input type="radio" name="selected_quotation_id"
                                                   value="{{ $quotation->id }}" class="quotation-radio"
                                                   id="quotation-{{ $quotation->id }}" required>
                                        </div>

                                        <div class="price text-primary mb-2">
                                            {{ number_format($quotation->grand_total, 2) }}
                                        </div>

                                        <div class="row text-muted small">
                                            <div class="col-6">
                                                <strong>Unit Price:</strong><br>
                                                {{ number_format($quotation->unit_price, 2) }}
                                            </div>
                                            <div class="col-6">
                                                <strong>Quantity:</strong><br>
                                                {{ number_format($quotation->quantity, 2) }}
                                            </div>
                                        </div>

                                        <hr>

                                        <div class="row text-muted small">
                                            <div class="col-6">
                                                <i class="fa fa-truck"></i> {{ $quotation->delivery_time_days ?? 'N/A' }} days
                                            </div>
                                            <div class="col-6">
                                                <i class="fa fa-calendar"></i> Valid: {{ $quotation->valid_until?->format('M d') ?? 'N/A' }}
                                            </div>
                                        </div>

                                        @if($quotation->payment_terms)
                                        <div class="mt-2 small text-muted">
                                            <i class="fa fa-credit-card"></i> {{ $quotation->payment_terms }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <div class="form-group mt-4">
                                <label for="recommendation_reason" class="control-label required">
                                    Recommendation Reason / Justification
                                </label>
                                <textarea name="recommendation_reason" id="recommendation_reason"
                                    class="form-control" rows="4" required minlength="10"
                                    placeholder="Explain why the selected quotation is recommended. Consider factors like price, delivery time, supplier reliability, payment terms, etc."></textarea>
                                <small class="text-muted">Minimum 10 characters required</small>
                            </div>

                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fa fa-check"></i> Create Comparison & Submit for Approval
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right: Analysis -->
            <div class="col-md-4">
                <!-- Request Info -->
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Request Details</h3>
                    </div>
                    <div class="block-content">
                        <p><strong>Request #:</strong> {{ $materialRequest->request_number }}</p>
                        <p><strong>Project:</strong> {{ $materialRequest->project?->name ?? 'N/A' }}</p>
                        <p><strong>Items:</strong> {{ $materialRequest->items->count() }} item(s)</p>
                        <p><strong>Required:</strong> {{ $materialRequest->required_date?->format('Y-m-d') ?? 'N/A' }}</p>
                        @if($materialRequest->items->count() > 0)
                        <p><strong>BOQ Items:</strong><br>
                            @foreach($materialRequest->items as $mrItem)
                                <small>{{ $mrItem->boqItem->item_code ?? '' }} - {{ $mrItem->boqItem->description ?? $mrItem->description ?? '' }} ({{ number_format($mrItem->quantity_requested, 2) }} {{ $mrItem->unit }})</small>@if(!$loop->last)<br>@endif
                            @endforeach
                        </p>
                        @endif
                    </div>
                </div>

                <!-- Price Analysis -->
                <div class="analysis-card">
                    <h4><i class="fa fa-chart-line"></i> Price Analysis</h4>

                    <div class="analysis-item">
                        <span>Quotations:</span>
                        <strong>{{ $quotations->count() }}</strong>
                    </div>

                    <div class="analysis-item">
                        <span>Lowest:</span>
                        <strong>{{ number_format($analysis['lowest']->grand_total ?? 0, 2) }}</strong>
                    </div>

                    <div class="analysis-item">
                        <span>Highest:</span>
                        <strong>{{ number_format($analysis['highest']->grand_total ?? 0, 2) }}</strong>
                    </div>

                    <div class="analysis-item">
                        <span>Average:</span>
                        <strong>{{ number_format($analysis['average'] ?? 0, 2) }}</strong>
                    </div>

                    <div class="analysis-item">
                        <span>Price Variance:</span>
                        <strong>{{ number_format($analysis['variance'] ?? 0, 2) }}</strong>
                    </div>

                    @if(($analysis['lowest']->grand_total ?? 0) > 0)
                    <div class="analysis-item">
                        <span>Potential Savings:</span>
                        <strong>{{ number_format((($analysis['highest']->grand_total ?? 0) - ($analysis['lowest']->grand_total ?? 0)), 2) }}</strong>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    function selectQuotation(quotationId) {
        // Remove selected class from all cards
        document.querySelectorAll('.quotation-card').forEach(card => {
            card.classList.remove('selected');
        });

        // Add selected class to clicked card
        const selectedCard = document.querySelector(`[data-quotation-id="${quotationId}"]`);
        if (selectedCard) {
            selectedCard.classList.add('selected');
        }

        // Check the radio button
        const radio = document.getElementById(`quotation-${quotationId}`);
        if (radio) {
            radio.checked = true;
        }
    }

    // Handle form validation
    document.getElementById('comparison-form').addEventListener('submit', function(e) {
        const selected = document.querySelector('input[name="selected_quotation_id"]:checked');
        if (!selected) {
            e.preventDefault();
            alert('Please select a quotation');
            return false;
        }

        const reason = document.getElementById('recommendation_reason').value.trim();
        if (reason.length < 10) {
            e.preventDefault();
            alert('Please provide a recommendation reason (minimum 10 characters)');
            return false;
        }
    });
</script>
@endsection
