@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @can('Add Statutory Payment')
                        <button type="button" onclick="loadFormModal('settings_statutory_payment_form', {className: 'StatutoryPayment'}, 'Create New Statutory Payment', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10">
                            <i class="si si-plus">&nbsp;</i>New Statutory Payment</button> @endcan

                </div>
            </div>
            <div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Statutory Payments</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Date</th>
                                <th scope="col">Control Number</th>
                                <th scope="col">Statutory Payments</th>
                                <th scope="col">Description</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Due Date</th>
                                <th scope="col">Status</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($statutory_payments as $key => $value)
                                <tr id="statutory_payment-tr-{{$value->id}}">
                                    <th>
                                        <a>{{$loop->iteration}}</a>
                                    </th>
                                    <td>{{ $value->updated_at }}</td>
                                    <td>{{ $value->control_number }}</td>
                                    <td>{{ $value->subCategory->name ?? null }}</td>
                                    <td>{{ \Str::limit($value->description, 100) }}</td>
                                    <td>{{ number_format($value->amount) }}</td>
                                    <td>{{ $value->due_date }}</td>
                                    <td>
                                        @if($value->status == 'PENDING')
                                            <div class="badge badge-warning">{{ $value->status}}</div>
                                        @elseif($value->status == 'APPROVED')
                                            <div class="badge badge-primary">{{ $value->status}}</div>
                                        @elseif($value->status == 'REJECTED')
                                            <div class="badge badge-danger">{{ $value->status}}</div>
                                        @elseif($value->status == 'PAID')
                                            <div class="badge badge-primary">{{ $value->status}}</div>
                                        @elseif($value->status == 'COMPLETED')
                                            <div class="badge badge-success">{{ $value->status}}</div>
                                        @else
                                            <div class="badge badge-secondary">{{ $value->status}}</div>
                                        @endif
                                    </td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{route('hr_settings_statutory_payment',['id' => $value->id,'document_type_id'=>1])}}"><i class="fa fa-eye"></i></a>
                                            @can('Edit Statutory Payment')
                                                <button type="button" onclick="loadFormModal('settings_statutory_payment_form', {className: 'StatutoryPayment', id: {{$value->id}}}, 'Edit {{$value->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                            @can('Delete Statutory Payment')
                                                <button type="button" onclick="deleteModelItem('StatutoryPayment', {{$value->id}}, 'statutory_payment-tr-{{$value->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            @endcan

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
@endsection
