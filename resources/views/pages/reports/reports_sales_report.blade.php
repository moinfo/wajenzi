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
                        <h3 class="block-title">Sales Report</h3>
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
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon3">EFD</span>
                                                    </div>
                                                    <select name="efd_id" id="input-efd-id" class="form-control" aria-describedby="basic-addon3">
                                                        <option value="">All EFD</option>
                                                        @foreach ($efds as $efd)
                                                            <option value="{{ $efd->id }}"> {{ $efd->name }} </option>
                                                        @endforeach
                                                    </select>
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
                                    <th>Attachment</th>
                                    <th>Date</th>
                                    <th>EFD Name</th>
                                    <th>Turnover</th>
                                    <th>NET (A+B+C)</th>
                                    <th>Tax</th>
                                    <th>Turnover (EX + SR)</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $sale = new \App\Models\Sale();
                                $start_date = $_POST['start_date'] ?? date('Y-m-01');
                                $end_date = $_POST['end_date'] ?? date('Y-m-t');
                                $efd_id = $_POST['efd_id'] ?? null;

                                $sales = $sale->getAll($start_date,$end_date,$efd_id,'APPROVED');
                                $total_amount = 0;
                                $total_net = 0;
                                $total_tax = 0;
                                $total_turn_over = 0;
                                ?>
                                @foreach($sales as $sale)
                                    <?php
                                    $amount = $sale->amount;
                                    $total_amount += $amount;
                                    $net = $sale->net;
                                    $total_net += $net;
                                    $tax = $sale->tax;
                                    $total_tax += $tax;
                                    $turn_over = $sale->turn_over;
                                    $total_turn_over += $turn_over;
                                    ?>
                                    <tr id="sale-tr-{{$sale->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td class="text-center">
                                            @if($sale->file != null)
                                                <a href="{{ url("$sale->file") }}">Attachment</a>
                                            @else
                                                No File
                                            @endif
                                        </td>
                                        <td class="font-w600">{{ $sale->date }}</td>
                                        <td class="font-w600">{{ $sale->efd }}</td>
                                        <td class="text-right">{{ number_format($sale->amount, 2) }}</td>
                                        <td class="text-right">{{ number_format($sale->net, 2) }}</td>
                                        <td class="text-right">{{ number_format($sale->tax, 2) }}</td>
                                        <td class="text-right">{{ number_format($sale->turn_over, 2) }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td colspan="4"></td>
                                    <td class="text-right">{{ number_format($total_amount, 2) }}</td>
                                    <td class="text-right">{{ number_format($total_net, 2) }}</td>
                                    <td class="text-right">{{ number_format($total_tax, 2) }}</td>
                                    <td class="text-right">{{ number_format($total_turn_over, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="5"></td>
                                    <td class="text-right" colspan="2">{{ number_format($total_net+$total_tax, 2) }}</td>
                                    <td class="text-right"></td>
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



