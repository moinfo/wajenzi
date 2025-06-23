@extends('layouts.backend')

@section('content')


    <?php
    use Illuminate\Support\Facades\DB;
    $start_date = $_POST['start_date'] ?? date('Y-m-01');
    $end_date = $_POST['end_date'] ?? date('Y-m-t');
    $payroll_record = new \App\Models\PayrollRecord();
    $payroll_records = $payroll_record->getCurrentPayroll($start_date, $end_date);
    $is_current_payroll_paid = \App\Models\Payroll::isCurrentPayrollPaid($start_date, $end_date);
    ?>
    <?php
    $document_id = \App\Classes\Utility::getLastId('Payroll')+1;

    ?>
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Payroll
                @php

                    $this_year = date('Y');
                    $this_month = date('m');

                @endphp
                <div class="float-right">
                    <div class='btn-group'>
                        <button type='button' class='btn btn-md btn-primary pull-left'
                                onclick="loadFormModal('payroll_form', {className: 'Payroll'}, 'Payroll Preview', 'modal-xl');">
                            <i class='fa fa-plus text-light'>&nbsp;&nbsp;</i>Create New Payroll
                        </button>
                        <button type='button' class=' btn-md btn-rounded btn-primary dropdown-toggle'
                                data-toggle='dropdown'><span class='caret'></span></button>
                        <ul class='dropdown-menu' role='menu'>

                            @php
                                $this_year = date('Y');
                                $this_month = date('m');
                              $done_res = \App\Models\Payroll::getDonePayroll($this_year);
                              $done_months = is_array($done_res) ? array_column($done_res, 'month') : [];
                              $possible_months = array_diff(range(1, $this_month + ($this_month > 11 ? 0 : 1)), $done_months);
                              @endphp
                              @foreach ($possible_months as $index => $possible_month)
                                  @php
                                  $possible_year = $this_year;
                                    @endphp
                            <li>
                                <button type="button"
                                        onclick="loadFormModal('payroll_form', {className: 'Payroll', month: {{$possible_month}}, year: {{$possible_year}}}, 'Create Payroll for {{ date('F',strtotime("01-$possible_month-$possible_year")).' '.$possible_year}}', 'modal-xl');"
                                        class="btn btn-sm btn-default js-tooltip-enabled"
                                        data-toggle="tooltip" title="Create Payroll" data-original-title="Edit">
                                    {{ \App\Classes\Utility::monthNames()[$possible_month] . ' '.$possible_year}}
                                </button>
                            </li>

                            @endforeach

                        </ul>
                    </div>

            </div>
        </div>
        <div>
            <div class="block block-themed">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Payroll</h3>
                </div>
                <div class="block-content">
                    <div class="row no-print m-t-10">
                        <div class="class col-md-12">
                            <div class="class card-box">
                                <form name="gross_search" action="" id="filter-form" method="post" autocomplete="off">
                                    @csrf
                                    <div class="row">
                                        <div class="class col-md-3">
                                            <div class="input-group mb-3">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text" id="basic-addon1">Start Date</span>
                                                </div>
                                                <input type="text" name="start_date" id="start_date"
                                                       class="form-control datepicker-index-form datepicker"
                                                       aria-describedby="basic-addon1" value="{{date('Y-m-01')}}">
                                            </div>
                                        </div>
                                        <div class="class col-md-3">
                                            <div class="input-group mb-3">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text" id="basic-addon2">End Date</span>
                                                </div>
                                                <input type="text" name="end_date" id="end_date"
                                                       class="form-control datepicker-index-form datepicker"
                                                       aria-describedby="basic-addon2" value="{{date('Y-m-t')}}">
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
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full" id="payroll">
                            <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Document Number</th>
                                <th>Payroll Number</th>
                                <th>Payroll Month</th>
                                <th>Payroll Amount</th>
                                <th>Status</th>
                                <th>Approvals</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                                @foreach($payrolls as $payroll)
                                    @php
                                        $payroll_id = $payroll->id;

                                            $total_net = \App\Models\PayrollNetSalary::getTotalNetSalaryByPayroll($payroll_id)
                                    @endphp
                                  <tr  id="gross-tr-{{$payroll->id}}">
                                      <td>{{$loop->iteration}}</td>
                                      <td>{{$payroll->document_number}}</td>
                                      <td>{{$payroll->payroll_number}}</td>
                                      <td>{{ date('F',strtotime("01-$payroll->month-$payroll->year")).' '.$payroll->year}}</td>
                                      <td class="text-right">{{number_format($total_net,2)}}</td>

                                      <td class="text-center">
                                          <!-- Approval status summary component -->
                                          <x-ringlesoft-approval-status-summary :model="$payroll" />
                                      </td>
                                      <td class="text-center">
                                          @php
                                              $approvalStatus = $payroll->approvalStatus?->status ?? 'PENDING';
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
                                      <td width="12%">
                                          <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{route('payroll_view',['id' => $payroll->id,'document_type_id'=>5])}}"><i class="fa fa-eye"></i>View</a>
                                          @if($payroll->status != 'APPROVED')
                                              @can('Delete Payroll')
                                              <button type="button"
                                                      onclick="deleteModelItem('Payroll', {{$payroll->id}}, 'gross-tr-{{$payroll->id}}');"
                                                      class="btn btn-sm btn-danger js-tooltip-enabled"
                                                      data-toggle="tooltip" title="Delete"
                                                      data-original-title="Delete">
                                                  <i class="fa fa-times"></i>
                                              </button>
                                                  @endcan
                                          @endif
                                      </td>

                                  </tr>
                                @endforeach

                            </tbody>
                            <tfoot>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

@endsection


