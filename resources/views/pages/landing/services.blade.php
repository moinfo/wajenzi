{{-- Landing CMS — Services management --}}
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">
                Website Content &mdash; Services
                <small>Services shown on the mobile app</small>
                <div class="float-right">
                    <button type="button"
                            onclick="loadFormModal('landing_service_form', {className: 'LandingService'}, 'New Service', 'modal-lg');"
                            class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                        <i class="si si-plus">&nbsp;</i>New Service
                    </button>
                </div>
            </div>

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">All Services ({{ $services->count() }})</h3>
                </div>
                <div class="block-content">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter">
                            <thead>
                            <tr>
                                <th style="width:90px;">Image</th>
                                <th>Title</th>
                                <th>Features</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width:120px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($services as $service)
                                @php
                                    $title = is_array($service->title) ? ($service->title['en'] ?? '—') : $service->title;
                                    $features = collect($service->features ?? [])
                                        ->map(fn ($f) => is_array($f) ? ($f['en'] ?? '') : $f)
                                        ->filter();
                                @endphp
                                <tr id="ls-row-{{ $service->id }}">
                                    <td>
                                        @if($service->image)
                                            <img src="{{ asset(ltrim($service->image, '/')) }}" style="width:70px;height:50px;object-fit:cover;" class="rounded">
                                        @else
                                            <span class="text-muted"><i class="fa fa-cogs"></i></span>
                                        @endif
                                    </td>
                                    <td class="font-w600">{{ $title }}</td>
                                    <td>
                                        @foreach($features as $f)
                                            <span class="badge badge-info mb-5">{{ $f }}</span>
                                        @endforeach
                                    </td>
                                    <td class="text-center">
                                        @if($service->is_published)
                                            <span class="badge badge-success">Published</span>
                                        @else
                                            <span class="badge badge-secondary">Hidden</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button type="button"
                                                    onclick="loadFormModal('landing_service_form', {className: 'LandingService', id: {{ $service->id }}}, 'Edit Service', 'modal-lg');"
                                                    class="btn btn-sm btn-primary"><i class="fa fa-pencil"></i></button>
                                            <button type="button"
                                                    onclick="deleteLandingService({{ $service->id }})"
                                                    class="btn btn-sm btn-danger"><i class="fa fa-times"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-20">No services yet. Click "New Service" to add one.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function deleteLandingService(id) {
            Utility.swalConfirm('Delete this service?', 'Delete Service', {type: 'question'}, function (res) {
                if (!res) return;
                var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
                var f = document.createElement('form');
                f.method = 'POST';
                f.action = '{{ url('landing/services') }}/' + id + '/delete';
                var input = document.createElement('input');
                input.type = 'hidden'; input.name = '_token'; input.value = token;
                f.appendChild(input);
                document.body.appendChild(f);
                f.submit();
            });
        }
    </script>
@endsection
