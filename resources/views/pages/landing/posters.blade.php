{{-- Landing CMS — Posters (home banners) management --}}
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">
                Website Content &mdash; Home Banners
                <small>Promotional posters shown on the mobile home screen</small>
                <div class="float-right">
                    <button type="button"
                            onclick="loadFormModal('landing_poster_form', {className: 'LandingPoster'}, 'New Poster', 'modal-lg');"
                            class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                        <i class="si si-plus">&nbsp;</i>New Poster
                    </button>
                </div>
            </div>

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">All Posters ({{ $posters->count() }})</h3>
                </div>
                <div class="block-content">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter">
                            <thead>
                            <tr>
                                <th style="width:140px;">Banner</th>
                                <th>Title</th>
                                <th class="text-center">Link</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width:120px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($posters as $poster)
                                @php
                                    $title = is_array($poster->title) ? ($poster->title['en'] ?? '') : $poster->title;
                                    $subtitle = is_array($poster->subtitle) ? ($poster->subtitle['en'] ?? '') : $poster->subtitle;
                                @endphp
                                <tr id="lpo-row-{{ $poster->id }}">
                                    <td>
                                        <img src="{{ asset(ltrim($poster->image, '/')) }}" style="width:130px;height:60px;object-fit:cover;" class="rounded">
                                    </td>
                                    <td class="font-w600">
                                        {{ $title ?: '—' }}
                                        @if($subtitle)<div class="text-muted">{{ $subtitle }}</div>@endif
                                    </td>
                                    <td class="text-center">
                                        @if($poster->youtube_url)<i class="fa fa-youtube-play text-danger" title="Video"></i>@endif
                                        @if($poster->link_url)<i class="fa fa-link text-primary ml-5" title="Link"></i>@endif
                                    </td>
                                    <td class="text-center">
                                        @if($poster->is_published)
                                            <span class="badge badge-success">Published</span>
                                        @else
                                            <span class="badge badge-secondary">Hidden</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button type="button"
                                                    onclick="loadFormModal('landing_poster_form', {className: 'LandingPoster', id: {{ $poster->id }}}, 'Edit Poster', 'modal-lg');"
                                                    class="btn btn-sm btn-primary"><i class="fa fa-pencil"></i></button>
                                            <button type="button"
                                                    onclick="deleteLandingPoster({{ $poster->id }})"
                                                    class="btn btn-sm btn-danger"><i class="fa fa-times"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-20">No posters yet. Click "New Poster" to add a home banner.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function deleteLandingPoster(id) {
            Utility.swalConfirm('Delete this poster?', 'Delete Poster', {type: 'question'}, function (res) {
                if (!res) return;
                var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
                var f = document.createElement('form');
                f.method = 'POST';
                f.action = '{{ url('landing/posters') }}/' + id + '/delete';
                var input = document.createElement('input');
                input.type = 'hidden'; input.name = '_token'; input.value = token;
                f.appendChild(input);
                document.body.appendChild(f);
                f.submit();
            });
        }
    </script>
@endsection
