@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @can('Add Staff Loan')
                        <button type="button" onclick="loadFormModal('loan_form', {className: 'Loan'}, 'Create New Loan', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Staff Loan</button>
                    @endcan
                </div>
            </div>
            <div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Staff Loan</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
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
                                <th scope="col">Approvals</th>
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
                                    <td class="text-center">
                                        <!-- Approval status summary component -->
                                        <x-ringlesoft-approval-status-summary :model="$loan" />
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $approvalStatus = $loan->approvalStatus?->status ?? 'PENDING';
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
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{route('staff_loan',['id' => $loan->id,'document_type_id'=>7])}}"><i class="fa fa-eye"></i></a>
                                        @can('Edit Staff Loan')
                                                <button type="button" onclick="loadFormModal('loan_form', {className: 'Loan', id: {{$loan->id}}}, 'Edit {{$loan->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                                @can('Delete Staff Loan')
                                                    <button type="button" onclick="deleteModelItem('Loan', {{$loan->id}}, 'loan-tr-{{$loan->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="text-right">{{number_format($sum)}}</td>
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
@endsection
