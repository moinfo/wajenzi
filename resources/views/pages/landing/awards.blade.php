{{-- Landing CMS — Awards management --}}
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">
                Website Content &mdash; Awards
                <small>Awards shown on the mobile app</small>
                <div class="float-right">
                    <button type="button"
                            onclick="loadFormModal('landing_award_form', {className: 'LandingAward'}, 'New Award', 'modal-lg');"
                            class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                        <i class="si si-plus">&nbsp;</i>New Award
                    </button>
                </div>
            </div>

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">All Awards ({{ $awards->count() }})</h3>
                </div>
                <div class="block-content">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter">
                            <thead>
                            <tr>
                                <th style="width:90px;">Image</th>
                                <th style="width:70px;">Year</th>
                                <th>Title</th>
                                <th>Organization</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width:120px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($awards as $award)
                                @php
                                    $title = is_array($award->title) ? ($award->title['en'] ?? '—') : $award->title;
                                    $org = is_array($award->organization) ? ($award->organization['en'] ?? '') : $award->organization;
                                @endphp
                                <tr id="la-row-{{ $award->id }}">
                                    <td>
                                        @if($award->image)
                                            <img src="{{ asset(ltrim($award->image, '/')) }}" style="width:70px;height:50px;object-fit:cover;" class="rounded">
                                        @else
                                            <span class="text-muted"><i class="fa fa-trophy"></i></span>
                                        @endif
                                    </td>
                                    <td class="font-w600">{{ $award->year }}</td>
                                    <td class="font-w600">{{ $title }}</td>
                                    <td class="text-muted">{{ $org }}</td>
                                    <td class="text-center">
                                        @if($award->is_published)
                                            <span class="badge badge-success">Published</span>
                                        @else
                                            <span class="badge badge-secondary">Hidden</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button type="button"
                                                    onclick="loadFormModal('landing_award_form', {className: 'LandingAward', id: {{ $award->id }}}, 'Edit Award', 'modal-lg');"
                                                    class="btn btn-sm btn-primary"><i class="fa fa-pencil"></i></button>
                                            <button type="button"
                                                    onclick="deleteLandingAward({{ $award->id }})"
                                                    class="btn btn-sm btn-danger"><i class="fa fa-times"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-20">No awards yet. Click "New Award" to add one.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function deleteLandingAward(id) {
            Utility.swalConfirm('Delete this award?', 'Delete Award', {type: 'question'}, function (res) {
                if (!res) return;
                var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
                var f = document.createElement('form');
                f.method = 'POST';
                f.action = '{{ url('landing/awards') }}/' + id + '/delete';
                var input = document.createElement('input');
                input.type = 'hidden'; input.name = '_token'; input.value = token;
                f.appendChild(input);
                document.body.appendChild(f);
                f.submit();
            });
        }
    </script>
@endsection
