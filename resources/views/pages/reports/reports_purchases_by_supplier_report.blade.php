@extends('layouts.backend')
@section('css_before')
    <!-- Page JS Plugins CSS -->
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables/dataTables.bootstrap4.css') }}">
@endsection

@section('js_after')


    <script>
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd'
        });
    </script>
@endsection
@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Reports
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-wajenzi-gradient">
                        <h3 class="block-title">Purchases Report</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                @include('components.headed_paper')
                                <br/>
                                <div class="class card-box">
                                    <form  name="collection_search" action="" id="filter-form" method="post" autocomplete="off">
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
                                    <th class="text-center">#</th>
                                    <th>Date</th>
                                    @foreach($suppliers as $supplier)
                                    <th>{{$supplier->name}}</th>
                                    @endforeach
                                    <th>Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php

                                $start_date = $_POST['start_date'] ?? date('Y-m-01');
                                $end_date = $_POST['end_date'] ?? date('Y-m-t');

                                $period = new DatePeriod(new DateTime("$start_date"), new DateInterval('P1D'), new DateTime("$end_date +1 day"));
                                foreach ($period as $date) {
                                $dates[] = $date->format("Y-m-d");
                                }
                                ?>
                                @foreach(array_reverse($dates) as $date)
                                    <?php

                                    ?>
                                    <tr>
                                        <td class="text-center">
                                            {{$loop->iteration}}
                                        </td>

                                        <td class="font-w600">{{ $date }}</td>
                                        @php
                                        $total_purchases_by_date = 0;
                                        @endphp
                                        @foreach($suppliers as $supplier)
                                            @php
                                            $purchases = \App\Models\Purchase::getTotalPurchasesBySupplier($date,$date,$supplier->id);
                                            $total_purchases_by_date += $purchases;
                                            @endphp
                                        <td class="text-right">{{number_format($purchases)}}</td>
                                        @endforeach

                                        <th class="text-right">{{number_format($total_purchases_by_date)}}</th>

                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Date</th>
                                    @foreach($suppliers as $supplier)
                                        <th>{{$supplier->name}}</th>
                                    @endforeach
                                    <th>Total</th>
                                </tr>
                                <tr>
                                    <th></th>
                                    <th></th>
                                    @php
                                        $total_purchases_by_supplier = 0;
                                    @endphp
                                    @foreach($suppliers as $supplier)
                                        @php
                                            $purchases = \App\Models\Purchase::getTotalPurchasesBySupplier(reset($dates),end($dates),$supplier->id);
                                            $total_purchases_by_supplier += $purchases;
                                        @endphp
                                        <td class="text-right">{{number_format($purchases)}}</td>
                                    @endforeach

                                    <th class="text-right">{{number_format($total_purchases_by_supplier)}}</th>

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



