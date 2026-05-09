@extends('layouts.client')

@section('title', 'Service Design - ' . $project->project_name)

@section('content')
    <div style="margin-bottom: var(--m-md);">
        <a href="{{ route('client.dashboard') }}" class="m-dimmed m-text-xs" style="text-decoration: none;">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
        <h1 class="m-title m-title-3" style="margin-top: var(--m-sm); margin-bottom: 0;">{{ $project->project_name }}</h1>
    </div>

    @include('client.partials.project_tabs')

    @if(session('success'))
        <div class="m-paper mb-3" style="background:#d4edda;border:1px solid #c3e6cb;padding:1rem;">
            <p style="color:#155724;margin:0;"><i class="fas fa-check-circle me-1"></i> {{ session('success') }}</p>
        </div>
    @endif

    @if($design)

    <!-- Approved Service Design Header -->
    <div class="m-paper mb-3" style="border-top: 3px solid #17a2b8;">
        <div class="m-paper-header" style="background: linear-gradient(135deg, #d1ecf1 0%, #f0faff 100%);">
            <h5 style="color:#0c5460;"><i class="fas fa-tools me-2"></i>Approved Service Design</h5>
            <span class="m-badge m-badge-teal">{{ $design->document_number }}</span>
        </div>
        <div class="m-paper-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;">
                <div>
                    <p class="m-text-xs m-dimmed" style="margin:0;">Engineer</p>
                    <p class="m-fw-500" style="margin:0;">{{ $design->assignedEngineer->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="m-text-xs m-dimmed" style="margin:0;">Approved On</p>
                    <p class="m-fw-500" style="margin:0;">{{ $design->approved_at->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="m-text-xs m-dimmed" style="margin:0;">Disciplines Covered</p>
                    <p class="m-fw-500" style="margin:0;">{{ $design->stages->count() }} stages</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Design Stages with Downloadable Files -->
    <div class="m-paper mb-3">
        <div class="m-paper-header">
            <h5><i class="fas fa-layer-group me-2" style="color:var(--m-teal-6,#17a2b8);"></i>Service Documents</h5>
            <span class="m-text-xs m-dimmed">{{ $design->stages->whereNotNull('file_path')->count() }} downloadable file(s)</span>
        </div>
        <div style="padding:0;">
            @php
            $icons = ['Electrical Drawings' => 'bolt', 'Fire Alarm Detection (FADS)' => 'fire-alt', 'ICT Drawings' => 'network-wired', 'HVAC Drawings' => 'wind'];
            @endphp
            @foreach($design->stages as $stage)
            <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--m-gray-2);">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <div style="width:36px;height:36px;border-radius:50%;background:#17a2b8;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-{{ $icons[$stage->name] ?? 'file-alt' }}" style="color:#fff;font-size:0.8rem;"></i>
                        </div>
                        <div>
                            <p class="m-fw-500" style="margin:0;">{{ $stage->name }}</p>
                            @if($stage->completed_at)
                            <p class="m-text-xs m-dimmed" style="margin:0;">
                                Completed {{ $stage->completed_at->format('d M Y') }}
                                @if($stage->completedByUser) by {{ $stage->completedByUser->name }}@endif
                            </p>
                            @endif
                            @if($stage->notes)
                            <p class="m-text-sm m-dimmed" style="margin:0.25rem 0 0;">{{ $stage->notes }}</p>
                            @endif
                        </div>
                    </div>
                    @if($stage->file_path)
                    <a href="{{ Storage::url($stage->file_path) }}"
                       target="_blank"
                       download="{{ $stage->file_name ?? 'service-design-stage-' . $stage->stage_order }}"
                       style="display:flex;align-items:center;gap:0.5rem;padding:0.5rem 1rem;background:#17a2b8;color:#fff;border-radius:var(--m-radius);text-decoration:none;font-size:0.875rem;white-space:nowrap;flex-shrink:0;">
                        <i class="fas fa-download"></i>
                        <span>{{ $stage->file_name ?? 'Download' }}</span>
                    </a>
                    @else
                    <span class="m-text-xs m-dimmed">No file</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Client Feedback / Comments -->
    <div class="m-paper mb-3">
        <div class="m-paper-header">
            <h5><i class="fas fa-comments me-2" style="color:var(--m-blue-6);"></i>Your Comments & Feedback</h5>
        </div>
        <div class="m-paper-body">

            <form method="POST" action="{{ route('client.project.service_design.feedback', $project->id) }}" style="margin-bottom:1.5rem;">
                @csrf
                <div style="margin-bottom:0.75rem;">
                    <label class="m-text-sm m-fw-500" style="display:block;margin-bottom:0.375rem;">Add a comment or review note</label>
                    <textarea name="comment" rows="3"
                              style="width:100%;padding:0.625rem 0.75rem;border:1px solid var(--m-gray-3);border-radius:var(--m-radius);font-size:0.875rem;resize:vertical;"
                              placeholder="Share your thoughts on the service design (electrical, FADS, ICT, HVAC)..."
                              required>{{ old('comment') }}</textarea>
                    @error('comment')
                        <p class="m-text-xs" style="color:var(--m-red-6);margin:0.25rem 0 0;">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                        style="padding:0.5rem 1.25rem;background:var(--m-blue-6);color:#fff;border:none;border-radius:var(--m-radius);font-size:0.875rem;cursor:pointer;">
                    <i class="fas fa-paper-plane me-1"></i> Submit Feedback
                </button>
            </form>

            @if($feedbacks->count() > 0)
            <div>
                <p class="m-text-xs m-dimmed" style="margin:0 0 0.75rem;text-transform:uppercase;letter-spacing:0.05em;">Previous Comments</p>
                @foreach($feedbacks as $fb)
                <div style="padding:0.75rem;background:var(--m-gray-0,#f8f9fa);border-radius:var(--m-radius);margin-bottom:0.5rem;border-left:3px solid #17a2b8;">
                    <p style="margin:0 0 0.25rem;">{{ $fb->comment }}</p>
                    <p class="m-text-xs m-dimmed" style="margin:0;">
                        {{ $fb->client->first_name ?? 'You' }}
                        &middot; {{ $fb->created_at->format('d M Y, H:i') }}
                    </p>
                </div>
                @endforeach
            </div>
            @else
            <p class="m-text-sm m-dimmed" style="margin:0;">No comments yet. Be the first to share your review.</p>
            @endif

        </div>
    </div>

    @else

    <div class="m-paper" style="text-align:center;padding:3rem 2rem;">
        <i class="fas fa-tools" style="font-size:3rem;color:var(--m-gray-3);margin-bottom:1rem;display:block;"></i>
        <h5 style="margin:0 0 0.5rem;">Service Design Not Yet Available</h5>
        <p class="m-text-sm m-dimmed" style="margin:0;">
            The service design (Electrical, FADS, ICT, HVAC) for your project is currently being prepared.<br>
            You will be notified once it has been approved and is ready for review.
        </p>
    </div>

    @endif

@endsection
