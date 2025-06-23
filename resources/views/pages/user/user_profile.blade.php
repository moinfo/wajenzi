@extends('layouts.backend')

@section('content')
    <!-- User Info -->

    <!-- Main Content -->
    <div class="content">
        <!-- User Profile -->
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-user-circle mr-5 text-muted"></i> Personal Information
                </h3>
            </div>
            <div class="block-content">
                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row items-push">
                        <div class="col-lg-12">
                            <p class="text-muted">
                                Your personal information and employment details. Some fields may require HR approval to modify.
                            </p>
                        </div>
                        <div class="col-lg-12">
                            <!-- Basic Information -->
                            <h2 class="content-heading pt-0">Basic Information</h2>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="{{ $user->name }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" value="{{ $user->email }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="dob">Date of Birth</label>
                                        <input type="text" class="form-control datepicker" id="dob" name="dob" value="{{ $user->dob }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="gender">Gender</label>
                                        <select class="form-control" id="gender" name="gender">
                                            <option value="MALE" {{ $user->gender === 'MALE' ? 'selected' : '' }}>Male</option>
                                            <option value="FEMALE" {{ $user->gender === 'FEMALE' ? 'selected' : '' }}>Female</option>
                                            <option value="OTHER" {{ $user->gender === 'OTHER' ? 'selected' : '' }}>Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="marital_status">Marital Status</label>
                                        <select class="form-control" id="marital_status" name="marital_status">
                                            <option value="SINGLE" {{ $user->marital_status === 'SINGLE' ? 'selected' : '' }}>Single</option>
                                            <option value="MARRIED" {{ $user->marital_status === 'MARRIED' ? 'selected' : '' }}>Married</option>
                                            <option value="DIVORCED" {{ $user->marital_status === 'DIVORCED' ? 'selected' : '' }}>Divorced</option>
                                            <option value="OTHER" {{ $user->marital_status === 'OTHER' ? 'selected' : '' }}>Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="address">Address</label>
                                        <input type="text" class="form-control" id="address" name="address" value="{{ $user->address }}">
                                    </div>
                                </div>
                            </div>

                            <!-- Employment Information -->
                            <h2 class="content-heading">Employment Information</h2>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="employee_number">Employee Number</label>
                                        <input type="text" class="form-control" id="employee_number" value="{{ $user->employee_number }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="designation">Designation</label>
                                        <input type="text" class="form-control" id="designation" value="{{ $user->designation }}" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="employment_date">Employment Date</label>
                                        <input type="date" class="form-control" id="employment_date" value="{{ $user->employment_date }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="employment_type">Employment Type</label>
                                        <input type="text" class="form-control" id="employment_type" value="{{ $user->employment_type }}" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- Profile Picture -->
                            <h2 class="content-heading">Profile Picture</h2>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="push">
                                        <img class="img-avatar" src="{{ asset($user->profile) }}" alt="">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="push">
                                        <img class="img-" src="{{ asset($user->file) }}" alt="" width="200">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="profile" name="profile" data-toggle="custom-file-input">
                                        <label class="custom-file-label" for="profile">Choose new avatar</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Documents -->
                            <h2 class="content-heading">Documents</h2>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="national_id">National ID</label>
                                        <input type="text" class="form-control" id="national_id" value="{{ $user->national_id }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="tin">TIN Number</label>
                                        <input type="text" class="form-control" id="tin" value="{{ $user->tin }}" readonly>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="id" value="{{ $user->id }}">
                            @can('Update Profile')

                            <div class="form-group row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-alt-primary">Update Profile</button>
                                </div>
                            </div>
                                @endcan
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-asterisk mr-5 text-muted"></i> Change Password
                </h3>
            </div>
            <div class="block-content">
                <form action="{{ route('profile.password.update') }}" method="POST">
                    @csrf
                    <div class="row items-push">
                        <div class="col-lg-3">
                            <p class="text-muted">
                                Changing your password regularly helps keep your account secure.
                            </p>
                        </div>
                        <div class="col-lg-7 offset-lg-1">
                            <div class="form-group row">
                                <div class="col-12">
                                    <label for="current_password">Current Password</label>
                                    <input type="password" class="form-control form-control-lg" id="current_password" name="current_password" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="col-12">
                                    <label for="new_password">New Password</label>
                                    <input type="password" class="form-control form-control-lg" id="new_password" name="password" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="col-12">
                                    <label for="new_password_confirmation">Confirm New Password</label>
                                    <input type="password" class="form-control form-control-lg" id="new_password_confirmation" name="new_password_confirmation" required>
                                </div>
                            </div>
                            <input type="hidden" name="id" value="{{ $user->id }}">
                            @can('Update Password')

                            <div class="form-group row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-alt-primary">Update Password</button>
                                </div>
                            </div>
                                @endcan
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
