@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Bank Deposit
                <div class="float-right">
                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Add Bank Deposit"))
                        <button type="button" onclick="loadFormModal('bank_deposit_form', {className: 'BankDeposit'}, 'Create New Bank Deposit', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Bank Deposit</button>
                    @endif
                </div>
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-gd-lake">
                        <h3 class="block-title">All Bank Deposits</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="bank_deposit_search" action="" id="filter-form" method="post" autocomplete="off">
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
                                    <th>Bank Name</th>
                                    <th>Amount</th>
                                    <th scope="col">Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                use Illuminate\Support\Facades\DB;
                                $start_date = $_POST['start_date'] ?? date('Y-m-d');
                                $end_date = $_POST['end_date'] ?? date('Y-m-d');
                                $bank_deposits = \App\Models\BankDeposit::whereBetween('date', [$start_date, $end_date])->select([DB::raw("*")])->get();

                                $sum = 0;
                                ?>
                                @foreach($bank_deposits as $bank_deposit)
                                    <?php
                                    $sum += $bank_deposit->amount;
                                    ?>
                                    <tr id="bank_deposit-tr-{{$bank_deposit->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td>{{ $bank_deposit->date }}</td>
                                        <td>{{ $bank_deposit->bank->name}}</td>
                                        <td class="text-right">{{ number_format($bank_deposit->amount, 2) }}</td>
                                        <td>
                                            @if($bank_deposit->status == 'PENDING')
                                                <div class="badge badge-warning">{{ $bank_deposit->status}}</div>
                                            @elseif($bank_deposit->status == 'APPROVED')
                                                <div class="badge badge-primary">{{ $bank_deposit->status}}</div>
                                            @elseif($bank_deposit->status == 'REJECTED')
                                                <div class="badge badge-danger">{{ $bank_deposit->status}}</div>
                                            @elseif($bank_deposit->status == 'PAID')
                                                <div class="badge badge-primary">{{ $bank_deposit->status}}</div>
                                            @elseif($bank_deposit->status == 'COMPLETED')
                                                <div class="badge badge-success">{{ $bank_deposit->status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $bank_deposit->status}}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{route('bank_deposits',['id' => $bank_deposit->id,'document_type_id'=>17])}}"><i class="fa fa-eye"></i></a>
                                            @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Edit Bank Deposit"))
                                                    <button type="button"
                                                            onclick="loadFormModal('bank_deposit_form', {className: 'BankDeposit', id: {{$bank_deposit->id}}}, 'Edit {{ $bank_deposit->bank->name}}', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endif

                                                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Delete Bank Deposit"))
                                                        <button type="button"
                                                                onclick="deleteModelItem('BankDeposit', {{$bank_deposit->id}}, 'bank_deposit-tr-{{$bank_deposit->id}}');"
                                                                class="btn btn-sm btn-danger js-tooltip-enabled"
                                                                data-toggle="tooltip" title="Delete"
                                                                data-original-title="Delete">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    @endif

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


