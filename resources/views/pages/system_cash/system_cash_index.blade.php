@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">System Cash
                <div class="float-right">
                    @can('Add System Cash')
                        <button type="button" onclick="loadFormModal('system_cash_form', {className: 'SystemCash'}, 'Create New System Cash', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New System Cash</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-gd-lake">
                        <h3 class="block-title">All System Cashs</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="system_cash_search" action="" id="filter-form" method="post" autocomplete="off">
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
                                    <th>System Name</th>
                                    <th>Amount</th>
                                    <th scope="col">Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                use Illuminate\Support\Facades\DB;
                                $start_date = $_POST['start_date'] ?? date('Y-m-01');
                                $end_date = $_POST['end_date'] ?? date('Y-m-d');
                                $system_cashs = \App\Models\SystemCash::whereBetween('date', [$start_date, $end_date])->select([DB::raw("*")])->get();

                                $sum = 0;
                                ?>
                                @foreach($system_cashs as $system_cash)
                                    <?php
                                    $sum += $system_cash->amount;
                                    ?>
                                    <tr id="system_cash-tr-{{$system_cash->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td>{{ $system_cash->date }}</td>
                                        <td>{{ $system_cash->system->name}}</td>
                                        <td class="text-right">{{ number_format($system_cash->amount, 2) }}</td>
                                        <td>
                                            @if($system_cash->status == 'PENDING')
                                                <div class="badge badge-warning">{{ $system_cash->status}}</div>
                                            @elseif($system_cash->status == 'APPROVED')
                                                <div class="badge badge-primary">{{ $system_cash->status}}</div>
                                            @elseif($system_cash->status == 'REJECTED')
                                                <div class="badge badge-danger">{{ $system_cash->status}}</div>
                                            @elseif($system_cash->status == 'PAID')
                                                <div class="badge badge-primary">{{ $system_cash->status}}</div>
                                            @elseif($system_cash->status == 'COMPLETED')
                                                <div class="badge badge-success">{{ $system_cash->status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $system_cash->status}}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{route('system_cashes',['id' => $system_cash->id,'document_type_id'=>14])}}"><i class="fa fa-eye"></i></a>
                                            @can('Edit System Cash')
                                                    <button type="button"
                                                            onclick="loadFormModal('system_cash_form', {className: 'SystemCash', id: {{$system_cash->id}}}, 'Edit {{ $system_cash->system->name}}', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan

                                                    @can('Delete System Cash')
                                                        <button type="button"
                                                                onclick="deleteModelItem('SystemCash', {{$system_cash->id}}, 'system_cash-tr-{{$system_cash->id}}');"
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
                                    <td class="text-right text-dark" colspan="4"><b>{{number_format($sum,2)}}</b></td>
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


