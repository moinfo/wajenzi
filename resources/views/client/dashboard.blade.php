@extends('layouts.client')

@section('title', 'Dashboard')

@section('content')
    {{-- Page title --}}
    <h1 class="m-title m-title-2" style="margin-bottom: 0.25rem;">Welcome back, {{ $client->first_name }}</h1>
    <p class="m-text-sm m-dimmed" style="margin: 0 0 var(--m-xl);">Here's an overview of your construction projects</p>

    {{-- Mantine StatsGrid: Paper withBorder p="md" radius="md" --}}
    <div class="m-stat-grid" style="margin-bottom: calc(var(--m-xl) * 1.5);">
        <div class="m-stat">
            <div class="m-stat-top">
                <span class="m-stat-title">Total Projects</span>
                <i class="fas fa-building m-stat-icon"></i>
            </div>
            <div class="m-group" style="align-items: flex-end; gap: var(--m-xs);">
                <span class="m-stat-value">{{ $stats['total_projects'] }}</span>
            </div>
            <p class="m-stat-desc">All assigned projects</p>
        </div>
        <div class="m-stat">
            <div class="m-stat-top">
                <span class="m-stat-title">Active Projects</span>
                <i class="fas fa-hard-hat m-stat-icon"></i>
            </div>
            <div class="m-group" style="align-items: flex-end; gap: var(--m-xs);">
                <span class="m-stat-value">{{ $stats['active_projects'] }}</span>
            </div>
            <p class="m-stat-desc">Currently in progress</p>
        </div>
        <div class="m-stat">
            <div class="m-stat-top">
                <span class="m-stat-title">Contract Value</span>
                <i class="fas fa-file-contract m-stat-icon"></i>
            </div>
            <div class="m-group" style="align-items: flex-end; gap: var(--m-xs);">
                <span class="m-stat-value" style="font-size: {{ strlen(number_format($stats['total_contract_value'], 0)) > 10 ? '1rem' : '1.5rem' }};">TZS {{ number_format($stats['total_contract_value'], 0) }}</span>
            </div>
            <p class="m-stat-desc">Combined contract value</p>
        </div>
        <div class="m-stat">
            <div class="m-stat-top">
                <span class="m-stat-title">Total Invoiced</span>
                <i class="fas fa-receipt m-stat-icon"></i>
            </div>
            <div class="m-group" style="align-items: flex-end; gap: var(--m-xs);">
                <span class="m-stat-value" style="font-size: {{ strlen(number_format($stats['total_invoiced'], 0)) > 10 ? '1rem' : '1.5rem' }};">TZS {{ number_format($stats['total_invoiced'], 0) }}</span>
            </div>
            <p class="m-stat-desc">Total amount invoiced</p>
        </div>
    </div>

    {{-- Section Header --}}
    <div class="m-group" style="justify-content: space-between; margin-bottom: var(--m-md);">
        <h2 class="m-title m-title-4">Your Projects</h2>
        <span class="m-badge m-badge-gray">{{ $stats['total_projects'] }} {{ Str::plural('project', $stats['total_projects']) }}</span>
    </div>

    {{-- Projects List --}}
    @forelse($projects as $project)
        <div class="m-paper" style="margin-bottom: var(--m-md);">
            <div class="m-paper-body">
                <div class="m-group" style="justify-content: space-between; margin-bottom: var(--m-sm);">
                    <div style="min-width: 0;">
                        <span class="m-fw-600" style="font-size: var(--m-fz-sm);">{{ $project->project_name }}</span>
                        <span class="m-text-xs m-dimmed" style="margin-left: var(--m-sm);">{{ $project->document_number }}</span>
                    </div>
                    @php
                        $badgeMap = ['APPROVED' => 'teal', 'PENDING' => 'yellow', 'REJECTED' => 'red', 'COMPLETED' => 'blue'];
                    @endphp
                    <span class="m-badge m-badge-{{ $badgeMap[$project->status] ?? 'gray' }}">{{ $project->status ?? 'N/A' }}</span>
                </div>

                <div class="m-group" style="gap: var(--m-xl); margin-bottom: var(--m-sm);">
                    @if($project->start_date)
                        <span class="m-text-sm m-dimmed">
                            <i class="fas fa-calendar" style="margin-right: 0.375rem; font-size: var(--m-fz-xs);"></i>{{ $project->start_date->format('M d, Y') }} — {{ $project->expected_end_date?->format('M d, Y') ?? 'Ongoing' }}
                        </span>
                    @endif
                    <span class="m-text-sm m-dimmed">
                        <i class="fas fa-money-bill-wave" style="margin-right: 0.375rem; font-size: var(--m-fz-xs);"></i>TZS {{ number_format($project->contract_value ?? 0, 0) }}
                    </span>
                </div>

                <div class="m-group" style="gap: var(--m-sm);">
                    <span class="m-badge m-badge-gray"><i class="fas fa-list" style="margin-right: 0.25rem;"></i>{{ $project->boqs_count }} BOQs</span>
                    <span class="m-badge m-badge-gray"><i class="fas fa-receipt" style="margin-right: 0.25rem;"></i>{{ $project->invoices_count }} Invoices</span>
                    <span class="m-badge m-badge-gray"><i class="fas fa-clipboard" style="margin-right: 0.25rem;"></i>{{ $project->daily_reports_count }} Reports</span>
                    <a href="{{ route('client.project.show', $project->id) }}" class="m-btn m-btn-light m-btn-sm" style="margin-left: auto;">
                        View Project <i class="fas fa-arrow-right" style="font-size: var(--m-fz-xs);"></i>
                    </a>
                </div>
            </div>
        </div>
    @empty
        <div class="m-paper">
            <div class="m-paper-body" style="text-align: center; padding: calc(var(--m-xl) * 2) var(--m-lg);">
                <i class="fas fa-building" style="font-size: 2.5rem; color: var(--m-gray-4); margin-bottom: var(--m-md);"></i>
                <h3 class="m-title m-title-4" style="margin-bottom: 0.25rem;">No Projects Yet</h3>
                <p class="m-text-sm m-dimmed" style="margin: 0;">You don't have any projects assigned. Contact your project manager for details.</p>
            </div>
        </div>
    @endforelse
@endsection
