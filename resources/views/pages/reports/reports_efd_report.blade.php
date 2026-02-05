@extends('layouts.backend')

@section('content')

    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Reports
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-wajenzi-gradient">
                        <h3 class="block-title">Efd Report</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                @include('components.headed_paper')
                                <br/>
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
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Date</th>
                                    @foreach($efds as $efd)
                                    <th>{{$efd->name}}</th>
                                    @endforeach
                                    <th>Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                use Illuminate\Support\Facades\DB;
                                $sale = new \App\Models\Sale();
                                $start_date = $_POST['start_date'] ?? date('Y-m-01');
                                $end_date = $_POST['end_date'] ?? date('Y-m-d');
                                $period = new DatePeriod(new DateTime("$start_date"), new DateInterval('P1D'), new DateTime("$end_date +1 day"));
                                foreach ($period as $date) {
                                    $dates[] = $date->format("Y-m-d");
                                }
                                    ?>
                                @foreach(array_reverse($dates) as $date)
                            <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$date}}</td>
                                    @foreach($efds as $efd)
                                        @php
                                            $efd_id = $efd->id;
                                            $sales = $sale->getTotalTurnover($date,$date,$efd_id);
                                            $key_name = 'efd_id';
                                        @endphp
                                    <td class="text-right">
                                        <a onclick="loadFormModal('efd_per_day_form', {className: 'Sale', date_find:'{{$date}}',  key_name:'{{$key_name}}', id: {{$efd_id}} }, '{{$efd->name}} EFD For {{$date}}', 'modal-lg');"
                                           class=" js-tooltip-enabled"
                                           data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                            {{number_format($sales)}}</a></td>
                                    @endforeach
                                    @php
                                        $efd_id1 = null;
                                        $total_sales = $sale->getTotalTurnover($date,$date,$efd_id1);
                                    @endphp
                                <td class="text-right">{{number_format($total_sales)}}</td>
                            </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Date</th>
                                    @foreach($efds as $efd)
                                        <th>{{$efd->name}}</th>
                                    @endforeach
                                    <th>Total</th>
                                </tr>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        @foreach($efds as $efd)
                                            @php
                                                $efd_id = $efd->id;
                                                $sales = $sale->getTotalTurnover($start_date,$end_date,$efd_id);
                                            @endphp
                                            <td class="text-right">{{number_format($sales)}}</td>
                                        @endforeach
                                        @php
                                            $efd_id1 = null;
                                            $total_sales = $sale->getTotalTurnover($start_date,$end_date,$efd_id1);
                                        @endphp
                                        <td class="text-right">{{number_format($total_sales)}}</td>
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



