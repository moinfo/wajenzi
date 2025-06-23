@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Sales
                <div class="float-right">
                    @can('Add Sales')
                        <button type="button" onclick="loadFormModal('sale_form', {className: 'Sale'}, 'Create New Sales', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Sales</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Sales</h3>
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
                                    <th>EFD Name</th>
                                    <th>Turnover</th>
                                    <th>NET (A+B+C)</th>
                                    <th>Tax</th>
                                    <th>Turnover (EX + SR)</th>
                                    <th>Attachment</th>
                                    <th scope="col">Approvals</th>
                                    <th scope="col">Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $total_amount = 0;
                                $total_net = 0;
                                $total_tax = 0;
                                $total_turn_over = 0;
                                ?>
                                @foreach($sales as $sale)
                                    <?php
                                    $amount = $sale->amount;
                                    $total_amount += $amount;
                                    $net = $sale->net;
                                    $total_net += $net;
                                    $tax = $sale->tax;
                                    $total_tax += $tax;
                                    $turn_over = $sale->turn_over;
                                    $total_turn_over += $turn_over;
                                    ?>

                                    <tr id="sale-tr-{{$sale->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td class="font-w600">{{ $sale->date }}</td>
                                        <td class="font-w600">{{ $sale->efd->name }}</td>
                                        <td class="text-right">{{ number_format($sale->amount, 2) }}</td>
                                        <td class="text-right">{{ number_format($sale->net, 2) }}</td>
                                        <td class="text-right">{{ number_format($sale->tax, 2) }}</td>
                                        <td class="text-right">{{ number_format($sale->turn_over, 2) }}</td>
                                        <td class="text-center">
                                            @if($sale->file != null)
                                                <a href="{{ url("$sale->file") }}">Attachment</a>
                                            @else
                                                No File
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <!-- Approval status summary component -->
                                            <x-ringlesoft-approval-status-summary :model="$sale" />
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $approvalStatus = $sale->approvalStatus?->status ?? 'PENDING';
                                                $statusClass = [
                                                    'Pending' => 'warning',
                                                    'Submitted' => 'info',
                                                    'Approved' => 'success',
                                                    'Rejected' => 'danger',
                                                    'Paid' => 'primary',
                                                    'Completed' => 'success',
                                                    'Discarded' => 'danger',
                                                ][$approvalStatus] ?? 'secondary';

                                                $statusIcon = [
                                                    'Pending' => '<i class="fas fa-clock"></i>',
                                                    'Submitted' => '<i class="fas fa-paper-plane"></i>',
                                                    'Approved' => '<i class="fas fa-check"></i>',
                                                    'Rejected' => '<i class="fas fa-times"></i>',
                                                    'Paid' => '<i class="fas fa-money-bill"></i>',
                                                    'Completed' => '<i class="fas fa-check-circle"></i>',
                                                    'Discarded' => '<i class="fas fa-trash"></i>',
                                                ][$approvalStatus] ?? '<i class="fas fa-question-circle"></i>';
                                            @endphp
                                            <span class="badge badge-{{ $statusClass }} badge-pill" style="font-size: 0.9em; padding: 6px 10px;">
                                                {!! $statusIcon !!} {{ $approvalStatus }}
                                            </span>

                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{route('sale',['id' => $sale->id,'document_type_id'=>2])}}"><i class="fa fa-eye"></i></a>
                                            @can('Edit Sales')
                                                    <button type="button"
                                                            onclick="loadFormModal('sale_form', {className: 'Sale', id: {{$sale->id}}}, 'Edit {{$sale->efd}}', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan

                                                    @can('Delete Sales')
                                                        <button type="button"
                                                                onclick="deleteModelItem('Sale', {{$sale->id}}, 'sale-tr-{{$sale->id}}');"
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
                                    <td colspan="3"></td>
                                    <td class="text-right">{{ number_format($total_amount, 2) }}</td>
                                    <td class="text-right">{{ number_format($total_net, 2) }}</td>
                                    <td class="text-right">{{ number_format($total_tax, 2) }}</td>
                                    <td class="text-right">{{ number_format($total_turn_over, 2) }}</td>
                                    <td colspan="4"></td>
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


