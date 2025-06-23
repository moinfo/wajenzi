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
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">VAT Payments Report</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                @include('components.headed_paper')
                                <br/>
                                <div class="class card-box">
                                    <form  name="vat_payment_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Start Date</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d', strtotime('first day of january this year'))}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon2">End Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d', strtotime('last day of december this year'))}}">
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
                            <table id="js-dataTable-full" class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Attachment</th>
                                    <th>Date</th>
                                    <th>Bank Name</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                use Illuminate\Support\Facades\DB;
                                $vat_payment = new \App\Models\VatPayment();
                                $start_date = $_POST['start_date'] ?? date('Y-m-d', strtotime('first day of january this year'));
                                $end_date = $_POST['end_date'] ?? date('Y-m-d', strtotime('last day of december this year'));

                                $vat_payments = $vat_payment->getAll($start_date,$end_date);
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
                                        <td class="text-center">
                                            @if($vat_payment->file != null)
                                                <a href="{{ url("$vat_payment->file") }}">Attachment</a>
                                            @else
                                                No File
                                            @endif
                                        </td>
                                        <td>{{ $vat_payment->date }}</td>
                                        <td>{{ $vat_payment->bank_name }}</td>
                                        <td class="font-w600">{{ $vat_payment->description }}</td>
                                        <td class="text-right">{{ number_format($vat_payment->amount, 2) }}</td>

                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td class="text-right text-dark" colspan="5"></td>
                                    <td class="text-right text-dark"><b>{{number_format($sum,2)}}</b></td>

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
