@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Gross Profit
                <div class="float-right">
                    <button type="button" onclick="loadFormModal('gross_form', {className: 'Gross'}, 'Create New Gross Profit', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Gross Profit</button>
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Grosses Profit</h3>
                    </div>
                    <div class="block-content">
                        <p>This is a list containing all grosses Profit</p>
                        <div class="table-responsive">
                            <table class="table table-striped table-vcenter">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Date</th>
                                    <th>Supervisor Name</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($grosses as $gross)
                                    <tr id="gross-tr-{{$gross->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td>{{ $gross->date }}</td>
                                        <td>{{ $gross->supervisor->name }}</td>
                                        <td class="font-w600">{{ $gross->description }}</td>
                                        <td class="text-right">{{ number_format($gross->amount, 2) }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button"
                                                        onclick="loadFormModal('gross_form', {className: 'Gross', id: {{$gross->id}}}, 'Edit {{$gross->name}}', 'modal-md');"
                                                        class="btn btn-sm btn-primary js-tooltip-enabled"
                                                        data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <button type="button"
                                                        onclick="deleteModelItem('Gross', {{$gross->id}}, 'gross-tr-{{$gross->id}}');"
                                                        class="btn btn-sm btn-danger js-tooltip-enabled"
                                                        data-toggle="tooltip" title="Delete"
                                                        data-original-title="Delete">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection


