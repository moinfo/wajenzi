@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Gross Profit
                <div class="float-right">
                    @can('Add Gross Profit')
                        <button type="button" onclick="loadFormModal('gross_form', {className: 'Gross'}, 'Create New Gross Profit', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Gross Profit</button>
                    @endcan

                </div>
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-gd-lake">
                        <h3 class="block-title">All Grosses Profit</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="gross_search" action="{{route('gross_search')}}" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Start Date</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon2">End Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon3">Supervisor</span>
                                                    </div>
                                                    <select name="supervisor_id" id="input-supervisor-id" class="form-control" aria-describedby="basic-addon3">
                                                        <option value="">All Supervisor</option>
                                                        @foreach ($supervisors as $supervisor)
                                                            <option value="{{ $supervisor->id }}"> {{ $supervisor->name }} </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-2">
                                                <div>
                                                    <button type="submit" name="submit"  class="btn btn-sm btn-primary">Show</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Date</th>
                                    <th>Supervisor Name</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Payment Type</th>
                                    <th>Attachment</th>
                                    <th scope="col">Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $sum = 0;
                                ?>
                                @foreach($grosses as $gross)
                                    <?php
                                    $payment_type = $gross->payment_type_id == '1' ? 'System' : 'Office';

                                    $sum += $gross->amount;
                                    ?>
                                    <tr id="gross-tr-{{$gross->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td>{{ $gross->date }}</td>
                                        <td>{{ $gross->supervisor->name ?? $gross->supervisor_name}}</td>
                                        <td class="font-w600">{{ $gross->description }}</td>
                                        <td class="text-right">{{ number_format($gross->amount, 2) }}</td>
                                        <td>{{$payment_type}}</td>
                                        <td class="text-center">
                                            @if($gross->file != null)
                                                <a href="{{ url("$gross->file") }}">Attachment</a>
                                            @else
                                                No File
                                            @endif
                                        </td>
                                        <td>
                                            @if($gross->status == 'PENDING')
                                                <div class="badge badge-warning">{{ $gross->status}}</div>
                                            @elseif($gross->status == 'APPROVED')
                                                <div class="badge badge-primary">{{ $gross->status}}</div>
                                            @elseif($gross->status == 'REJECTED')
                                                <div class="badge badge-danger">{{ $gross->status}}</div>
                                            @elseif($gross->status == 'PAID')
                                                <div class="badge badge-primary">{{ $gross->status}}</div>
                                            @elseif($gross->status == 'COMPLETED')
                                                <div class="badge badge-success">{{ $gross->status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $gross->status}}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{route('grosses',['id' => $gross->id,'document_type_id'=>11])}}"><i class="fa fa-eye"></i></a>
                                            @can('Edit Gross Profit')
                                                    <button type="button"
                                                            onclick="loadFormModal('gross_form', {className: 'Gross', id: {{$gross->id}}}, 'Edit {{ $gross->supervisor->name ?? $gross->supervisor_name}}', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan

                                                    @can('Delete Gross Profit')
                                                        <button type="button"
                                                                onclick="deleteModelItem('Gross', {{$gross->id}}, 'gross-tr-{{$gross->id}}');"
                                                                class="btn btn-sm btn-danger js-tooltip-enabled"
                                                                data-toggle="tooltip" title="Delete"
                                                                data-original-title="Delete">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    @endcan

                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td class="text-right text-dark" colspan="5"><b>{{number_format($sum,2)}}</b></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection


