@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Collection
                <div class="float-right">

                    @can('Add Collection')
                        <button type="button"
                                onclick="loadFormModal('collection_form', {className: 'Collection'}, 'Create New Collection', 'modal-md');"
                                class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New
                            Collection
                        </button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-gd-lake">
                        <h3 class="block-title">All Collections</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form name="collection_search" action="{{route('collection_search')}}"
                                          id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"
                                                              id="basic-addon1">Start Date</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date"
                                                           class="form-control datepicker-index-form datepicker"
                                                           aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon2">End Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date"
                                                           class="form-control datepicker-index-form datepicker"
                                                           aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"
                                                              id="basic-addon3">Supervisor</span>
                                                    </div>
                                                    <select name="supervisor_id" id="input-supervisor-id"
                                                            class="form-control" aria-describedby="basic-addon3">
                                                        <option value="0">All Supervisor</option>
                                                        @foreach ($supervisors as $supervisor)
                                                            <option
                                                                value="{{ $supervisor->id }}"> {{ $supervisor->name }} </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-2">
                                                <div>
                                                    <button type="submit" name="submit" class="btn btn-sm btn-primary">
                                                        Show
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="js-dataTable-full"
                                   class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Date</th>
                                    <th>Supervisor Name</th>
                                    <th>Bank Name</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Attachment</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sum = 0;
                                    ?>
                                @foreach($collections as $collection)
                                        <?php
                                        $sum += $collection->amount;
                                        ?>
                                    <tr id="collection-tr-{{$collection->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td>{{ $collection->date }}</td>
                                        <td>{{ $collection->supervisor->name ?? $collection->supervisor_name}}</td>
                                        <td>{{ $collection->bank->name ?? $collection->bank_name }}</td>
                                        <td class="font-w600">{{ $collection->description }}</td>
                                        <td class="text-right">{{ number_format($collection->amount, 2) }}</td>
                                        <td class="text-center">
                                            @if($collection->file != null)
                                                <a href="{{ url("$collection->file") }}">Attachment</a>
                                            @else
                                                No File
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                @can('Edit Collection')
                                                    <button type="button"
                                                            onclick="loadFormModal('collection_form', {className: 'Collection', id: {{$collection->id}}}, 'Edit {{$collection->supervisor->name ?? $collection->supervisor_name}} Collection', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit"
                                                            data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete Collection')

                                                    <button type="button"
                                                            onclick="deleteModelItem('Collection', {{$collection->id}}, 'collection-tr-{{$collection->id}}');"
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
                                    <td class="text-right text-dark" colspan="6"><b>{{number_format($sum,2)}}</b></td>
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



