{{-- Landing CMS — Portfolio management --}}
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">
                Website Content &mdash; Portfolio
                <small>Manage the "Our Portfolio" projects shown on the mobile app landing screen</small>
                <div class="float-right">
                    <button type="button"
                            onclick="loadFormModal('landing_project_form', {className: 'LandingProject'}, 'New Portfolio Project', 'modal-lg');"
                            class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                        <i class="si si-plus">&nbsp;</i>New Project
                    </button>
                </div>
            </div>

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">All Portfolio Projects ({{ $projects->count() }})</h3>
                </div>
                <div class="block-content">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter">
                            <thead>
                            <tr>
                                <th style="width:90px;">Cover</th>
                                <th>Title</th>
                                <th>Badge</th>
                                <th class="text-right">Price</th>
                                <th class="text-center">Media</th>
                                <th class="text-center">Likes</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width:120px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($projects as $project)
                                @php
                                    $cover = $project->images->firstWhere('is_primary', true) ?? $project->images->first();
                                    $title = is_array($project->title) ? ($project->title['en'] ?? '—') : $project->title;
                                    $badge = is_array($project->category) ? ($project->category['en'] ?? '') : $project->category;
                                @endphp
                                <tr id="lp-row-{{ $project->id }}">
                                    <td>
                                        @if($cover)
                                            <img src="{{ asset(ltrim($cover->file, '/')) }}" alt="" style="width:70px;height:50px;object-fit:cover;" class="rounded">
                                        @else
                                            <span class="text-muted"><i class="fa fa-image"></i></span>
                                        @endif
                                    </td>
                                    <td class="font-w600">
                                        {{ $title }}
                                        <div class="text-muted">{{ $project->amenities->count() }} amenities · {{ $project->images->count() }} images</div>
                                    </td>
                                    <td>@if($badge)<span class="badge badge-info">{{ $badge }}</span>@endif</td>
                                    <td class="text-right">
                                        @if($project->price_usd)<div>USD {{ number_format($project->price_usd) }}</div>@endif
                                        @if($project->price_tzs)<div class="text-muted">TZS {{ number_format($project->price_tzs) }}</div>@endif
                                    </td>
                                    <td class="text-center">
                                        @if($project->youtube_url)<i class="fa fa-youtube-play text-danger" title="Has video"></i>@endif
                                        @if($project->model_3d_url)<i class="fa fa-cube text-primary ml-5" title="Has 3D"></i>@endif
                                    </td>
                                    <td class="text-center">{{ $project->likes_count }}</td>
                                    <td class="text-center">
                                        @if($project->is_featured)<span class="badge badge-warning">Featured</span>@endif
                                        @if($project->is_published)
                                            <span class="badge badge-success">Published</span>
                                        @else
                                            <span class="badge badge-secondary">Hidden</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button type="button"
                                                    onclick="loadFormModal('landing_project_form', {className: 'LandingProject', id: {{ $project->id }}}, 'Edit Portfolio Project', 'modal-lg');"
                                                    class="btn btn-sm btn-primary"><i class="fa fa-pencil"></i></button>
                                            <button type="button"
                                                    onclick="deleteLandingProject({{ $project->id }})"
                                                    class="btn btn-sm btn-danger"><i class="fa fa-times"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-20">No portfolio projects yet. Click "New Project" to add one.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function deleteLandingProject(id) {
            Utility.swalConfirm('Delete this portfolio project and all its images?', 'Delete Project', {type: 'question'}, function (res) {
                if (!res) return;
                var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
                var f = document.createElement('form');
                f.method = 'POST';
                f.action = '{{ url('landing/portfolio') }}/' + id + '/delete';
                var input = document.createElement('input');
                input.type = 'hidden'; input.name = '_token'; input.value = token;
                f.appendChild(input);
                document.body.appendChild(f);
                f.submit();
            });
        }
    </script>
@endsection
