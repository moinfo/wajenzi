{{-- Landing CMS — Leadership Team management --}}
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">
                Website Content &mdash; Team
                <small>Leadership team shown on the mobile app</small>
                <div class="float-right">
                    <button type="button"
                            onclick="loadFormModal('landing_team_form', {className: 'LandingTeamMember'}, 'New Team Member', 'modal-lg');"
                            class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                        <i class="si si-plus">&nbsp;</i>New Team Member
                    </button>
                </div>
            </div>

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">All Team Members ({{ $members->count() }})</h3>
                </div>
                <div class="block-content">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter">
                            <thead>
                            <tr>
                                <th style="width:90px;">Photo</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width:120px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($members as $member)
                                @php
                                    $role = is_array($member->role) ? ($member->role['en'] ?? '—') : $member->role;
                                @endphp
                                <tr id="ltm-row-{{ $member->id }}">
                                    <td>
                                        @if($member->image)
                                            <img src="{{ asset(ltrim($member->image, '/')) }}" style="width:60px;height:60px;object-fit:cover;" class="rounded">
                                        @else
                                            <span class="text-muted"><i class="fa fa-user"></i></span>
                                        @endif
                                    </td>
                                    <td class="font-w600">{{ $member->name }}</td>
                                    <td>{{ $role }}</td>
                                    <td class="text-center">
                                        @if($member->is_published)
                                            <span class="badge badge-success">Published</span>
                                        @else
                                            <span class="badge badge-secondary">Hidden</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button type="button"
                                                    onclick="loadFormModal('landing_team_form', {className: 'LandingTeamMember', id: {{ $member->id }}}, 'Edit Team Member', 'modal-lg');"
                                                    class="btn btn-sm btn-primary"><i class="fa fa-pencil"></i></button>
                                            <button type="button"
                                                    onclick="deleteLandingTeamMember({{ $member->id }})"
                                                    class="btn btn-sm btn-danger"><i class="fa fa-times"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-20">No team members yet. Click "New Team Member" to add one.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function deleteLandingTeamMember(id) {
            Utility.swalConfirm('Delete this team member?', 'Delete Team Member', {type: 'question'}, function (res) {
                if (!res) return;
                var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
                var f = document.createElement('form');
                f.method = 'POST';
                f.action = '{{ url('landing/team') }}/' + id + '/delete';
                var input = document.createElement('input');
                input.type = 'hidden'; input.name = '_token'; input.value = token;
                f.appendChild(input);
                document.body.appendChild(f);
                f.submit();
            });
        }
    </script>
@endsection
