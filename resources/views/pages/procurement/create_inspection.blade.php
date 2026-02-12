@extends('layouts.backend')

@section('css')
<style>
    .delivery-header {
        background: linear-gradient(135deg, #2563EB 0%, #22C55E 100%);
        border-radius: 12px;
        padding: 24px;
        color: white;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }
    .delivery-header::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(255,255,255,0.08);
        backdrop-filter: blur(10px);
    }
    .delivery-header .row { position: relative; z-index: 1; }
    .delivery-header label { opacity: 0.8; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
    .delivery-header .value { font-size: 1rem; font-weight: 600; }

    .items-table-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        margin-bottom: 24px;
    }
    .items-table-card .card-header-custom {
        background: #f8fafc;
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 600;
        color: #1e293b;
        font-size: 1rem;
    }
    .items-table-card .card-header-custom i { color: #2563EB; margin-right: 8px; }

    .inspection-card {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        margin-bottom: 24px;
        background: white;
    }
    .inspection-card .card-header-custom {
        background: #f8fafc;
        padding: 14px 20px;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .inspection-card .card-header-custom i { color: #2563EB; }
    .inspection-card .card-body-custom { padding: 20px; }

    .qty-card {
        background: #f8fafc;
        border-radius: 10px;
        padding: 16px;
        text-align: center;
        border: 1px solid #e2e8f0;
    }
    .qty-card label { font-size: 0.8rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 8px; }
    .qty-card input {
        text-align: center;
        font-size: 1.25rem;
        font-weight: 700;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        padding: 8px;
        transition: border-color 0.2s;
    }
    .qty-card input:focus { border-color: #2563EB; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    .qty-card.accepted input { color: #16a34a; }
    .qty-card.rejected input { color: #dc2626; }

    .rate-bar {
        height: 8px;
        background: #e2e8f0;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 8px;
    }
    .rate-bar-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.4s ease, background 0.4s ease;
    }

    .condition-card {
        cursor: pointer;
        padding: 16px 12px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        text-align: center;
        transition: all 0.25s ease;
        background: white;
    }
    .condition-card:hover {
        border-color: #2563EB;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37,99,235,0.15);
    }
    .condition-card.selected {
        border-color: #2563EB;
        background: linear-gradient(135deg, rgba(37,99,235,0.05), rgba(34,197,94,0.05));
        box-shadow: 0 4px 12px rgba(37,99,235,0.15);
    }
    .condition-card .icon {
        width: 48px; height: 48px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 8px;
    }
    .condition-card .icon.excellent { background: #dcfce7; color: #16a34a; }
    .condition-card .icon.good { background: #dbeafe; color: #2563eb; }
    .condition-card .icon.fair { background: #fef3c7; color: #d97706; }
    .condition-card .icon.poor { background: #fee2e2; color: #dc2626; }
    .condition-card strong { display: block; font-size: 0.9rem; margin-bottom: 2px; }
    .condition-card small { color: #94a3b8; }

    .result-option {
        display: flex;
        align-items: center;
        padding: 14px 18px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
        gap: 12px;
    }
    .result-option:hover { background: #f8fafc; }
    .result-option.selected-accepted { border-color: #16a34a; background: #f0fdf4; }
    .result-option.selected-partial { border-color: #d97706; background: #fffbeb; }
    .result-option.selected-rejected { border-color: #dc2626; background: #fef2f2; }
    .result-option input[type="radio"] { display: none; }
    .result-option .radio-dot {
        width: 20px; height: 20px;
        border: 2px solid #cbd5e1;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: all 0.2s;
    }
    .result-option.selected-accepted .radio-dot,
    .result-option.selected-partial .radio-dot,
    .result-option.selected-rejected .radio-dot { border-width: 6px; }
    .result-option.selected-accepted .radio-dot { border-color: #16a34a; }
    .result-option.selected-partial .radio-dot { border-color: #d97706; }
    .result-option.selected-rejected .radio-dot { border-color: #dc2626; }

    .checklist-item {
        display: flex;
        align-items: center;
        padding: 10px 14px;
        border-radius: 8px;
        margin-bottom: 6px;
        transition: background 0.2s;
        gap: 10px;
    }
    .checklist-item:hover { background: #f8fafc; }
    .checklist-item input[type="checkbox"] {
        width: 18px; height: 18px;
        accent-color: #2563EB;
        cursor: pointer;
        flex-shrink: 0;
    }
    .checklist-item label { margin: 0; cursor: pointer; font-weight: 400; color: #334155; }

    .summary-card {
        border-radius: 12px;
        border: 2px solid #2563EB;
        overflow: hidden;
        position: sticky;
        top: 20px;
    }
    .summary-card .summary-header {
        background: linear-gradient(135deg, #2563EB, #1d4ed8);
        color: white;
        padding: 16px 20px;
        font-weight: 600;
        font-size: 1rem;
    }
    .summary-card .summary-body { padding: 20px; }
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .summary-row:last-child { border-bottom: none; }
    .summary-row .label { color: #64748b; }
    .summary-row .value { font-weight: 600; }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">Create Material Inspection
            <div class="float-right">
                <a href="{{ route('material_inspections') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h5 class="alert-heading font-weight-bold"><i class="fa fa-exclamation-circle mr-1"></i> Validation Errors</h5>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('material_inspection.store') }}" method="POST" id="inspection-form">
            @csrf
            <input type="hidden" name="supplier_receiving_id" value="{{ $receiving->id }}">
            <input type="hidden" name="project_id" value="{{ $receiving->project_id ?? $receiving->purchase?->project_id }}">
            <input type="hidden" name="boq_item_id" value="{{ $receiving->purchase?->materialRequest?->boq_item_id }}">

            {{-- Delivery Header --}}
            <div class="delivery-header">
                <div class="row">
                    <div class="col-md-2">
                        <label>Receiving #</label>
                        <div class="value">{{ $receiving->receiving_number ?? $receiving->id }}</div>
                    </div>
                    <div class="col-md-2">
                        <label>Supplier</label>
                        <div class="value">{{ $receiving->supplier?->name ?? 'N/A' }}</div>
                    </div>
                    <div class="col-md-2">
                        <label>Delivery Date</label>
                        <div class="value">{{ $receiving->date?->format('d M Y') ?? 'N/A' }}</div>
                    </div>
                    <div class="col-md-2">
                        <label>Delivery Note</label>
                        <div class="value">{{ $receiving->delivery_note_number ?? 'N/A' }}</div>
                    </div>
                    <div class="col-md-2">
                        <label>Project</label>
                        <div class="value">{{ $receiving->purchase?->project?->name ?? 'N/A' }}</div>
                    </div>
                    <div class="col-md-2">
                        <label>PO Number</label>
                        <div class="value">{{ $receiving->purchase?->document_number ?? ('PO-' . $receiving->purchase_id) }}</div>
                    </div>
                </div>
            </div>

            {{-- Items Table --}}
            @if($receiving->purchase?->purchaseItems?->count())
            <div class="items-table-card">
                <div class="card-header-custom">
                    <i class="fa fa-boxes"></i> Delivered Items
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter mb-0">
                        <thead style="background: #f1f5f9;">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Description</th>
                                <th>BOQ Item</th>
                                <th class="text-center">Unit</th>
                                <th class="text-right">Ordered</th>
                                <th class="text-right">Received</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receiving->purchase->purchaseItems as $pItem)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $pItem->description }}</td>
                                <td><code>{{ $pItem->boqItem?->item_code ?? '-' }}</code></td>
                                <td class="text-center">{{ $pItem->unit }}</td>
                                <td class="text-right">{{ number_format($pItem->quantity, 2) }}</td>
                                <td class="text-right font-weight-bold">{{ number_format($pItem->quantity_received, 2) }}</td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $pItem->status_badge_class }}">{{ ucfirst($pItem->status ?? 'pending') }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot style="background: #f8fafc;">
                            <tr>
                                <td colspan="4" class="text-right"><strong>Totals</strong></td>
                                <td class="text-right"><strong>{{ number_format($receiving->quantity_ordered, 2) }}</strong></td>
                                <td class="text-right"><strong>{{ number_format($receiving->quantity_delivered, 2) }}</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif

            <div class="row">
                {{-- Left Column --}}
                <div class="col-lg-8">
                    {{-- Quantity Inspection --}}
                    <div class="inspection-card">
                        <div class="card-header-custom">
                            <i class="fa fa-calculator"></i> Quantity Inspection
                        </div>
                        <div class="card-body-custom">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="qty-card">
                                        <label>Qty Delivered</label>
                                        <input type="number" name="quantity_delivered" id="quantity_delivered"
                                            class="form-control" step="0.01" required
                                            value="{{ $receiving->quantity_delivered ?? 0 }}"
                                            onchange="updateQuantities()">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="qty-card accepted">
                                        <label>Qty Accepted</label>
                                        <input type="number" name="quantity_accepted" id="quantity_accepted"
                                            class="form-control" step="0.01" required min="0"
                                            value="{{ $receiving->quantity_delivered ?? 0 }}"
                                            onchange="updateQuantities()">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="qty-card rejected">
                                        <label>Qty Rejected</label>
                                        <input type="number" name="quantity_rejected" id="quantity_rejected"
                                            class="form-control" step="0.01" readonly value="0">
                                        <small class="text-muted">Auto-calculated</small>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Acceptance Rate</span>
                                    <strong id="acceptance-rate" style="font-size: 1.1rem;">100%</strong>
                                </div>
                                <div class="rate-bar">
                                    <div class="rate-bar-fill" id="rate-bar-fill" style="width: 100%; background: #16a34a;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Condition Assessment --}}
                    <div class="inspection-card">
                        <div class="card-header-custom">
                            <i class="fa fa-clipboard-check"></i> Condition Assessment
                        </div>
                        <div class="card-body-custom">
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <div class="condition-card" data-condition="excellent" onclick="selectCondition('excellent')">
                                        <div class="icon excellent"><i class="fa fa-star"></i></div>
                                        <strong>Excellent</strong>
                                        <small>Perfect condition</small>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="condition-card selected" data-condition="good" onclick="selectCondition('good')">
                                        <div class="icon good"><i class="fa fa-thumbs-up"></i></div>
                                        <strong>Good</strong>
                                        <small>Acceptable quality</small>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="condition-card" data-condition="acceptable" onclick="selectCondition('acceptable')">
                                        <div class="icon fair"><i class="fa fa-exclamation-triangle"></i></div>
                                        <strong>Fair</strong>
                                        <small>Minor issues</small>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <div class="condition-card" data-condition="poor" onclick="selectCondition('poor')">
                                        <div class="icon poor"><i class="fa fa-times-circle"></i></div>
                                        <strong>Poor</strong>
                                        <small>Major issues</small>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="overall_condition" id="overall_condition" value="good" required>
                        </div>
                    </div>

                    {{-- Overall Result --}}
                    <div class="inspection-card">
                        <div class="card-header-custom">
                            <i class="fa fa-check-double"></i> Overall Result
                        </div>
                        <div class="card-body-custom">
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="result-option selected-accepted" onclick="selectResult('accepted', this)">
                                        <input type="radio" name="overall_result" value="accepted" checked>
                                        <div class="radio-dot"></div>
                                        <div>
                                            <strong class="text-success">Accepted</strong>
                                            <small class="d-block text-muted">All materials pass</small>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="result-option" onclick="selectResult('partial', this)">
                                        <input type="radio" name="overall_result" value="partial">
                                        <div class="radio-dot"></div>
                                        <div>
                                            <strong class="text-warning">Partial</strong>
                                            <small class="d-block text-muted">Some items rejected</small>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="result-option" onclick="selectResult('rejected', this)">
                                        <input type="radio" name="overall_result" value="rejected">
                                        <div class="radio-dot"></div>
                                        <div>
                                            <strong class="text-danger">Rejected</strong>
                                            <small class="d-block text-muted">Materials unacceptable</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div id="rejection-reason-group" style="display: none; margin-top: 16px;">
                                <label class="font-weight-bold">Rejection Reason <span class="text-danger">*</span></label>
                                <textarea name="rejection_reason" id="rejection_reason"
                                    class="form-control" rows="3"
                                    placeholder="Explain why materials were rejected or partially accepted..."></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Criteria Checklist --}}
                    <div class="inspection-card">
                        <div class="card-header-custom">
                            <i class="fa fa-tasks"></i> Inspection Checklist
                        </div>
                        <div class="card-body-custom">
                            @foreach($criteriaChecklist as $key => $label)
                            <div class="checklist-item">
                                <input type="checkbox" name="criteria_{{ $key }}" id="criteria_{{ $key }}" value="1" checked>
                                <label for="criteria_{{ $key }}">{{ $label }}</label>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="inspection-card">
                        <div class="card-header-custom">
                            <i class="fa fa-sticky-note"></i> Inspection Notes
                        </div>
                        <div class="card-body-custom">
                            <textarea name="inspection_notes" class="form-control" rows="3"
                                placeholder="Additional observations, measurements, or comments..."></textarea>
                        </div>
                    </div>
                </div>

                {{-- Right Column - Summary --}}
                <div class="col-lg-4">
                    <div class="summary-card">
                        <div class="summary-header">
                            <i class="fa fa-chart-bar mr-2"></i> Inspection Summary
                        </div>
                        <div class="summary-body">
                            <div class="summary-row">
                                <span class="label">Delivered</span>
                                <span class="value" id="summary-delivered">0</span>
                            </div>
                            <div class="summary-row">
                                <span class="label">Accepted</span>
                                <span class="value text-success" id="summary-accepted">0</span>
                            </div>
                            <div class="summary-row">
                                <span class="label">Rejected</span>
                                <span class="value text-danger" id="summary-rejected">0</span>
                            </div>
                            <div class="summary-row">
                                <span class="label">Condition</span>
                                <span class="value" id="summary-condition">Good</span>
                            </div>
                            <div class="summary-row">
                                <span class="label">Result</span>
                                <span class="value text-success" id="summary-result">Accepted</span>
                            </div>
                            <div class="summary-row">
                                <span class="label">Inspector</span>
                                <span class="value">{{ auth()->user()->name }}</span>
                            </div>
                            <div class="summary-row">
                                <span class="label">Date</span>
                                <span class="value">{{ now()->format('d M Y') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-success btn-lg btn-block" style="border-radius: 10px; padding: 14px;">
                            <i class="fa fa-clipboard-check mr-1"></i> Submit Inspection
                        </button>
                        <a href="{{ route('material_inspections') }}"
                            class="btn btn-outline-secondary btn-block mt-2" style="border-radius: 10px;">
                            Cancel
                        </a>
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
        const rejected = Math.max(0, delivered - accepted);

        document.getElementById('quantity_rejected').value = rejected.toFixed(2);

        const rate = delivered > 0 ? (accepted / delivered * 100) : 100;
        document.getElementById('acceptance-rate').textContent = rate.toFixed(1) + '%';

        // Rate bar
        const fill = document.getElementById('rate-bar-fill');
        fill.style.width = rate + '%';
        fill.style.background = rate >= 95 ? '#16a34a' : rate >= 80 ? '#d97706' : '#dc2626';

        // Summary
        document.getElementById('summary-delivered').textContent = delivered.toFixed(2);
        document.getElementById('summary-accepted').textContent = accepted.toFixed(2);
        document.getElementById('summary-rejected').textContent = rejected.toFixed(2);

        // Auto-select result
        if (rejected > 0 && accepted > 0) {
            document.querySelector('input[name="overall_result"][value="partial"]').checked = true;
            highlightResult('partial');
            showRejectionReason();
        } else if (accepted === 0 && delivered > 0) {
            document.querySelector('input[name="overall_result"][value="rejected"]').checked = true;
            highlightResult('rejected');
            showRejectionReason();
        } else {
            document.querySelector('input[name="overall_result"][value="accepted"]').checked = true;
            highlightResult('accepted');
            hideRejectionReason();
        }
    }

    function selectCondition(condition) {
        document.querySelectorAll('.condition-card').forEach(c => c.classList.remove('selected'));
        document.querySelector(`[data-condition="${condition}"]`).classList.add('selected');
        document.getElementById('overall_condition').value = condition;
        document.getElementById('summary-condition').textContent = condition.charAt(0).toUpperCase() + condition.slice(1);
    }

    function selectResult(value, el) {
        el.querySelector('input[type="radio"]').checked = true;
        highlightResult(value);
        if (value === 'accepted') {
            hideRejectionReason();
        } else {
            showRejectionReason();
        }
    }

    function highlightResult(value) {
        document.querySelectorAll('.result-option').forEach(opt => {
            opt.className = 'result-option';
        });
        const checked = document.querySelector(`input[name="overall_result"][value="${value}"]`);
        if (checked) {
            checked.closest('.result-option').classList.add('selected-' + value);
        }
        const resultEl = document.getElementById('summary-result');
        resultEl.textContent = value.charAt(0).toUpperCase() + value.slice(1);
        resultEl.className = 'value text-' + (value === 'accepted' ? 'success' : value === 'partial' ? 'warning' : 'danger');
    }

    function showRejectionReason() {
        document.getElementById('rejection-reason-group').style.display = 'block';
        document.getElementById('rejection_reason').required = true;
    }

    function hideRejectionReason() {
        document.getElementById('rejection-reason-group').style.display = 'none';
        document.getElementById('rejection_reason').required = false;
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateQuantities();
    });

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
