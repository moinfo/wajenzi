@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">
                Material Request Details
                <div class="float-right">
                    <a href="{{ route('project_material_requests') }}" class="btn btn-rounded btn-outline-secondary min-width-125 mb-10">
                        <i class="fas fa-arrow-left"></i> Back to Requests
                    </a>
                </div>
            </div>

            <!-- Request Details Card -->
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Request Information</h3>
                    <div class="block-options">
                        @php
                            $statusColors = [
                                'pending' => 'warning',
                                'APPROVED' => 'success',
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'completed' => 'primary'
                            ];
                        @endphp
                        <span class="badge badge-{{ $statusColors[$request->status] ?? 'secondary' }} p-2">
                            {{ strtoupper($request->status) }}
                        </span>
                    </div>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 35%;">Request Number</th>
                                        <td class="font-w600">{{ $request->request_number }}</td>
                                    </tr>
                                    <tr>
                                        <th>Project</th>
                                        <td>{{ $request->project->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>BOQ Item</th>
                                        <td>{{ $request->boqItem->item_code ?? 'N/A' }} - {{ $request->boqItem->description ?? '' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Quantity Requested</th>
                                        <td>{{ number_format($request->quantity_requested, 2) }} {{ $request->unit }}</td>
                                    </tr>
                                    <tr>
                                        <th>Priority</th>
                                        <td>
                                            @php
                                                $priorityColors = ['low' => 'secondary', 'medium' => 'info', 'high' => 'warning', 'urgent' => 'danger'];
                                            @endphp
                                            <span class="badge badge-{{ $priorityColors[$request->priority] ?? 'secondary' }}">
                                                {{ ucfirst($request->priority ?? 'medium') }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 35%;">Required Date</th>
                                        <td>{{ $request->required_date ? \Carbon\Carbon::parse($request->required_date)->format('d M Y') : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Requested By</th>
                                        <td>{{ $request->requester->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Request Date</th>
                                        <td>{{ $request->created_at ? $request->created_at->format('d M Y H:i') : 'N/A' }}</td>
                                    </tr>
                                    @if($request->approved_by)
                                    <tr>
                                        <th>Approved By</th>
                                        <td>{{ $request->approver->name ?? 'N/A' }}</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($request->purpose)
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5>Purpose / Description</h5>
                            <p class="p-3 bg-light rounded">{{ $request->purpose }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- BOQ Item Details (if linked) -->
            @if($request->boqItem)
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">BOQ Item Details</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">BOQ Quantity</h6>
                                    <h4>{{ number_format($request->boqItem->quantity, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Already Requested</h6>
                                    <h4>{{ number_format($request->boqItem->quantity_requested ?? 0, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Received</h6>
                                    <h4>{{ number_format($request->boqItem->quantity_received ?? 0, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Remaining</h6>
                                    <h4>{{ number_format($request->boqItem->quantity_remaining ?? 0, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Approval Section -->
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Approval Status</h3>
                </div>
                <div class="block-content">
                    @if(isset($approvalStages) && count($approvalStages) > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Step</th>
                                        <th>Role</th>
                                        <th>Action</th>
                                        <th>Status</th>
                                        <th>Approved By</th>
                                        <th>Date</th>
                                        <th>Comments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($approvalStages as $stage)
                                        <tr>
                                            <td>{{ $stage->order ?? $loop->iteration }}</td>
                                            <td>{{ $stage->role->name ?? 'N/A' }}</td>
                                            <td>{{ $stage->action ?? 'APPROVE' }}</td>
                                            <td>
                                                @if($stage->approved)
                                                    <span class="badge badge-success">Approved</span>
                                                @elseif($stage->rejected ?? false)
                                                    <span class="badge badge-danger">Rejected</span>
                                                @else
                                                    <span class="badge badge-warning">Pending</span>
                                                @endif
                                            </td>
                                            <td>{{ $stage->approver->name ?? '-' }}</td>
                                            <td>{{ $stage->approved_at ? \Carbon\Carbon::parse($stage->approved_at)->format('d M Y H:i') : '-' }}</td>
                                            <td>{{ $stage->comments ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Approval Actions -->
                        @if(isset($nextApproval) && $nextApproval && !$approvalCompleted && !$rejected)
                            @can('Approve Material Request')
                                <div class="mt-4 p-3 bg-light rounded">
                                    <h5>Pending Your Action</h5>
                                    <form action="{{ route('process_approval') }}" method="POST" class="row">
                                        @csrf
                                        <input type="hidden" name="document_id" value="{{ $document_id }}">
                                        <input type="hidden" name="document_type_id" value="{{ $nextApproval->document_type_id ?? 0 }}">
                                        <input type="hidden" name="approval_id" value="{{ $nextApproval->id }}">

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Comments (optional)</label>
                                                <textarea name="comments" class="form-control" rows="2"></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                                <div>
                                                    <button type="submit" name="action" value="approve" class="btn btn-success">
                                                        <i class="fa fa-check"></i> Approve
                                                    </button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-danger ml-2">
                                                        <i class="fa fa-times"></i> Reject
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            @endcan
                        @endif
                    @else
                        <p class="text-muted">No approval workflow configured for this document type.</p>
                    @endif
                </div>
            </div>

            <!-- Actions for Approved Requests -->
            @if(strtoupper($request->status) === 'APPROVED')
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Next Steps</h3>
                </div>
                <div class="block-content">
                    <a href="{{ route('supplier_quotations.by_request', ['id' => $request->id]) }}" class="btn btn-primary">
                        <i class="fa fa-file-invoice-dollar"></i> Manage Quotations
                    </a>
                    @if($request->quotations && $request->quotations->count() >= 3)
                        <a href="{{ route('quotation_comparison.create', ['material_request_id' => $request->id]) }}" class="btn btn-success ml-2">
                            <i class="fa fa-balance-scale"></i> Create Comparison
                        </a>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection
