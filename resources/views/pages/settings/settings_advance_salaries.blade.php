@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @can('Add Advance Salary')
                        <button type="button" onclick="loadFormModal('settings_advance_salary_form', {className: 'AdvanceSalary'}, 'Create New AdvanceSalary', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10">
                            <i class="si si-plus">&nbsp;</i>New AdvanceSalary</button>
                    @endcan

                </div>
            </div>
            <div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Advanced Salaries</h3>
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
                                <th>Description</th>
                                <th>Amount</th>
                                <th scope="col">Approvals</th>
                                <th scope="col">Status</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $sum = 0;
                            ?>
                            @foreach($advance_salaries as $advance_salary)
                                <?php
                                    $sum += $advance_salary->amount;
                                ?>


                                <tr id="advance_salary-tr-{{$advance_salary->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $advance_salary->date }}</td>
                                    <td class="font-w600">{{ $advance_salary->staff->name  ?? null}}</td>
                                    <td class="font-w600">{{ $advance_salary->description}}</td>
                                    <td class="text-right">{{ number_format($advance_salary->amount) }}
                                    </td>
                                    <td class="text-center">
                                        <!-- Approval status summary component -->
                                        <x-ringlesoft-approval-status-summary :model="$advance_salary" />
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $approvalStatus = $advance_salary->approvalStatus?->status ?? 'PENDING';
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
                                            <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{route('advance_salary',['id' => $advance_salary->id,'document_type_id'=>6])}}"><i class="fa fa-eye"></i></a>
                                        @can('Edit Advance Salary')
                                                <button type="button" onclick="loadFormModal('settings_advance_salary_form', {className: 'AdvanceSalary', id: {{$advance_salary->id}}}, 'Edit {{$advance_salary->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                                @can('Delete Advance Salary')
                                                    <button type="button" onclick="deleteModelItem('AdvanceSalary', {{$advance_salary->id}}, 'advance_salary-tr-{{$advance_salary->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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
