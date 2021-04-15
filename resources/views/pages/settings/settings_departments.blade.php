@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    <button type="button" onclick="loadFormModal('settings_department_form', {className: 'Department', metadata: {divisions: 'Division'}}, 'Create New Department', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Department</button>
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Departments</h3>
                    </div>
                    <div class="block-content">

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
