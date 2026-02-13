@extends('layouts.client')

@section('title', 'Documents - ' . $project->project_name)

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

    <!-- Project Designs -->
    <div class="portal-card mb-3">
        <div class="portal-card-header">
            <h5><i class="fas fa-drafting-compass me-2"></i>Project Designs</h5>
        </div>
        @if($designs->count())
            <div class="portal-card-body p-0">
                <div class="table-responsive">
                    <table class="portal-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Design Type</th>
                                <th>Version</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>File</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($designs as $design)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ ucfirst($design->design_type ?? 'Design') }}</td>
                                    <td>{{ $design->version ?? '-' }}</td>
                                    <td>
                                        @php
                                            $dMap = ['approved' => 'success', 'pending' => 'warning', 'rejected' => 'danger', 'draft' => 'secondary'];
                                        @endphp
                                        <span class="status-badge {{ $dMap[$design->status] ?? 'secondary' }}">
                                            {{ ucfirst($design->status ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td>{{ $design->created_at?->format('M d, Y') ?? '-' }}</td>
                                    <td>
                                        @if($design->file_path)
                                            <a href="{{ asset('storage/' . $design->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-download me-1"></i> Download
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="portal-card-body text-center py-4 text-muted">
                <i class="fas fa-drafting-compass fa-2x mb-2"></i>
                <p class="mb-0">No project designs uploaded yet.</p>
            </div>
        @endif
    </div>

    <!-- Project File -->
    @if($project->file)
        <div class="portal-card">
            <div class="portal-card-header">
                <h5><i class="fas fa-file-alt me-2"></i>Project Document</h5>
            </div>
            <div class="portal-card-body">
                <a href="{{ asset('storage/' . $project->file) }}" target="_blank" class="btn btn-outline-primary">
                    <i class="fas fa-download me-1"></i> Download Project File
                </a>
            </div>
        </div>
    @endif
@endsection
