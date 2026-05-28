{{-- Landing CMS — Hero Stats management --}}
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">
                Website Content &mdash; Hero Stats
                <small>The metrics row on the mobile home screen (e.g. 120+ Flagship Projects)</small>
                <div class="float-right">
                    <button type="button"
                            onclick="loadFormModal('landing_stat_form', {className: 'LandingStat'}, 'New Stat', 'modal-md');"
                            class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                        <i class="si si-plus">&nbsp;</i>New Stat
                    </button>
                </div>
            </div>

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">All Stats ({{ $stats->count() }})</h3>
                </div>
                <div class="block-content">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter">
                            <thead>
                            <tr>
                                <th style="width:120px;">Value</th>
                                <th>Label</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width:120px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($stats as $stat)
                                @php $label = is_array($stat->label) ? ($stat->label['en'] ?? '—') : $stat->label; @endphp
                                <tr id="lst-row-{{ $stat->id }}">
                                    <td class="font-size-h4 font-w700 text-primary">{{ $stat->value }}</td>
                                    <td class="font-w600">{{ $label }}</td>
                                    <td class="text-center">
                                        @if($stat->is_published)
                                            <span class="badge badge-success">Published</span>
                                        @else
                                            <span class="badge badge-secondary">Hidden</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button type="button"
                                                    onclick="loadFormModal('landing_stat_form', {className: 'LandingStat', id: {{ $stat->id }}}, 'Edit Stat', 'modal-md');"
                                                    class="btn btn-sm btn-primary"><i class="fa fa-pencil"></i></button>
                                            <button type="button"
                                                    onclick="deleteLandingStat({{ $stat->id }})"
                                                    class="btn btn-sm btn-danger"><i class="fa fa-times"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-20">No stats yet. Click "New Stat" to add one.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function deleteLandingStat(id) {
            Utility.swalConfirm('Delete this stat?', 'Delete Stat', {type: 'question'}, function (res) {
                if (!res) return;
                var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
                var f = document.createElement('form');
                f.method = 'POST';
                f.action = '{{ url('landing/stats') }}/' + id + '/delete';
                var input = document.createElement('input');
                input.type = 'hidden'; input.name = '_token'; input.value = token;
                f.appendChild(input);
                document.body.appendChild(f);
                f.submit();
            });
        }
    </script>
@endsection
