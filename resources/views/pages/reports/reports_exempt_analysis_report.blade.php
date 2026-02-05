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
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Reports
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Exempt Analysis Report</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                @include('components.headed_paper')
                                <br/>
                                <div class="class card-box">
                                    <form  name="supplier_receiving_search" action="" id="filter-form" method="post" autocomplete="off">
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
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-t')}}">
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
                                    <th>Supplier</th>
                                    <th>VRN</th>
                                    <th>Invoice</th>
                                    <th>Invoice Date</th>
                                    <th>Goods</th>
                                    <th>Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                use Illuminate\Support\Facades\DB;
                                $purchase = new \App\Models\Purchase();
                                $payable = new \App\Models\VatAnalysis();
                                $start_date = $_POST['start_date'] ?? date('Y-m-01');
                                $end_date = $_POST['end_date'] ?? date('Y-m-t');

                                $purchases = $purchase->getAll($start_date,$end_date,null,2);
                                $auto_purchases = \App\Models\AutoPurchase::getAutoPurchasesExempt($start_date,$end_date);
                                $total_net = \App\Models\Sale::getTotalNet($start_date,$end_date);
                                $total_turnover = \App\Models\Sale::getTotalTurnover($start_date,$end_date);
                                $total_tax = \App\Models\Sale::getTotalTax($start_date,$end_date);
                                $total_sales = \App\Models\Sale::getTotalSale($start_date,$end_date);
                                $total_amount_vat_exc = \App\Models\Sale::getTotalSaleVatExcl($start_date,$end_date);
                                $total_vat_amt = \App\Models\Sale::getTotalVatAmt($start_date,$end_date);
                                $total_exempt = \App\Models\Sale::getTotalExempt($start_date,$end_date);
                                $vat_payable = $payable->getTaxPayable($end_date);
                                $total_purchases = 0;
                                $total_vat_exempts = 0;
                                $total_vats = 0;
                                $no = 1;
                                ?>
                                @foreach($purchases as $purchase)
                                    <?php
                                    $purchases_amount = $purchase->total_amount;
                                    $total_purchases += $purchases_amount;
                                    $vat_exempts_amount = $purchase->amount_vat_exc;
                                    $total_vat_exempts += $vat_exempts_amount;
                                    $vats_amount = $purchase->vat_amount;
                                    $total_vats += $vats_amount;
                                    ?>
                                    <tr id="purchase-tr-{{$purchase->id}}">
                                        <td class="text-center">
                                            {{$no}}
                                        </td>
                                        <td class="text-center">
                                            @if($purchase->file != null)
                                                <a href="{{ url("$purchase->file") }}">Attachment</a>
                                            @else
                                                No File
                                            @endif
                                        </td>
                                        <td class="font-w600">{{ $purchase->date }}</td>
                                        <td class="font-w600">{{ $purchase->supplier ?? null }}</td>
                                        <td class="font-w600">{{ $purchase->vrn ?? null}}</td>
                                        <td class="font-w600">{{ $purchase->tax_invoice }}</td>
                                        <td class="font-w600">{{ $purchase->invoice_date }}</td>
                                        <td class="font-w600">{{ $purchase->goods ?? null }}</td>
                                        <td class="text-right">{{ number_format($purchase->total_amount, 2) }}</td>

                                    </tr>
                                    @php
                                        $no++;
                                    @endphp
                                @endforeach

                                @php
                                    $receipt_total_excl_of_tax = 0;
                                    $receipt_total_tax = 0;
                                    $receipt_total_incl_of_tax = 0;
                                    $nos = $no;
                                @endphp
                                @foreach($auto_purchases as $purchase)
                                    @php
                                        $receipt_id = $purchase->id;
                                        $receipt_total_excl_of_tax += $purchase->receipt_total_excl_of_tax;
                                        $receipt_total_tax += $purchase->receipt_total_tax;
                                        $receipt_total_incl_of_tax += ($purchase->receipt_total_incl_of_tax);
                                        $receipt_items = \App\Models\ReceiptItem::getItems($receipt_id);
                                        $items = implode(',',array_column($receipt_items,'description'));
                                        $receipt_time = $purchase->receipt_time;
                                    @endphp
                                    <tr>
                                        <td>{{$nos}}</td>
                                        <td>
                                            @if($purchase->receipt_verification_code)
                                                @php
                                                    $time = explode(':',$receipt_time);
                                                @endphp
                                                <a href="https://verify.tra.go.tz/{{$purchase->receipt_verification_code}}_{{$time[0]}}{{$time[1]}}{{$time[2]}}">{{$purchase->receipt_verification_code}}</a>
                                            @endif
                                        </td>
                                        <td>{{$purchase->date}}</td>
                                        <td>{{$purchase->company_name}}</td>
                                        <td>{{$purchase->vrn}}</td>
                                        <td>{{$purchase->receipt_number}}</td>
                                        <td>{{$purchase->receipt_date}}</td>
                                        <td class="text-primary"><a onclick="loadFormModal('receipt_items_form', {className: 'Receipt', id: {{$receipt_id}} }, 'Receipt items for {{$purchase->company_name}}', 'modal-lg');"
                                                                    class=" js-tooltip-enabled"
                                                                    data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                {{$items}}
                                            </a>
                                        </td>
                                        <td class="text-right">{{number_format($purchase->receipt_total_incl_of_tax)}}</td>
                                    </tr>

                                    @php
                                        $nos++;
                                    @endphp
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td colspan="8" class="text-right">TOTAL PURCHASES</td>
                                    <td class="text-right">{{ number_format($total_purchases+$receipt_total_incl_of_tax, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="8" class="text-right">TOTAL SALES</td>
                                    <td class="text-right"><a href="{{ route('sales', ['start_date'=>$start_date, 'end_date'=>$end_date]) }}">{{ number_format($total_exempt, 2,'.',',') }}</a></td>
                                </tr>
                                <tr>
                                    <td colspan="8" class="text-right"><b>DIFFERENCE</b></td>
                                    <td class="text-right">{{ number_format(($total_exempt-($total_purchases+$receipt_total_incl_of_tax)), 2) }}</td>
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
