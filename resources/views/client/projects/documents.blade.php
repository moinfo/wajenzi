@extends('layouts.client')

@section('title', 'Documents - ' . $project->project_name)

@section('content')
    <div style="margin-bottom: var(--m-md);">
        <a href="{{ route('client.dashboard') }}" class="m-dimmed m-text-xs" style="text-decoration: none;">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
        <h1 class="m-title m-title-3" style="margin-top: var(--m-sm); margin-bottom: 0;">{{ $project->project_name }}</h1>
    </div>

    @include('client.partials.project_tabs')

    <!-- Project Designs -->
    <div class="m-paper mb-3">
        <div class="m-paper-header">
            <h5><i class="fas fa-drafting-compass me-2" style="color: var(--m-violet-6);"></i>Project Designs</h5>
        </div>
        @if($designs->count())
            <div style="padding: 0;">
                <div class="table-responsive">
                    <table class="m-table">
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
                                    <td class="m-fw-500">{{ ucfirst($design->design_type ?? 'Design') }}</td>
                                    <td>{{ $design->version ?? '-' }}</td>
                                    <td>
                                        @php
                                            $dMap = ['approved' => 'teal', 'pending' => 'yellow', 'rejected' => 'red', 'draft' => 'gray'];
                                        @endphp
                                        <span class="m-badge m-badge-{{ $dMap[$design->status] ?? 'gray' }}">
                                            {{ ucfirst($design->status ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td>{{ $design->created_at?->format('M d, Y') ?? '-' }}</td>
                                    <td>
                                        @if($design->file_path)
                                            <a href="{{ asset('storage/' . $design->file_path) }}" target="_blank" class="m-btn m-btn-light" style="height: 1.75rem; padding: 0 0.75rem; font-size: 0.75rem;">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        @else
                                            <span class="m-dimmed">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="m-paper-body" style="text-align: center; padding: 2rem;">
                <i class="fas fa-drafting-compass" style="font-size: 2rem; color: var(--m-gray-3); margin-bottom: 0.5rem;"></i>
                <p class="m-text-sm m-dimmed" style="margin: 0;">No project designs uploaded yet.</p>
            </div>
        @endif
    </div>

    <!-- Project File -->
    @if($project->file)
        <div class="m-paper">
            <div class="m-paper-header">
                <h5><i class="fas fa-file-alt me-2" style="color: var(--m-blue-6);"></i>Project Document</h5>
            </div>
            <div class="m-paper-body">
                <a href="{{ asset('storage/' . $project->file) }}" target="_blank" class="m-btn m-btn-outline">
                    <i class="fas fa-download"></i> Download Project File
                </a>
            </div>
        </div>
    @endif
@endsection
