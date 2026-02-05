
<div class="block-content">
    <form method="post"  enctype="multipart/form-data"  autocomplete="off">
        @csrf
        <div class="row">
            <input type="hidden" id="recruitment_date" name="recruitment_date" value="<?=date('Y-m-d')?>">
            <input type="hidden" id="password" name="password" value="<?=bcrypt('123456')?>">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-name" class="control-label required">Name</label>
                    <input type="text" class="form-control" id="input-user-name"  required="required" name="name" value="{{ $object->name ?? '' }}" placeholder="User Name">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-email"  class="control-label required">Email</label>
                    <input type="email" class="form-control" id="input-user-email" name="email" value="{{ $object->email ?? '' }}" placeholder="User Email" >
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-gender"  class="control-label required">Gender</label>
                    <select name="gender" id="input-gender" class="form-control"  required="required">
{{--                        <option value="">Select Gender</option>--}}
                        @foreach ($genders as $gender)
                            <option value="{{ $gender['name'] }}" {{ ( $gender['name'] == $object->gender) ? 'selected' : '' }}> {{ $gender['name'] }} </option>
                        @endforeach

                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-address" >Address</label>
                    <input type="text" class="form-control" id="input-user-address" name="address" value="{{ $object->address ?? '' }}" placeholder="User Address">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-designation" >Designation</label>
                    <input type="text" class="form-control" id="input-user-designation" name="designation" value="{{ $object->designation ?? '' }}" placeholder="User Destination">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-employee_number"  class="control-label required">Employee No.</label>
                    <input type="text" class="form-control" id="input-user-employee_number" name="employee_number" value="{{ $object->employee_number ?? '' }}" placeholder="HRM/0001">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-user_device_id">Device ID</label>
                    <input type="number" class="form-control" id="input-user-user_device_id" name="user_device_id" value="{{ $object->user_device_id ?? '' }}" placeholder="Biometric Device ID">
                    <small class="form-text text-muted">ID used by biometric attendance device</small>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-type" class="control-label required">Employee Type</label>
                    <select name="type" id="input-type" class="form-control"  required="required">
{{--                        <option value="">Select Type</option>--}}
                        @foreach ($employee_types as $employee_type)
                            <option value="{{ $employee_type['name'] }}" {{ ( $employee_type['name'] == $object->type) ? 'selected' : '' }}> {{ $employee_type['name'] }} </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-dob">Date of Birth</label>
                    <input type="text" class="form-control datepicker" id="input-user-dob" name="dob" value="{{ $object->dob ?? '' }}">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-dob">Date of Job</label>
                    <input type="text" class="form-control datepicker" id="input-user-doj" name="employment_date" value="{{ $object->employment_date ?? '' }}">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-tin">TIN</label>
                    <input type="number" class="form-control" id="input-user-tin" name="tin" value="{{ $object->tin ?? '' }}">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-national_id">National ID</label>
                    <input type="number" class="form-control" id="input-user-national_id" name="national_id" value="{{ $object->national_id ?? '' }}">
                </div>
            </div>

            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-employment_type">Employment Type</label>
                    <select name="employment_type" id="input-employment_type" class="form-control">
{{--                        <option value="">Select Type</option>--}}
                        @foreach ($employment_types as $employment_type)
                            <option value="{{ $employment_type['name'] }}" {{ ( $employment_type['name'] == $object->employment_type) ? 'selected' : '' }}> {{ $employment_type['name'] }} </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-marital_status">Marital Status</label>
                    <select name="marital_status" id="input-marital_status" class="form-control">
{{--                        <option value="">Select Marital Status</option>--}}
                        @foreach ($marital_status as $status)
                            <option value="{{ $status['name'] }}" {{ ( $status['name'] == $object->marital_status) ? 'selected' : '' }}> {{ $status['name'] }} </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-type"  class="control-label required">Status</label>
                    <select name="status" id="input-status" class="form-control"  required="required">
                        <option value="">Select Status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status['name'] }}" {{ ( $status['name'] == $object->status) ? 'selected' : '' }}> {{ $status['name'] }} </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-department">Department</label>
                    <select name="department_id" id="input-department" class="form-control">
{{--                        <option value="">Select Department</option>--}}
                        @foreach ($departments as $department)
                            <option value="{{ $department['id'] }}" {{ ( $department['id'] == $object->department_id) ? 'selected' : '' }}> {{ $department['name'] }} </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-attendance_type">Attendance Type</label>
                    <select name="attendance_type_id" id="input-attendance_type" class="form-control">
                        <option value="">Select Attendance Type</option>
                        @if(isset($attendance_types))
                            @foreach ($attendance_types as $attendance_type)
                                <option value="{{ $attendance_type['id'] }}" {{ ( $attendance_type['id'] == $object->attendance_type_id) ? 'selected' : '' }}> {{ $attendance_type['name'] }} </option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-attendance_status">Attendance Status</label>
                    <select name="attendance_status" id="input-attendance_status" class="form-control">
                        <option value="">Select Attendance Status</option>
                        <option value="ENABLED" {{ ($object->attendance_status ?? 'ENABLED') == 'ENABLED' ? 'selected' : '' }}>ENABLED</option>
                        <option value="DISABLED" {{ ($object->attendance_status ?? '') == 'DISABLED' ? 'selected' : '' }}>DISABLED</option>
                    </select>
                    <small class="form-text text-muted">Enable/disable attendance tracking for this user</small>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label class="control-label" for="chooseFile">Choose signature</label>
                    <input type="file" name="file" class="form-control" id="chooseFile">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label class="control-label" for="contractFile">Staff Contract (PDF)</label>
                    <input type="file" name="contract" class="form-control" id="contractFile" accept=".pdf">
                    @if($object->contract ?? null)
                        <small class="text-success"><i class="fa fa-check"></i> Contract uploaded: <a href="{{ url($object->contract) }}" target="_blank">View Contract</a></small>
                    @endif
                </div>
            </div>
{{--            <div class="col-sm-6">--}}
{{--                <div class="form-group">--}}
{{--                    <label for="example-nf-password">Password</label>--}}
{{--                    <input type="text" class="form-control" id="input-user-password" name="password" value="123456">--}}
{{--                </div>--}}
{{--            </div>--}}
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="User">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
