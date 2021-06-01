@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
{{--                    @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Add Staff Loan"))--}}
                        <button type="button" onclick="loadFormModal('loan_form', {className: 'Loan'}, 'Create New Loan', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Staff Loan</button>
{{--                    @endif--}}
                </div>
            </div>
            <div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Staff Loan</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Date</th>
                                <th>Name</th>
                                <th>Deduction</th>
                                <th>Amount</th>
                                <th scope="col">Status</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $sum = 0;
                            ?>
                            @foreach($staff_loans as $loan)
                                <?php
                                    $sum += $loan->amount;
                                ?>
                                <tr id="loan-tr-{{$loan->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $loan->date }}</td>
                                    <td class="font-w600">{{ $loan->staff->name  ?? null}}</td>
                                    <td class="text-right">{{ number_format($loan->deduction)}}</td>
                                    <td class="text-right">{{ number_format($loan->amount) }}
                                    </td>
                                    <td>
                                        @if($loan->status == 'PENDING')
                                            <div class="badge badge-warning">{{ $loan->status}}</div>
                                        @elseif($loan->status == 'APPROVED')
                                            <div class="badge badge-primary">{{ $loan->status}}</div>
                                        @elseif($loan->status == 'REJECTED')
                                            <div class="badge badge-danger">{{ $loan->status}}</div>
                                        @elseif($loan->status == 'PAID')
                                            <div class="badge badge-primary">{{ $loan->status}}</div>
                                        @elseif($loan->status == 'COMPLETED')
                                            <div class="badge badge-success">{{ $loan->status}}</div>
                                        @else
                                            <div class="badge badge-secondary">{{ $loan->status}}</div>
                                        @endif
                                    </td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{route('staff_loan',['id' => $loan->id,'document_type_id'=>2])}}"><i class="fa fa-eye"></i></a>
                                        @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Edit Staff Loan"))
                                                <button type="button" onclick="loadFormModal('loan_form', {className: 'Loan', id: {{$loan->id}}}, 'Edit {{$loan->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endif

                                                @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Delete Staff Loan"))
                                                    <button type="button" onclick="deleteModelItem('Loan', {{$loan->id}}, 'loan-tr-{{$loan->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="text-right">{{number_format($sum)}}</td>
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
@endsection
