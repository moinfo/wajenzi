<div class="block-content">
    <form method="post" autocomplete="off" action="{{ route('hr_settings_role_permissions') }}">
        @csrf
        @php
            use Illuminate\Support\Facades\DB;
            use Spatie\Permission\Models\Permission;

            // Check if we have roles in the database first
            $rolesExist = DB::table('roles')->exists();

            // Only try to get roles if they exist
            if ($rolesExist) {
                $allRoles = DB::table('roles')->get();

                // Get the role_id from the request with validation
                $role_id = request()->has('role_id') ? (int)request('role_id') : null;

                // Get the selected role safely
                $selectedRole = $role_id ? DB::table('roles')->where('id', $role_id)->first() : null;

                // Get all permissions
                $permissions = Permission::all();

                // Group permissions by their type
                $permissionsByType = $permissions->groupBy('permission_type');

                // Get the permissions already assigned to this role (if a role is selected)
                $rolePermissionIds = [];
                if ($selectedRole) {
                    $rolePermissionIds = DB::table('role_has_permissions')
                        ->where('role_id', $role_id)
                        ->pluck('permission_id')
                        ->toArray();
                }
            }
        @endphp

        @if(isset($rolesExist) && $rolesExist)
            <div class="row mb-4">
                <div class="col-sm-12">
                    <label class="form-label" for="role-select">Select Role to Manage Permissions</label>
                    <select class="form-control" id="role-select" name="role_id" onchange="this.form.submit()">
                        <option value="">-- Select a role --</option>
                        @foreach($allRoles as $role)
                            <option value="{{ $role->id }}" {{ $role_id == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if($selectedRole)
                <h4 class="mb-3">Manage Permissions for Role: {{ $selectedRole->name }}</h4>

                <div class="row">
                    <div class="col-sm-12">
                        <!-- Permission type tabs -->
                        <ul class="nav nav-tabs mb-3" id="permissionTypeTabs" role="tablist">
                            @foreach($permissionsByType as $type => $typePermissions)
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link {{ $loop->first ? 'active' : '' }}"
                                            id="{{ $type }}-tab"
                                            data-bs-toggle="tab"
                                            data-bs-target="#{{ $type }}"
                                            type="button"
                                            role="tab"
                                            aria-controls="{{ $type }}"
                                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                        {{ $type }} ({{ $typePermissions->count() }})
                                    </button>
                                </li>
                            @endforeach
                        </ul>

                        <!-- Permission type content -->
                        <div class="tab-content" id="permissionTypeContent">
                            @foreach($permissionsByType as $type => $typePermissions)
                                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                     id="{{ $type }}"
                                     role="tabpanel"
                                     aria-labelledby="{{ $type }}-tab">

                                    <div class="card mb-3">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">{{ $type }} Permissions</h5>
                                            <div>
                                                <button type="button" class="btn btn-sm btn-primary select-all" data-type="{{ $type }}">
                                                    Select All
                                                </button>
                                                <button type="button" class="btn btn-sm btn-secondary deselect-all" data-type="{{ $type }}">
                                                    Deselect All
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-unstyled">
                                                @foreach($typePermissions as $permission)
                                                    <li class="mb-2">
                                                        <div class="form-check">
                                                            <input class="form-check-input permission-{{ $type }}"
                                                                   type="checkbox"
                                                                   name="permission_id[]"
                                                                   id="permission-{{ $permission->id }}"
                                                                   value="{{ $permission->id }}"
                                                                {{ in_array($permission->id, $rolePermissionIds) ? 'checked' : '' }}>
                                                            <label class="form-check-label" for="permission-{{ $permission->id }}">
                                                                {{ $permission->name }}
                                                            </label>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <input type="hidden" name="update_permissions" value="1">
                    <button type="submit" class="btn btn-alt-primary" name="submit" value="UpdatePermissions">
                        Update Permissions
                    </button>
                </div>
            @endif
        @else
            <div class="alert alert-warning">
                No roles found in the database. Please create roles first.
                <a href="{{ route('roles.create') }}" class="btn btn-sm btn-warning mt-2">Create Roles</a>
            </div>
        @endif
    </form>
</div>

<script>
    // Datepicker initialization
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });

    // Select/Deselect all permissions by type
    $(document).ready(function() {
        $('.select-all').click(function() {
            var type = $(this).data('type');
            $('.permission-' + type).prop('checked', true);
        });

        $('.deselect-all').click(function() {
            var type = $(this).data('type');
            $('.permission-' + type).prop('checked', false);
        });

        // Add specific CSS to make tabs more clearly interactive
        $('<style>')
            .text(`
            .nav-tabs .nav-link {
                cursor: pointer;
                transition: background-color 0.2s;
            }
            .nav-tabs .nav-link:hover {
                background-color: #f8f9fa;
            }
            /* Ensure tab content is visible */
            .tab-pane.active.show {
                display: block !important;
            }
        `)
            .appendTo('head');

        // Remove any existing click handlers to avoid conflicts
        $('.nav-tabs .nav-link').off('click');

        // Add new click handlers for tabs
        $('.nav-tabs .nav-link').on('click', function(e) {
            e.preventDefault();

            console.log('Tab clicked:', $(this).text().trim());

            // Get the target tab content
            var targetId = $(this).attr('data-bs-target') || $(this).attr('data-target');

            if (!targetId) {
                console.error('No target specified for tab');
                return;
            }

            console.log('Activating tab pane:', targetId);

            // Deactivate all tabs
            $('.nav-tabs .nav-link').removeClass('active').attr('aria-selected', 'false');

            // Activate clicked tab
            $(this).addClass('active').attr('aria-selected', 'true');

            // Hide all tab panes
            $('.tab-content .tab-pane').removeClass('active show');

            // Show the target tab pane (with small delay for transitions)
            $(targetId).addClass('active');
            setTimeout(function() {
                $(targetId).addClass('show');
            }, 50);
        });

        // Make sure the initial tab is active
        var $activeTab = $('.nav-tabs .nav-link.active');
        if ($activeTab.length) {
            var activeTargetId = $activeTab.attr('data-bs-target') || $activeTab.attr('data-target');
            if (activeTargetId) {
                $(activeTargetId).addClass('active show');
            }
        } else {
            // If no tab is active, activate the first one
            $('.nav-tabs .nav-link:first').click();
        }

        // Select/Deselect all permissions by type
        $('.select-all').click(function() {
            var type = $(this).data('type');
            $('.permission-' + type).prop('checked', true);
        });

        $('.deselect-all').click(function() {
            var type = $(this).data('type');
            $('.permission-' + type).prop('checked', false);
        });

        // Initialize datepicker if needed
        if (typeof $.fn.datepicker !== 'undefined') {
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd'
            });
        }

        console.log('jQuery tab initialization complete');
    });


</script>

