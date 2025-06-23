<form method="post" name="staff-form">
        <div class="row">
            <div class="col-12">
                <div class="block-content">
                    <form action="be_forms_premade.html" method="post" onsubmit="return false;">
                        <div class="row">
                            <div class="col-md-7">
                                <div class="form-group row">
                                    <div class="col-6">
                                        <label for="mega-firstname">First Name</label>
                                        <input type="text" class="form-control" id="mega-firstname" name="firstname" placeholder="Enter your firstname..">
                                    </div>
                                    <div class="col-6">
                                        <label for="mega-lastname">Lastname</label>
                                        <input type="text" class="form-control" id="mega-lastname" name="lastname" placeholder="Enter your lastname..">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group row">
                                    <div class="col-12">
                                        <label for="mega-lastname">E-mail Address</label>
                                        <input type="email" class="form-control" id="mega-email" name="email" placeholder="Enter your E-mail">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-7">
                                <div class="form-group row">
                                    <div class="col-12">
                                        <label for="mega-bio">Postal Address</label>
                                        <textarea class="form-control" id="mega-bio" name="address" rows="3" placeholder="Postal Address"></textarea>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-12">
                                        <label for="mega-bio">Type</label>
                                        <select class="form-control" id="mega-type" name="type">
                                            <option value="STAFF">Staff</option>
                                            <option value="EXTERNAL">External User</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-12">
                                        <label for="mega-bio">Marital Status</label>
                                        <select class="form-control" id="mega-marital_status" name="marital_status">
                                            <option value="SINGLE">Single</option>
                                            <option value="MARRIED">Married</option>
                                            <option value="DIVORCED">Divorced</option>
                                            <option value="OTHER">Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group row">
                                    <div class="col-12">
                                        <label for="mega-city">Date of Birth</label>
                                        <input type="text" class="form-control datepicker" id="mega-dob" name="dob" placeholder="Date of Birth">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-12">
                                        <label for="mega-city">TIN</label>
                                        <input type="text" class="form-control" id="mega-tin" name="tin" placeholder="Taxpayer Identification Number">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-12">
                                        <label for="mega-city">National ID</label>
                                        <input type="text" class="form-control form-control-lg" id="mega-national_id" name="national_id" placeholder="National Identification Number">
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-12">
                                        <label for="mega-bio">Designation</label>
                                        <select class="form-control" id="mega-position_id" name="position_id">
                                            @foreach($positions as $position)
                                                <option value="{{$position->id}}">{{$position->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-12">
                                        <label for="mega-bio">Department</label>
                                        <select class="form-control" id="mega-department_id" name="department_id">
                                            @foreach($departments as $department)
                                            <option value="{{$department->id}}">{{$department->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-12">
                                        <label for="mega-bio">Supervisor</label>
                                        <select class="form-control" id="mega-marital_status" name="marital_status">
                                            @foreach($supervisors as $supervisor)
                                                <option value="{{$supervisor->id}}">{{$supervisor->name}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-12">Gender</label>
                                    <div class="col-12">
                                        <label class="css-control css-control-primary css-radio mr-10">
                                            <input type="radio" class="css-control-input" name="mega-gender-group">
                                            <span class="css-control-indicator"></span> Female
                                        </label>
                                        <label class="css-control css-control-primary css-radio">
                                            <input type="radio" class="css-control-input" name="mega-gender-group">
                                            <span class="css-control-indicator"></span> Male
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-alt-primary">
                                    <i class="fa fa-check mr-5"></i> Complete Profile
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-12">
                <button type="button" class="btn btn-alt-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-alt-success" data-dismiss="modal">
                    <i class="fa fa-check"></i> Perfect
                </button>
            </div>
        </div>
</form>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
