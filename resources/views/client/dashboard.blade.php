@extends('layouts.client')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Welcome back, {{ $client->first_name }}!</h4>
        <p class="text-muted mb-0">Here's an overview of your projects.</p>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background: #EFF6FF; color: #2563EB;">
                        <i class="fas fa-building"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $stats['total_projects'] }}</div>
                        <div class="stat-label">Total Projects</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background: #DCFCE7; color: #16A34A;">
                        <i class="fas fa-hard-hat"></i>
                    </div>
                    <div>
                        <div class="stat-value">{{ $stats['active_projects'] }}</div>
                        <div class="stat-label">Active Projects</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background: #FEF3C7; color: #D97706;">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div>
                        <div class="stat-value">TZS {{ number_format($stats['total_contract_value'], 0) }}</div>
                        <div class="stat-label">Contract Value</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background: #F3E8FF; color: #7C3AED;">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <div class="stat-value">TZS {{ number_format($stats['total_invoiced'], 0) }}</div>
                        <div class="stat-label">Total Invoiced</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Projects Grid -->
    <div class="row g-3">
        @forelse($projects as $project)
            <div class="col-md-6 col-xl-4">
                <div class="portal-card h-100">
                    <div class="portal-card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="fw-bold mb-1">{{ $project->project_name }}</h6>
                                <span class="text-muted" style="font-size: 0.8125rem;">{{ $project->document_number }}</span>
                            </div>
                            @php
                                $statusMap = [
                                    'APPROVED' => 'success',
                                    'PENDING' => 'warning',
                                    'REJECTED' => 'danger',
                                    'COMPLETED' => 'info',
                                ];
                            @endphp
                            <span class="status-badge {{ $statusMap[$project->status] ?? 'secondary' }}">
                                {{ $project->status ?? 'N/A' }}
                            </span>
                        </div>

                        <div class="mb-3" style="font-size: 0.8125rem; color: var(--wajenzi-gray-600);">
                            @if($project->start_date)
                                <div class="mb-1"><i class="fas fa-calendar me-1"></i> {{ $project->start_date->format('M d, Y') }} - {{ $project->expected_end_date?->format('M d, Y') ?? 'Ongoing' }}</div>
                            @endif
                            <div><i class="fas fa-money-bill me-1"></i> TZS {{ number_format($project->contract_value ?? 0, 0) }}</div>
                        </div>

                        <div class="d-flex gap-2 flex-wrap" style="font-size: 0.75rem;">
                            <span class="badge bg-light text-dark"><i class="fas fa-list me-1"></i> {{ $project->boqs_count }} BOQs</span>
                            <span class="badge bg-light text-dark"><i class="fas fa-receipt me-1"></i> {{ $project->invoices_count }} Invoices</span>
                            <span class="badge bg-light text-dark"><i class="fas fa-clipboard me-1"></i> {{ $project->daily_reports_count }} Reports</span>
                        </div>

                        <hr class="my-3">
                        <a href="{{ route('client.project.show', $project->id) }}" class="btn btn-sm w-100" style="background: linear-gradient(135deg, #2563EB, #22C55E); color: white; font-weight: 600;">
                            View Project <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="portal-card">
                    <div class="portal-card-body text-center py-5">
                        <i class="fas fa-building fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Projects Yet</h5>
                        <p class="text-muted">You don't have any projects assigned yet. Contact your project manager for details.</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
@endsection
