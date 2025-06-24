@extends('layouts.backend')

@section('content')
    <!-- Page Content -->
    <div class="content">
        <h2 class="content-heading">Recruitment Requests <small>All</small>
            <div class="float-right">
                <button type="button" onclick="loadFormModal('recruitment_form', null, 'modal-lg');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Recruitment Request</button>
            </div>
        </h2>
        <p>

            <button class="btn btn-alt-primary" onclick="test()">Test</button>
        </p>
    </div>
    <!-- END Page Content -->
@endsection

@section('js_after')
    <script>
        function test(){
            Utility.callClassMethod('User', null, 'getCount', null, function(res) {
                Utility.swal(res, 'Thats the number of users');
            }, function(error) {
                Utility.swal('Could not call class', 'Sorry', 'error');
            });
        }
    </script>
@endsection
