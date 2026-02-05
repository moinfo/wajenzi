@extends('layouts.backend')

@section('content')
    <style>
        .btn-groups {
            opacity: 0;           /* Makes it invisible */
            visibility: hidden;   /* Hides it from view */
            transition: opacity 0.3s ease, visibility 0.3s ease; /* Smooth transition */
        }

        /* Show the .btn-group when hovering over the parent td */
        td:hover .btn-groups {
            opacity: 1;           /* Makes it visible */
            visibility: visible;  /* Shows it */
        }
    </style>
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Beneficiaries
                <div class="float-right">
                    @can('Add Beneficiary')
                        <button type="button"
                                onclick="loadFormModal('beneficiary_form', {className: 'Beneficiary'}, 'Create New Beneficiary', 'modal-md');"
                                class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Beneficiary
                        </button>
                        <button type="button"
                                onclick="loadFormModal('beneficiary_account_form', {className: 'BeneficiaryAccount'}, 'Create New Beneficiary Account', 'modal-md');"
                                class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>Add Beneficiary Account
                        </button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Beneficiaries</h3>
                    </div>
                    <div class="block-content">
                        <div class="table-responsive">
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="table-responsive">
                                        <table
                                            class="table table-bordered table-striped table-vcenter js-dataTable-full"
                                            data-ordering="false">
                                            <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Beneficiary Details</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($beneficiaries as $beneficiary)
                                                <tr>
                                                    <td>{{$loop->iteration}}</td>
                                                    <td style="position: relative;">
                                                        <!-- WhatsApp and Copy to Clipboard Buttons -->
                                                        <div style="position: absolute; top: 0; right: 0;">
                                                            <!-- WhatsApp Share Button -->
                                                            <button type="button" onclick="shareViaWhatsApp('{{$beneficiary->name}}', '@foreach($beneficiary->accounts as $account){{ $account->bank->name }} -  {{ $account->account }}\n @endforeach');" class="btn btn-success btn-sm" title="Share via WhatsApp">
                                                                <i class="fa fa-whatsapp"></i>
                                                            </button>

                                                            <!-- Copy to Clipboard Button -->
                                                            <button type="button" onclick="copyToClipboard('ACC NAME: {{$beneficiary->name}} @foreach($beneficiary->accounts as $account)\n{{ $account->bank->name }} -  {{ $account->account }} @endforeach');" class="btn btn-info btn-sm" title="Copy to Clipboard">
                                                                <i class="fa fa-copy"></i>
                                                            </button>

                                                            @can('Edit Beneficiary')
                                                                <button type="button" onclick="loadFormModal('beneficiary_form', {className: 'Beneficiary', id: {{$beneficiary->id}}}, 'Edit {{$beneficiary->account_name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                                    <i class="fa fa-pencil"></i>
                                                                </button>
                                                            @endcan
                                                            @can('Delete Beneficiary')
                                                                <button type="button" onclick="deleteModelItem('Beneficiary', {{$beneficiary->id}}, 'beneficiary-tr-{{$beneficiary->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                                                    <i class="fa fa-times"></i>
                                                                </button>
                                                            @endcan
                                                        </div>

                                                        <!-- Beneficiary Details -->
                                                        <ul>
                                                            <b>ACC NAME: {{$beneficiary->name}}</b>
                                                            @foreach($beneficiary->accounts as $account)
                                                                <li style="list-style-type: none; margin: 0; padding: 0;">
                                                                    <i>{{ $account->bank->name }}</i> -  {{ $account->account }}

                                                                    <div class="btn-group btn-groups">
                                                                        @can('Edit Beneficiary')
                                                                            <button type="button" onclick="loadFormModal('beneficiary_account_form', {className: 'BeneficiaryAccount', id: {{$account->id}}}, 'Edit {{$account->account_name}}', 'modal-md');" class="btn btn-sm btn-default js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                                                <i class="fa fa-edit"></i>
                                                                            </button>
                                                                        @endcan
                                                                        @can('Delete Beneficiary')
                                                                            <button type="button" onclick="deleteModelItem('BeneficiaryAccount', {{$account->id}}, 'beneficiary-tr-{{$account->id}}');" class="btn btn-sm btn-default js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                                                                <i class="fa fa-minus"></i>
                                                                            </button>
                                                                        @endcan

                                                                    </div>

                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

<script>
    function shareViaWhatsApp(name, accounts) {
        let message = `Beneficiary Name: ${name}\n${accounts}`;
        let url = `https://api.whatsapp.com/send?text=${encodeURIComponent(message)}`;
        window.open(url, '_blank');
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('Copied to clipboard!');
        }, function(err) {
            alert('Failed to copy text.');
        });
    }

</script>
