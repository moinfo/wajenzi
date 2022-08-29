@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Reports
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-gd-dusk">
                        <h3 class="block-title">Bank Report</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="expense_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Start Date</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-01')}}">
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
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full"  data-ordering="false">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Sales</th>
                                        <th>Bank</th>
                                        <th>Difference</th>
                                        <th>Increment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php
                                $start_date = $_POST['start_date'] ?? date('Y-m-01');
                                $end_date = $_POST['end_date'] ?? date('Y-m-d');
                                $date_from_began = '2010-01-01';
                                $period = new DatePeriod(new DateTime("$start_date"), new DateInterval('P1D'), new DateTime("$end_date +1 day"));
                                foreach ($period as $date) {
                                    $dates[] = $date->format("Y-m-d");
                                }
                                ?>
                                @foreach(array_reverse($dates) as $date)
                                  @php
                                      $yesterday = date('Y-m-d', strtotime('-1 day', strtotime($date)));
                                        $turnover = \App\Models\Sale::getTotalTurnover($date,$date,null);
                                        $bank_deposit = \App\Models\BankReconciliation::getTotalDepositPerSupplierBank($date,$date);
                                        $difference = $turnover - $bank_deposit;
                                        $all_time_turnover = \App\Models\Sale::getTotalTurnover($date_from_began,$date,null);
                                        $all_time_bank_deposit = \App\Models\BankReconciliation::getTotalDepositPerSupplierBank($date_from_began,$date,null);
                                        $all_time_difference = $all_time_turnover - $all_time_bank_deposit;


                                  @endphp
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$date}}</td>
                                        <td class="text-right">{{number_format($turnover,2)}}</td>
                                        <td class="text-right">{{number_format($bank_deposit,2)}}</td>
                                        <td class="text-right">{{number_format($difference,2)}}</td>
                                        <td class="text-right">{{number_format($all_time_difference,2)}}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection



