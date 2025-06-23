<div class="block-content">
    <form method="post" autocomplete="off" action="{{ route('hr_settings_role_users') }}">
        @csrf
        @php
            use Illuminate\Support\Facades\DB;

            // Get all users
            $users = DB::table('users')->orderBy('name')->get();

            // Get all roles
            $roles = DB::table('roles')->get();

            // Get the selected role_id from the request
            $role_id = (int)request()->input('role_id', 0);

            // Get the selected role details
            $selectedRole = $role_id ? DB::table('roles')->where('id', $role_id)->first() : null;

            // If a role is selected, get all users who have this role
            $usersWithRole = [];
            if ($selectedRole) {
                $usersWithRole = DB::table('model_has_roles')
                    ->where('role_id', $role_id)
                    ->where('model_type', 'App\\Models\\User')  // Adjust if your user model is different
                    ->pluck('model_id')
                    ->toArray();
            }
        @endphp

        <div class="row mb-4">
            <div class="col-sm-12">
                <label class="form-label" for="role-select">Select Role to Assign to Users</label>
                <select class="form-control" id="role-select" name="role_id" onchange="this.form.submit()">
                    <option value="">-- Select a role --</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ $role_id == $role->id ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($selectedRole)
            <h4 class="mb-3">Assign Users to Role: {{ $selectedRole->name }}</h4>

            <div class="row">
                <div class="col-sm-12">
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th style="width: 10%;">Select</th>
                            <th style="width: 20%;">ID</th>
                            <th style="width: 70%;">User Name</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox"
                                           name="user_id[]"
                                           value="{{ $user->id }}"
                                        {{ in_array($user->id, $usersWithRole) ? 'checked' : '' }}>
                                </td>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="form-group mt-4">
                <input type="hidden" name="update_role_users" value="1">
                <button type="submit" class="btn btn-alt-primary col" name="submit" value="UpdateRoleUsers">
                    Update Users for this Role
                </button>
            </div>
        @endif
    </form>
</div>

<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
