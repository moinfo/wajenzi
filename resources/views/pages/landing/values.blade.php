{{-- Landing CMS — Core Values management --}}
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">
                Website Content &mdash; Core Values
                <small>Values shown on the mobile app</small>
                <div class="float-right">
                    <button type="button"
                            onclick="loadFormModal('landing_value_form', {className: 'LandingValue'}, 'New Core Value', 'modal-lg');"
                            class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                        <i class="si si-plus">&nbsp;</i>New Core Value
                    </button>
                </div>
            </div>

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">All Core Values ({{ $values->count() }})</h3>
                </div>
                <div class="block-content">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter">
                            <thead>
                            <tr>
                                <th style="width:60px;">Order</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width:120px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($values as $value)
                                @php
                                    $title = is_array($value->title) ? ($value->title['en'] ?? '—') : $value->title;
                                    $desc = is_array($value->description) ? ($value->description['en'] ?? '') : $value->description;
                                @endphp
                                <tr id="lv-row-{{ $value->id }}">
                                    <td>{{ $value->sort_order }}</td>
                                    <td class="font-w600">{{ $title }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($desc, 120) }}</td>
                                    <td class="text-center">
                                        @if($value->is_published)
                                            <span class="badge badge-success">Published</span>
                                        @else
                                            <span class="badge badge-secondary">Hidden</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button type="button"
                                                    onclick="loadFormModal('landing_value_form', {className: 'LandingValue', id: {{ $value->id }}}, 'Edit Core Value', 'modal-lg');"
                                                    class="btn btn-sm btn-primary"><i class="fa fa-pencil"></i></button>
                                            <button type="button"
                                                    onclick="deleteLandingValue({{ $value->id }})"
                                                    class="btn btn-sm btn-danger"><i class="fa fa-times"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-20">No core values yet. Click "New Core Value" to add one.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function deleteLandingValue(id) {
            Utility.swalConfirm('Delete this core value?', 'Delete Core Value', {type: 'question'}, function (res) {
                if (!res) return;
                var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
                var f = document.createElement('form');
                f.method = 'POST';
                f.action = '{{ url('landing/values') }}/' + id + '/delete';
                var input = document.createElement('input');
                input.type = 'hidden'; input.name = '_token'; input.value = token;
                f.appendChild(input);
                document.body.appendChild(f);
                f.submit();
            });
        }
    </script>
@endsection
