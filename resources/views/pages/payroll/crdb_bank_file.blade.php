@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Payroll

                <div class="float-right">
                </div>
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">CRDB Bank File</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form name="gross_search" action="{{ route('crdb_bank_file') }}" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Start Date</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date"
                                                           class="form-control datepicker-index-form datepicker"
                                                           aria-describedby="basic-addon1" value="{{ $start_date ?? date('Y-m-01') }}">
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon2">End Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date"
                                                           class="form-control datepicker-index-form datepicker"
                                                           aria-describedby="basic-addon2" value="{{ $end_date ?? date('Y-m-t') }}">
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
                                    <th class="text-center">ID</th>
                                    <th>Name</th>
                                    <th>Account</th>
                                    <th>Amount</th>
                                    <th>Bank</th>
                                    <th>Branch</th>
                                    <th>Details</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($staffs as $staff)
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$staff->staff->name ?? null}}</td>
                                        <td>{{$staff->account_number}}</td>
                                        <td class="text-right">
                                            @if(isset($payrolls) && !empty($payrolls))
                                                @php
                                                    $netAmount = 0;
                                                    foreach($payrolls as $payroll_id) {
                                                        $netAmount += App\Models\PayrollNetSalary::getStaffNetPaid($staff->staff_id, $payroll_id);
                                                    }
                                                    echo number_format($netAmount);
                                                @endphp
                                            @endif
                                        </td>
                                        <td class="text-right">3</td>
                                        <td class="text-right">3</td>
                                        <td>SALARY</td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                @if(isset($payrolls) && !empty($payrolls))
                                    <tr>
                                        <th colspan="3" class="text-right">Total:</th>
                                        <th class="text-right">
                                            @php
                                                $totalNet = 0;
                                                foreach($payrolls as $payroll_id) {
                                                    $totalNet += App\Models\PayrollNetSalary::getTotalNetSalaryByPayroll($payroll_id);
                                                }
                                                echo number_format($totalNet, 2);
                                            @endphp
                                        </th>
                                        <th colspan="3"></th>
                                    </tr>
                                @endif
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
