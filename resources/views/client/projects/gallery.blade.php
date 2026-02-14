@extends('layouts.client')

@section('title', 'Gallery - ' . $project->project_name)

@section('css')
<style>
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: var(--m-md);
    }
    @media (max-width: 992px) { .gallery-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 576px) { .gallery-grid { grid-template-columns: 1fr; } }

    .gallery-card {
        border: 1px solid var(--m-gray-3);
        border-radius: var(--m-radius-md);
        overflow: hidden;
        background: #fff;
        transition: box-shadow 150ms ease;
        cursor: pointer;
    }
    .gallery-card:hover { box-shadow: var(--m-shadow-md); }

    .gallery-thumb {
        width: 100%;
        aspect-ratio: 4/3;
        object-fit: cover;
        display: block;
    }
    .gallery-info { padding: 0.75rem; }

    /* Lightbox modal */
    .lightbox-img {
        max-width: 100%;
        max-height: 80vh;
        display: block;
        margin: 0 auto;
        border-radius: var(--m-radius-sm);
    }

    [data-theme="dark"] .gallery-card { background: var(--m-dark-6); border-color: var(--m-dark-4); }
</style>
@endsection

@section('content')
    <div style="margin-bottom: var(--m-md);">
        <a href="{{ route('client.dashboard') }}" class="m-dimmed m-text-xs" style="text-decoration: none;">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
        <h1 class="m-title m-title-3" style="margin-top: var(--m-sm); margin-bottom: 0;">{{ $project->project_name }}</h1>
    </div>

    @include('client.partials.project_tabs')

    <!-- Filter -->
    @if($phases->count() && $images->count())
        <div style="margin-bottom: var(--m-md); display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            <span class="m-text-sm m-fw-500">Filter by phase:</span>
            <button class="m-btn m-btn-filled m-btn-sm phase-filter active" data-phase="all">All</button>
            @foreach($phases as $phase)
                @if($images->where('construction_phase_id', $phase->id)->count())
                    <button class="m-btn m-btn-light m-btn-sm phase-filter" data-phase="{{ $phase->id }}">{{ $phase->phase_name }}</button>
                @endif
            @endforeach
        </div>
    @endif

    @if($images->count())
        <div class="gallery-grid" id="galleryGrid">
            @foreach($images as $image)
                <div class="gallery-card" data-phase-id="{{ $image->construction_phase_id ?? '' }}"
                     onclick="openLightbox('{{ asset($image->file) }}', '{{ e($image->title ?? $image->file_name ?? 'Progress Image') }}', '{{ $image->taken_at?->format('M d, Y') ?? '' }}', '{{ $image->constructionPhase->phase_name ?? '' }}', '{{ asset($image->file) }}')">
                    <img src="{{ asset($image->file) }}" alt="{{ $image->title ?? 'Progress image' }}" class="gallery-thumb" loading="lazy">
                    <div class="gallery-info">
                        @if($image->title)
                            <div class="m-fw-500 m-text-sm" style="margin-bottom: 0.25rem;">{{ $image->title }}</div>
                        @endif
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.25rem;">
                            @if($image->taken_at)
                                <span class="m-text-xs m-dimmed"><i class="fas fa-calendar me-1"></i>{{ $image->taken_at->format('M d, Y') }}</span>
                            @endif
                            @if($image->constructionPhase)
                                <span class="m-badge m-badge-blue">{{ $image->constructionPhase->phase_name }}</span>
                            @endif
                        </div>
                        @if($image->description)
                            <p class="m-text-xs m-dimmed" style="margin: 0.375rem 0 0; line-height: 1.4;">{{ Str::limit($image->description, 80) }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="m-paper">
            <div class="m-paper-body" style="text-align: center; padding: 3rem;">
                <i class="fas fa-images" style="font-size: 2.5rem; color: var(--m-gray-3); margin-bottom: 0.75rem;"></i>
                <p class="m-text-sm m-dimmed" style="margin: 0;">No progress images uploaded yet.</p>
            </div>
        </div>
    @endif

    <!-- Lightbox Modal -->
    <div class="modal fade" id="lightboxModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="background: transparent; border: none;">
                <div class="modal-body" style="padding: 0; position: relative;">
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"
                            style="position: absolute; top: 0.5rem; right: 0.5rem; z-index: 10;"></button>
                    <img id="lightboxImg" src="" alt="" class="lightbox-img">
                    <div style="text-align: center; margin-top: 0.75rem;">
                        <div id="lightboxTitle" class="m-fw-600" style="color: #fff; font-size: 1rem;"></div>
                        <div style="display: flex; justify-content: center; align-items: center; gap: 0.75rem; margin-top: 0.375rem;">
                            <span id="lightboxDate" class="m-text-sm" style="color: rgba(255,255,255,.7);"></span>
                            <span id="lightboxPhase" class="m-badge m-badge-blue" style="display: none;"></span>
                        </div>
                        <a id="lightboxDownload" href="" download class="m-btn m-btn-filled m-btn-sm" style="margin-top: 0.75rem;">
                            <i class="fas fa-download me-1"></i> Download
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
    function openLightbox(src, title, date, phase, downloadUrl) {
        document.getElementById('lightboxImg').src = src;
        document.getElementById('lightboxTitle').textContent = title;
        document.getElementById('lightboxDate').textContent = date;
        var phaseEl = document.getElementById('lightboxPhase');
        if (phase) {
            phaseEl.textContent = phase;
            phaseEl.style.display = 'inline-flex';
        } else {
            phaseEl.style.display = 'none';
        }
        document.getElementById('lightboxDownload').href = downloadUrl;
        new bootstrap.Modal(document.getElementById('lightboxModal')).show();
    }

    // Phase filter
    document.querySelectorAll('.phase-filter').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var phase = this.dataset.phase;
            document.querySelectorAll('.phase-filter').forEach(function(b) {
                b.classList.remove('m-btn-filled', 'active');
                b.classList.add('m-btn-light');
            });
            this.classList.remove('m-btn-light');
            this.classList.add('m-btn-filled', 'active');

            document.querySelectorAll('.gallery-card').forEach(function(card) {
                if (phase === 'all' || card.dataset.phaseId === phase) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
</script>
@endsection
