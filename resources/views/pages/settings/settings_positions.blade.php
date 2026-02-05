@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    <button type="button" onclick="loadFormModal('settings_position_form', {className: 'Position'}, 'Create New Position', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Position</button>
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Positions</h3>
                    </div>
                    <div class="block-content">

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
