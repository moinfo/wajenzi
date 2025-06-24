@extends('layouts.backend')
@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Purchases
                <div class="float-right">
                    {{--                    @can('Add Purchases')--}}
                    {{--                        <button type="button" onclick="loadFormModal('purchase_form', {className: 'Purchase'}, 'Create New Purchase', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Purchase</button>--}}
                    {{--                    @endcan--}}
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Purchases</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form name="supplier_receiving_search" action="" id="filter-form" method="post"
                                          autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"
                                                              id="basic-addon1">Start Date</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date"
                                                           class="form-control datepicker-index-form datepicker"
                                                           aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon2">End Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date"
                                                           class="form-control datepicker-index-form datepicker"
                                                           aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">
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
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>InsertedDate</th>
                                    <th>SupplierName</th>
                                    <th>SupplierVRN</th>
                                    <th>TaxInvoice</th>
                                    <th>InvoiceDate</th>
                                    <th>Goods</th>
                                    <th>AmountVATEXC</th>
                                    <th>VATAmount</th>
                                    <th>TotalAmount</th>
                                    <th>Discount</th>
                                    <th>VerificationCode</th>
                                    <th>Is Expenses</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php
                                    $receipt_total_excl_of_tax = 0;
                                    $receipt_total_tax = 0;
                                    $receipt_total_incl_of_tax = 0;
                                @endphp
                                @foreach($purchases as $purchase)
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
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$purchase->date}}</td>
                                        <td>{{$purchase->company_name}}</td>
                                        <td>{{$purchase->vrn}}</td>
                                        <td>{{$purchase->receipt_number}}</td>
                                        <td>{{$purchase->receipt_date}}</td>
                                        <td class="text-primary"><a
                                                onclick="loadFormModal('receipt_items_form', {className: 'Receipt', id: {{$receipt_id}} }, 'Receipt items for {{$purchase->company_name}}', 'modal-lg');"
                                                class=" js-tooltip-enabled"
                                                data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                {{$items}}
                                            </a>
                                        </td>
                                        <td class="text-right">{{number_format($purchase->receipt_total_excl_of_tax)}}</td>
                                        <td class="text-right">{{number_format($purchase->receipt_total_tax)}}</td>
                                        <td class="text-right">{{number_format($purchase->receipt_total_incl_of_tax)}}</td>
                                        <td class="text-right">{{number_format($purchase->receipt_total_discount ?? 0)}}</td>
                                        <td>
                                            @if($purchase->receipt_verification_code)
                                                @php
                                                    $time = explode(':',$receipt_time);
                                                @endphp
                                                <a href="https://verify.tra.go.tz/{{$purchase->receipt_verification_code}}_{{$time[0]}}{{$time[1]}}{{$time[2]}}">{{$purchase->receipt_verification_code}}</a>
                                            @endif
                                        </td>
                                        <td>{{$purchase->is_expense}}</td>
                                        <td class="text-center">
                                            <div class="btn-group">

                                                @can('Edit Auto Purchase')
                                                    <button type="button"
                                                            onclick="loadFormModal('auto_purchase_form', {className: 'Receipt', id: {{$purchase->id}}}, 'Edit {{$purchase->company_name}} Receipt', 'modal-md');"
                                                            class="btn btn-sm btn-primary js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Edit"
                                                            data-original-title="Edit">
                                                        <i class="fa fa-pencil"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete Auto Purchase')

                                                    <button type="button"
                                                            onclick="deleteModelItem('Receipt', {{$purchase->id}}, 'collection-tr-{{$purchase->id}}');"
                                                            class="btn btn-sm btn-danger js-tooltip-enabled"
                                                            data-toggle="tooltip" title="Delete"
                                                            data-original-title="Delete">
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
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th class="text-right">{{ number_format($receipt_total_excl_of_tax) }}</th>
                                    <th class="text-right">{{ number_format($receipt_total_tax) }}</th>
                                    <th class="text-right">{{number_format($receipt_total_incl_of_tax)}}</th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
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

