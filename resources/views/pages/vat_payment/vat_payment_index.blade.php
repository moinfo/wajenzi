@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">VAT Payment
                <div class="float-right">
                    <button type="button"
                            onclick="loadFormModal('vat_payment_form', {className: 'VatPayment'}, 'Create New VatPayment', 'modal-md');"
                            class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i
                            class="si si-plus">&nbsp;</i>New VatPayment
                    </button>
                </div>
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Payments</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form name="vat_payment_search" action="" id="filter-form" method="post"
                                          autocomplete="off">
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
                                    <th>Bank Name</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Attachment</th>
                                    <th scope="col">Approvals</th>
                                    <th scope="col">Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php

                                use Illuminate\Support\Facades\DB;

                                $vat_payment = new \App\Models\VatPayment();
                                $start_date = $_POST['start_date'] ?? date('Y-m-01');
                                $end_date = $_POST['end_date'] ?? date('Y-m-t');

//                                $vat_payments = $vat_payment->getAll($start_date,$end_date);
                                $sum = 0;
                                ?>
                                @foreach($vat_payments as $vat_payment)
                                        <?php
                                        $sum += $vat_payment->amount;
                                        ?>


                                    <tr id="vat_payment-tr-{{$vat_payment->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td>{{ $vat_payment->date }}</td>
                                        <td>{{ $vat_payment->bank->name }}</td>
                                        <td class="font-w600">{{ $vat_payment->description }}</td>
                                        <td class="text-right">{{ number_format($vat_payment->amount, 2) }}</td>
                                        <td class="text-center">
                                            @if($vat_payment->file != null)
                                                <a href="{{ url("$vat_payment->file") }}">Attachment</a>
                                            @else
                                                No File
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <!-- Approval status summary component -->
                                            <x-ringlesoft-approval-status-summary :model="$vat_payment" />
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $approvalStatus = $vat_payment->approvalStatus?->status ?? 'PENDING';
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
                                                <a class="btn btn-sm btn-success js-tooltip-enabled"
                                                   href="{{route('individual_vat_payment',['id' => $vat_payment->id,'document_type_id'=>4])}}"><i
                                                        class="fa fa-eye"></i></a>
                                                <button type="button"
                                                        onclick="loadFormModal('vat_payment_form', {className: 'VatPayment', id: {{$vat_payment->id}}}, 'Edit VatPayment', 'modal-md');"
                                                        class="btn btn-sm btn-primary js-tooltip-enabled"
                                                        data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <button type="button"
                                                        onclick="deleteModelItem('VatPayment', {{$vat_payment->id}}, 'vat_payment-tr-{{$vat_payment->id}}');"
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



