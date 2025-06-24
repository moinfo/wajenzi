@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Receiving
                <div class="float-right">
                    @can('Add Receiving')
                        <button type="button" onclick="loadFormModal('receiving_form', {className: 'Receiving'}, 'Create New Receiving', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Receiving</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Receiving</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="collection_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">

                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">

                                                </div>

                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon3">EFD</span>
                                                    </div>
                                                    <select name="efd_id" id="input-efd-id" class="form-control" aria-describedby="basic-addon3">
                                                        <option value="">All EFD</option>
                                                        @foreach ($efds as $efd)
                                                            <option value="{{ $efd->id }}"> {{ $efd->name }} </option>
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
                                    <th>Description</th>
                                    <th>EFD Name</th>
                                    <th>Amount</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $start_date = $_POST['start_date'] ?? date('Y-m-d');
                                $end_date = $_POST['end_date'] ?? date('Y-m-d');
                                $efd_id = $_POST['efd_id'] ?? null;
                                $supplier_id = $_POST['supplier_id'] ?? null;

                               // dump($_GET);
                                // dump($_GET);
                                //                                dump($_GET['end_date']);

                                $receivings = \App\Models\Receiving::getAll($start_date,$end_date,$efd_id);
                                $total_credit = 0;
                                ?>
                                @foreach($receivings as $receiving)
                                    <?php
                                    $credit = $receiving->amount;
                                    $total_credit += $credit;


                                    ?>
                                    <tr id="receiving-tr-{{$receiving->id}}">
                                        <td class="text-center">
                                            {{$loop->iteration}}
                                        </td>
                                        <td class="font-w600">{{ $receiving->date }}</td>
                                        <td class="font-w600">{{ $receiving->description }}</td>
                                        <td class="font-w600">{{ $receiving->efd }}</td>
                                        <td class="text-right">{{ number_format($receiving->amount, 2) }}</td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                            @can('Edit Receiving')
                                                    <button type="button"
                                                            onclick="loadFormModal('receiving_form', {className: 'Receiving', id: {{$receiving->id}}}, 'Edit {{$receiving->efd}}', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan

                                                    @can('Delete Receiving')
                                                        <button type="button"
                                                                onclick="deleteModelItem('Receiving', {{$receiving->id}}, 'receiving-tr-{{$receiving->id}}');"
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
                                    <td colspan="4"></td>
                                    <td class="text-right">{{ number_format($total_credit, 2) }}</td>
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


