<div class="block-content">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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
                    <select class="form-control" id="role-select" name="role_id" onchange="loadRolePermissions()">
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

                <!-- Search Box -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Search Permissions</label>
                            <input type="text" class="form-control" id="permission-search" placeholder="Search permissions...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Filter by Type</label>
                            <select class="form-control" id="type-filter">
                                <option value="">All Types</option>
                                @foreach($permissionsByType as $type => $typePermissions)
                                    <option value="{{ $type }}">{{ $type }} ({{ $typePermissions->count() }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Two Column Layout -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="mb-0 text-success">
                                    <i class="fas fa-check-circle"></i> Assigned Permissions
                                    <span class="badge bg-success ms-2" id="assigned-count">0</span>
                                </h5>
                                <small class="text-muted">Permissions currently assigned to this role</small>
                            </div>
                            <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                                <div id="assigned-permissions" class="permission-list">
                                    <!-- Assigned permissions will be populated here -->
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="button" class="btn btn-sm btn-outline-danger" id="remove-all">
                                    <i class="fas fa-times"></i> Remove All
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-light">
                                <h5 class="mb-0 text-primary">
                                    <i class="fas fa-plus-circle"></i> Available Permissions
                                    <span class="badge bg-primary ms-2" id="available-count">0</span>
                                </h5>
                                <small class="text-muted">Permissions not yet assigned to this role</small>
                            </div>
                            <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                                <div id="available-permissions" class="permission-list">
                                    <!-- Available permissions will be populated here -->
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="button" class="btn btn-sm btn-outline-success" id="add-all-filtered">
                                    <i class="fas fa-plus"></i> Add All Visible
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden checkboxes for form submission -->
                <div id="hidden-permissions" style="display: none;">
                    @foreach($permissions as $permission)
                        <input type="checkbox" 
                               name="permission_id[]" 
                               value="{{ $permission->id }}" 
                               id="hidden-permission-{{ $permission->id }}"
                               {{ in_array($permission->id, $rolePermissionIds) ? 'checked' : '' }}>
                    @endforeach
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
    // Global variables for permissions
    let allPermissions = [];
    let assignedPermissions = [];
    let availablePermissions = [];

    $(document).ready(function() {
        // Initialize permissions data
        @if($selectedRole)
            allPermissions = [
                @foreach($permissions as $permission)
                {
                    id: {{ $permission->id }},
                    name: '{{ $permission->name }}',
                    type: '{{ $permission->permission_type }}',
                    assigned: {{ in_array($permission->id, $rolePermissionIds) ? 'true' : 'false' }}
                },
                @endforeach
            ];

            // Initialize the permission lists
            initializePermissions();
            renderPermissions();

            // Search functionality
            $('#permission-search').on('input', function() {
                renderPermissions();
            });

            // Type filter functionality
            $('#type-filter').on('change', function() {
                renderPermissions();
            });

            // Remove all permissions
            $('#remove-all').click(function() {
                assignedPermissions.forEach(function(permission) {
                    permission.assigned = false;
                    $('#hidden-permission-' + permission.id).prop('checked', false);
                });
                initializePermissions();
                renderPermissions();
            });

            // Add all filtered permissions
            $('#add-all-filtered').click(function() {
                var searchTerm = $('#permission-search').val().toLowerCase();
                var typeFilter = $('#type-filter').val();
                
                availablePermissions.forEach(function(permission) {
                    var matchesSearch = permission.name.toLowerCase().includes(searchTerm);
                    var matchesType = !typeFilter || permission.type === typeFilter;
                    
                    if (matchesSearch && matchesType) {
                        permission.assigned = true;
                        $('#hidden-permission-' + permission.id).prop('checked', true);
                    }
                });
                initializePermissions();
                renderPermissions();
            });
        @endif

        // Initialize datepicker if needed
        if (typeof $.fn.datepicker !== 'undefined') {
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd'
            });
        }

        // Add custom styles
        $('<style>')
            .text(`
            .permission-item {
                padding: 8px 12px;
                margin: 2px 0;
                border: 1px solid #e9ecef;
                border-radius: 4px;
                background: #f8f9fa;
                cursor: pointer;
                transition: all 0.2s;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .permission-item:hover {
                background: #e9ecef;
                border-color: #dee2e6;
            }
            .permission-item .permission-name {
                font-size: 14px;
                font-weight: 500;
            }
            .permission-item .permission-type {
                font-size: 12px;
                color: #6c757d;
                background: #fff;
                padding: 2px 6px;
                border-radius: 3px;
                border: 1px solid #dee2e6;
            }
            .permission-item .btn {
                padding: 2px 8px;
                font-size: 12px;
            }
            .permission-list {
                min-height: 200px;
            }
            .empty-state {
                text-align: center;
                color: #6c757d;
                font-style: italic;
                padding: 40px 20px;
            }
        `)
            .appendTo('head');
    });

    function initializePermissions() {
        assignedPermissions = allPermissions.filter(p => p.assigned);
        availablePermissions = allPermissions.filter(p => !p.assigned);
    }

    function renderPermissions() {
        var searchTerm = $('#permission-search').val().toLowerCase();
        var typeFilter = $('#type-filter').val();

        // Filter assigned permissions
        var filteredAssigned = assignedPermissions.filter(function(permission) {
            var matchesSearch = permission.name.toLowerCase().includes(searchTerm);
            var matchesType = !typeFilter || permission.type === typeFilter;
            return matchesSearch && matchesType;
        });

        // Filter available permissions
        var filteredAvailable = availablePermissions.filter(function(permission) {
            var matchesSearch = permission.name.toLowerCase().includes(searchTerm);
            var matchesType = !typeFilter || permission.type === typeFilter;
            return matchesSearch && matchesType;
        });

        // Render assigned permissions
        var assignedHtml = '';
        if (filteredAssigned.length === 0) {
            assignedHtml = '<div class="empty-state">No assigned permissions match your criteria</div>';
        } else {
            filteredAssigned.forEach(function(permission) {
                assignedHtml += `
                    <div class="permission-item" data-id="${permission.id}">
                        <div>
                            <div class="permission-name">${permission.name}</div>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="permission-type me-2">${permission.type}</span>
                            <button type="button" class="btn btn-outline-danger btn-sm remove-permission" data-id="${permission.id}">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
        }
        $('#assigned-permissions').html(assignedHtml);

        // Render available permissions
        var availableHtml = '';
        if (filteredAvailable.length === 0) {
            availableHtml = '<div class="empty-state">No available permissions match your criteria</div>';
        } else {
            filteredAvailable.forEach(function(permission) {
                availableHtml += `
                    <div class="permission-item" data-id="${permission.id}">
                        <div>
                            <div class="permission-name">${permission.name}</div>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="permission-type me-2">${permission.type}</span>
                            <button type="button" class="btn btn-outline-success btn-sm add-permission" data-id="${permission.id}">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
        }
        $('#available-permissions').html(availableHtml);

        // Update counts
        $('#assigned-count').text(assignedPermissions.length);
        $('#available-count').text(availablePermissions.length);

        // Bind click events
        $('.add-permission').click(function() {
            var permissionId = $(this).data('id');
            addPermission(permissionId);
        });

        $('.remove-permission').click(function() {
            var permissionId = $(this).data('id');
            removePermission(permissionId);
        });
    }

    function addPermission(permissionId) {
        var permission = allPermissions.find(p => p.id == permissionId);
        if (permission) {
            permission.assigned = true;
            $('#hidden-permission-' + permissionId).prop('checked', true);
            initializePermissions();
            renderPermissions();
        }
    }

    function removePermission(permissionId) {
        var permission = allPermissions.find(p => p.id == permissionId);
        if (permission) {
            permission.assigned = false;
            $('#hidden-permission-' + permissionId).prop('checked', false);
            initializePermissions();
            renderPermissions();
        }
    }

    function loadRolePermissions() {
        var roleId = document.getElementById('role-select').value;
        if (roleId) {
            window.location.href = '{{ route("hr_settings_role_permissions") }}?role_id=' + roleId;
        }
    }
</script>

