@extends('layouts.backend')

@section('css')
<style>
    .delivery-info {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .inspection-section {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .inspection-section h5 {
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
    .quantity-input {
        max-width: 150px;
    }
    .condition-option {
        cursor: pointer;
        padding: 15px;
        border: 2px solid #dee2e6;
        border-radius: 8px;
        text-align: center;
        transition: all 0.2s;
    }
    .condition-option:hover {
        border-color: #007bff;
    }
    .condition-option.selected {
        border-color: #28a745;
        background-color: #f8fff8;
    }
    .condition-option i {
        font-size: 2rem;
        margin-bottom: 10px;
        display: block;
    }
    .signature-box {
        border: 1px dashed #ccc;
        padding: 20px;
        text-align: center;
        background: #fafafa;
        border-radius: 8px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            Create Material Inspection
            <div class="float-right">
                <a href="{{ route('material_inspections') }}"
                    class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <form action="{{ route('material_inspection.store') }}" method="POST" id="inspection-form">
            @csrf
            <input type="hidden" name="supplier_receiving_id" value="{{ $receiving->id }}">
            <input type="hidden" name="project_id" value="{{ $receiving->project_id ?? $receiving->purchase?->project_id }}">
            <input type="hidden" name="boq_item_id" value="{{ $receiving->purchase?->materialRequest?->boq_item_id }}">

            <div class="row">
                <!-- Left: Inspection Form -->
                <div class="col-md-8">
                    <!-- Delivery Information -->
                    <div class="delivery-info">
                        <h5><i class="fa fa-truck"></i> Delivery Information</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Receiving #:</strong><br>
                                {{ $receiving->receiving_number ?? $receiving->id }}
                            </div>
                            <div class="col-md-4">
                                <strong>Supplier:</strong><br>
                                {{ $receiving->supplier?->name ?? 'N/A' }}
                            </div>
                            <div class="col-md-4">
                                <strong>Delivery Date:</strong><br>
                                {{ $receiving->date?->format('Y-m-d') ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <strong>Delivery Note #:</strong><br>
                                {{ $receiving->delivery_note_number ?? 'N/A' }}
                            </div>
                            <div class="col-md-4">
                                <strong>Quantity Ordered:</strong><br>
                                {{ number_format($receiving->quantity_ordered ?? 0, 2) }}
                            </div>
                            <div class="col-md-4">
                                <strong>Quantity Delivered:</strong><br>
                                <span class="text-primary font-weight-bold">
                                    {{ number_format($receiving->quantity_delivered ?? $receiving->amount ?? 0, 2) }}
                                </span>
                            </div>
                        </div>
                        @if($receiving->purchase?->materialRequest?->items?->count() > 0)
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <strong>BOQ Items:</strong><br>
                                @foreach($receiving->purchase->materialRequest->items as $mrItem)
                                    {{ $mrItem->boqItem->item_code ?? '' }} - {{ $mrItem->boqItem->description ?? $mrItem->description ?? '' }}@if(!$loop->last), @endif
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Quantity Inspection -->
                    <div class="inspection-section">
                        <h5><i class="fa fa-calculator"></i> Quantity Inspection</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label required">Quantity Delivered</label>
                                    <input type="number" name="quantity_delivered" id="quantity_delivered"
                                        class="form-control quantity-input" step="0.01" required
                                        value="{{ $receiving->quantity_delivered ?? $receiving->amount ?? 0 }}"
                                        onchange="updateQuantities()">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label required">Quantity Accepted</label>
                                    <input type="number" name="quantity_accepted" id="quantity_accepted"
                                        class="form-control quantity-input" step="0.01" required min="0"
                                        value="{{ $receiving->quantity_delivered ?? $receiving->amount ?? 0 }}"
                                        onchange="updateQuantities()">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label">Quantity Rejected</label>
                                    <input type="number" name="quantity_rejected" id="quantity_rejected"
                                        class="form-control quantity-input" step="0.01" readonly
                                        value="0">
                                    <small class="text-muted">Auto-calculated</small>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info mt-2" id="acceptance-rate-alert">
                            Acceptance Rate: <strong id="acceptance-rate">100%</strong>
                        </div>
                    </div>

                    <!-- Condition Assessment -->
                    <div class="inspection-section">
                        <h5><i class="fa fa-clipboard-check"></i> Condition Assessment</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="condition-option" data-condition="excellent" onclick="selectCondition('excellent')">
                                    <i class="fa fa-star text-success"></i>
                                    <strong>Excellent</strong>
                                    <small class="d-block text-muted">Perfect condition</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="condition-option selected" data-condition="good" onclick="selectCondition('good')">
                                    <i class="fa fa-thumbs-up text-primary"></i>
                                    <strong>Good</strong>
                                    <small class="d-block text-muted">Acceptable quality</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="condition-option" data-condition="fair" onclick="selectCondition('fair')">
                                    <i class="fa fa-exclamation-triangle text-warning"></i>
                                    <strong>Fair</strong>
                                    <small class="d-block text-muted">Minor issues</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="condition-option" data-condition="poor" onclick="selectCondition('poor')">
                                    <i class="fa fa-times-circle text-danger"></i>
                                    <strong>Poor</strong>
                                    <small class="d-block text-muted">Major issues</small>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="overall_condition" id="overall_condition" value="good" required>
                    </div>

                    <!-- Overall Result -->
                    <div class="inspection-section">
                        <h5><i class="fa fa-check-double"></i> Overall Result</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="overall_result"
                                        id="result_accepted" value="accepted" checked>
                                    <label class="form-check-label text-success" for="result_accepted">
                                        <strong><i class="fa fa-check"></i> Accepted</strong>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="overall_result"
                                        id="result_partial" value="partial">
                                    <label class="form-check-label text-warning" for="result_partial">
                                        <strong><i class="fa fa-exclamation"></i> Partially Accepted</strong>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="overall_result"
                                        id="result_rejected" value="rejected">
                                    <label class="form-check-label text-danger" for="result_rejected">
                                        <strong><i class="fa fa-times"></i> Rejected</strong>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-3" id="rejection-reason-group" style="display: none;">
                            <label class="control-label required">Rejection Reason</label>
                            <textarea name="rejection_reason" id="rejection_reason"
                                class="form-control" rows="3"
                                placeholder="Explain why the materials were rejected or partially accepted..."></textarea>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="inspection-section">
                        <h5><i class="fa fa-sticky-note"></i> Inspection Notes</h5>
                        <div class="form-group">
                            <textarea name="notes" class="form-control" rows="3"
                                placeholder="Additional notes about the inspection..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Right: Summary & Actions -->
                <div class="col-md-4">
                    <!-- Inspection Summary -->
                    <div class="block">
                        <div class="block-header block-header-default bg-primary">
                            <h3 class="block-title text-white">Inspection Summary</h3>
                        </div>
                        <div class="block-content">
                            <table class="table table-sm">
                                <tr>
                                    <td>Delivered:</td>
                                    <td class="text-right"><strong id="summary-delivered">0</strong></td>
                                </tr>
                                <tr class="text-success">
                                    <td>Accepted:</td>
                                    <td class="text-right"><strong id="summary-accepted">0</strong></td>
                                </tr>
                                <tr class="text-danger">
                                    <td>Rejected:</td>
                                    <td class="text-right"><strong id="summary-rejected">0</strong></td>
                                </tr>
                                <tr>
                                    <td>Condition:</td>
                                    <td class="text-right"><strong id="summary-condition">Good</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Project Info -->
                    @if($receiving->purchase?->project)
                    <div class="block">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">Project</h3>
                        </div>
                        <div class="block-content">
                            <p><strong>{{ $receiving->purchase->project->name }}</strong></p>
                            @if($receiving->purchase->materialRequest)
                            <p class="text-muted small">
                                Request: {{ $receiving->purchase->materialRequest->request_number }}
                            </p>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Signatures (placeholder) -->
                    <div class="block">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">Verification</h3>
                        </div>
                        <div class="block-content">
                            <div class="signature-box mb-3">
                                <small class="text-muted">Inspector Signature</small>
                                <p class="mb-0"><strong>{{ auth()->user()->name }}</strong></p>
                                <small>{{ now()->format('Y-m-d H:i') }}</small>
                            </div>
                            <p class="text-muted small">
                                <i class="fa fa-info-circle"></i>
                                Additional verification signatures will be collected during approval.
                            </p>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="block">
                        <div class="block-content">
                            <button type="submit" class="btn btn-success btn-lg btn-block">
                                <i class="fa fa-clipboard-check"></i> Submit Inspection
                            </button>
                            <a href="{{ route('material_inspections') }}"
                                class="btn btn-outline-secondary btn-block mt-2">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
    function updateQuantities() {
        const delivered = parseFloat(document.getElementById('quantity_delivered').value) || 0;
        const accepted = parseFloat(document.getElementById('quantity_accepted').value) || 0;
        const rejected = delivered - accepted;

        document.getElementById('quantity_rejected').value = Math.max(0, rejected).toFixed(2);

        // Update acceptance rate
        const rate = delivered > 0 ? (accepted / delivered * 100) : 100;
        document.getElementById('acceptance-rate').textContent = rate.toFixed(1) + '%';

        // Update alert class based on rate
        const alert = document.getElementById('acceptance-rate-alert');
        alert.className = 'alert mt-2 ';
        if (rate >= 95) {
            alert.className += 'alert-success';
        } else if (rate >= 80) {
            alert.className += 'alert-warning';
        } else {
            alert.className += 'alert-danger';
        }

        // Update summary
        document.getElementById('summary-delivered').textContent = delivered.toFixed(2);
        document.getElementById('summary-accepted').textContent = accepted.toFixed(2);
        document.getElementById('summary-rejected').textContent = Math.max(0, rejected).toFixed(2);

        // Auto-select result based on quantities
        if (rejected > 0 && accepted > 0) {
            document.getElementById('result_partial').checked = true;
            showRejectionReason();
        } else if (accepted === 0 && rejected > 0) {
            document.getElementById('result_rejected').checked = true;
            showRejectionReason();
        } else {
            document.getElementById('result_accepted').checked = true;
            hideRejectionReason();
        }
    }

    function selectCondition(condition) {
        document.querySelectorAll('.condition-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        document.querySelector(`[data-condition="${condition}"]`).classList.add('selected');
        document.getElementById('overall_condition').value = condition;

        // Update summary
        document.getElementById('summary-condition').textContent =
            condition.charAt(0).toUpperCase() + condition.slice(1);
    }

    function showRejectionReason() {
        document.getElementById('rejection-reason-group').style.display = 'block';
        document.getElementById('rejection_reason').required = true;
    }

    function hideRejectionReason() {
        document.getElementById('rejection-reason-group').style.display = 'none';
        document.getElementById('rejection_reason').required = false;
    }

    // Handle result radio changes
    document.querySelectorAll('input[name="overall_result"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'accepted') {
                hideRejectionReason();
            } else {
                showRejectionReason();
            }
        });
    });

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateQuantities();
    });

    // Form validation
    document.getElementById('inspection-form').addEventListener('submit', function(e) {
        const accepted = parseFloat(document.getElementById('quantity_accepted').value) || 0;
        const delivered = parseFloat(document.getElementById('quantity_delivered').value) || 0;

        if (accepted > delivered) {
            e.preventDefault();
            alert('Accepted quantity cannot exceed delivered quantity.');
            return false;
        }

        const result = document.querySelector('input[name="overall_result"]:checked').value;
        if (result !== 'accepted') {
            const reason = document.getElementById('rejection_reason').value.trim();
            if (reason.length < 10) {
                e.preventDefault();
                alert('Please provide a rejection reason (minimum 10 characters).');
                return false;
            }
        }
    });
</script>
@endsection
