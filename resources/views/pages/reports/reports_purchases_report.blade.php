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
                    <div class="block-header bg-gd-dusk">
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
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon3">Suppliers</span>
                                                    </div>
                                                    <select name="supplier_id" id="input-supplier-id" class="form-control" aria-describedby="basic-addon3">
                                                        <option value="">All Suppliers</option>
                                                        @foreach ($suppliers as $supplier)
                                                            <option value="{{ $supplier->id }}"> {{ $supplier->name }} </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon3">Type</span>
                                                    </div>
                                                    <select name="purchase_type" id="input-purchase-id" class="form-control" aria-describedby="basic-addon3">
                                                        <option value="">All Purchase Types</option>
                                                        <option value="1">VAT</option>
                                                        <option value="2">EXEMPT</option>
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
                                    <th>Supplier</th>
                                    <th>VRN</th>
                                    <th>Invoice</th>
                                    <th>Invoice Date</th>
                                    <th>Goods</th>
                                    <th>Total</th>
                                    <th>VAT EXC</th>
                                    <th>VAT</th>
                                    <th>Exempt</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                use Illuminate\Support\Facades\DB;
                                $purchase = new \App\Models\Purchase();
                                $start_date = $_POST['start_date'] ?? date('Y-m-01');
                                $end_date = $_POST['end_date'] ?? date('Y-m-t');
                                $supplier_id = $_POST['supplier_id'] ?? null;
                                $purchase_type = $_POST['purchase_type'] ?? null;

                                $purchases = $purchase->getAll($start_date,$end_date,$supplier_id,$purchase_type);
                                $auto_purchases = \App\Models\AutoPurchase::getAutoPurchases($start_date,$end_date);

                                $total_purchases = 0;
                                $total_vat_exempts = 0;
                                $total_vats = 0;
                                $total_exempt = 0;
                                $no = 1;
                                ?>
                                @foreach($purchases as $purchase)
                                    <?php
                                    $purchases_amount = $purchase->total_amount ?? 0;
                                    $total_purchases += $purchases_amount;
                                    $vat_exempts_amount = $purchase->amount_vat_exc ?? 0;
                                    $total_vat_exempts += $vat_exempts_amount;
                                    $vats_amount = $purchase->vat_amount ?? 0;
                                    $total_vats += $vats_amount;
                                    $exempt = $purchases_amount - $vat_exempts_amount - $vats_amount;
                                    $total_exempt += $exempt;

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
                                        <td class="font-w600">{{ $purchase->tax_invoice  ?? null }}</td>
                                        <td class="font-w600">{{ $purchase->invoice_date  ?? null}}</td>
                                        <td class="font-w600">{{ $purchase->goods ?? null }}</td>
                                        <td class="text-right">{{ number_format(($purchase->total_amount ?? 0), 2) }}</td>
                                        <td class="text-right">{{ number_format($purchase->amount_vat_exc,2) }}</td>
                                        <td class="text-right">{{ number_format($purchase->vat_amount, 2) }}</td>
                                        <td class="text-right">{{ number_format($exempt, 2) }}</td>

                                    </tr>
                                    @php
                                        $no++;
                                    @endphp
                                @endforeach
                                @php
                                    $receipt_total_excl_of_tax = 0;
                                    $receipt_total_excl_of_tax_total = 0;
                                    $receipt_total_tax = 0;
                                    $receipt_total_incl_of_tax = 0;
                                    $exempt_live_total = 0;
                                    $nos = $no ?? 1;
                                @endphp
                                @foreach($auto_purchases as $purchase)
                                    @php
                                        $receipt_id = $purchase->id ?? 0;

                                        $receipt_total_tax += $purchase->receipt_total_tax;
                                        $receipt_total_incl_of_tax += ($purchase->receipt_total_incl_of_tax);
                                        $receipt_items = \App\Models\ReceiptItem::getItems($receipt_id) ?? [];
                                        $items = implode(',',array_column($receipt_items,'description'));
                                        $receipt_time = $purchase->receipt_time;

                                        if($purchase->receipt_total_tax>0){
                                            $receipt_total_excl_of_tax = $purchase->receipt_total_excl_of_tax;
                                            $receipt_total_excl_of_tax_total += $receipt_total_excl_of_tax;
                                        }else{
                                            $receipt_total_excl_of_tax = 0;
                                             $receipt_total_excl_of_tax_total += $receipt_total_excl_of_tax;
                                        }
                                        $exempt_live = $purchase->receipt_total_incl_of_tax - $receipt_total_excl_of_tax - $purchase->receipt_total_tax;
                                        $exempt_live_total += $exempt_live;

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
                                        <td class="text-right">{{number_format($receipt_total_excl_of_tax)}}</td>
                                        <td class="text-right">{{number_format($purchase->receipt_total_tax)}}</td>
                                        <td class="text-right">{{number_format($exempt_live)}}</td>
                                    </tr>

                                    @php
                                        $nos++;
                                    @endphp
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td colspan="8"></td>
                                    <td class="text-right">{{ number_format($total_purchases+$receipt_total_incl_of_tax, 2) }}</td>
                                    <td class="text-right">{{ number_format($receipt_total_excl_of_tax_total+$total_vat_exempts, 2) }}</td>
                                    <td class="text-right">{{ number_format($total_vats+$receipt_total_tax, 2) }}</td>
                                    <td class="text-right">{{ number_format($total_exempt+$exempt_live_total, 2) }}</td>
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



