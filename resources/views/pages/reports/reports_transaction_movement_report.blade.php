@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">Reports
        </div>
        <div>
            <div class="block block-themed">
                <div class="block-header bg-gd-dusk">
                    <h3 class="block-title">Transaction Movement Report</h3>
                </div>
                <div class="block-content">
                    <div class="row no-print m-t-10">
                        <div class="class col-md-12">
                            <div class="class card-box">
                                <form  name="collection_search" id="filter-form" method="post" autocomplete="off">
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
                                                    <span class="input-group-text" id="basic-addon2">System</span>
                                                </div>
                                                <select name="system" id="system" class="form-control">
                                                    <option value="1">Mainstore</option>
                                                    <option value="0">All</option>
                                                    <option value="2">Whitestar</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="class col-md-1">
                                            <div>
                                                <button type="submit" name="submit" value="0" class="btn btn-sm btn-primary">Show</button>
                                            </div>
                                        </div>

                                        <div class="class col-md-2">
                                            <a href="{{route('transfer_by_only_supplier_reports')}}" class="btn btn-rounded btn-outline-success min-width-125 mb-10"><i class="si si-graph">&nbsp;</i>Transfer Reports</a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <tr>
                            <th class="text-center" colspan="6">TRANSACTION REPORT</th>
                            <th style="display: none"></th>
                            <th style="display: none"></th>
                            <th style="display: none"></th>
                            <th style="display: none"></th>
                            <th style="display: none"></th>
                        </tr>
                        <tr>
                            <th class="text-center" >#</th>
                            <th>System Name</th>
                            <th>Amount</th>
                        </tr>
                        @php
                            $start_date = $_POST['start_date'] ?? date('Y-m-d');
                            $end_date = $_POST['end_date'] ?? date('Y-m-d');
                            $system = $_POST['system'] ?? 1;

                        @endphp
                        @if($system == 0)
                            @php
                                $total_debit = 0;
                            @endphp
                            @foreach($systems as $system)
                                @php
                                    $system_id = $system->id;
                                    $amount = \App\Models\BankReconciliation::getDebitDepositMainStore($start_date,$end_date,$system_id);
                                    $total_debit += $amount;
                                @endphp
                                <tr>
                                    <td class="text-center" >{{$loop->iteration}}</td>
                                    <td>{{$system->name}}</td>
                                    <td class="text-right">{{number_format($amount)}}</td>
                                </tr>
                            @endforeach
                                @php
                                    $white = \App\Models\BankReconciliation::getTotalDepositWhitestar($start_date,$end_date,16) - \App\Models\BankReconciliation::getTotalDepositWhitestarAuto($start_date,$end_date,16);
                                @endphp
                                <tr>
                                    <td class="text-center" >4</td>
                                    <td>WHITESTAR</td>
                                    <td class="text-right">{{number_format($white)}}</td>
                                </tr>
                                <tr>
                                    <th colspan="2" class="text-right">Total</th>
                                    <th style="display: none"></th>
                                    <th class="text-right">{{number_format($total_debit+$white)}}</th>
                                </tr>

                            <tr>
                                <th class="text-center" colspan="6">PAYMENT REPORT</th>
                            </tr>
                            <tr>
                                <th class="text-center" >#</th>
                                <th>Supplier Name</th>
                                <th>Amount</th>
                            </tr>
                        @php
                            $total_payment = 0;
                        @endphp
                            @foreach($all_systems as $system)
                                @php
                                    $system_id = $system->id;
                                    $suppliers = \App\Models\Supplier::where('system_id',$system_id)->get();

                                @endphp
                                @foreach($suppliers as $supplier)
                                    @php
                                        $supplier_id = $supplier->id;
                                            $amount = \App\Models\BankReconciliation::select(\Illuminate\Support\Facades\DB::raw("SUM(debit) as total_debit"))->where('supplier_id',$supplier_id)->whereBetween('date',[$start_date,$end_date])->get()->first()->total_debit;

                                    @endphp
                                @if($amount > 0)
                                    @php
                                        $total_payment += $amount;
                                    @endphp
                                <tr>
                                    <td class="text-center" >{{$loop->iteration}}</td>
                                    <td>{{$supplier->name}}</td>
                                    <td class="text-right">{{number_format($amount)}}</td>
                                </tr>
                                    @endif
                                @endforeach
                            @endforeach
                            <tr>
                                <th class="text-right" colspan="2">Total</th>
                                <th class="text-right">{{number_format($total_payment)}}</th>
                            </tr>
                            <tr>
                                <th class="text-right" colspan="2">Difference</th>
                                <th class="text-right">{{number_format(($total_debit+$white)-$total_payment)}}</th>
                            </tr>



                        @elseif($system == 1)
                            @php
                                $total_debit = 0;
                            @endphp
                            @foreach($systems as $system)
                                @php
                                    $system_id = $system->id;
                                    $amount = \App\Models\BankReconciliation::getDebitDepositMainStore($start_date,$end_date,$system_id);
                                    $total_debit += $amount;
                                @endphp
                                <tr>
                                    <td class="text-center" >{{$loop->iteration}}</td>
                                    <td>{{$system->name}}</td>
                                    <td class="text-right">{{number_format($amount)}}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <th colspan="2" class="text-right">Total</th>
                                <th style="display: none"></th>
                                <th class="text-right">{{number_format($total_debit)}}</th>
                            </tr>
                            <tr>
                                <th class="text-center" colspan="6">PAYMENT REPORT</th>
                            </tr>
                            <tr>
                                <td class="text-center" >#</td>
                                <td>Supplier Name</td>
                                <td>Amount</td>
                            </tr>
                            @php
                                $total_payment = 0;
                            @endphp
                            @foreach($systems as $system)
                                @php
                                    $system_id = $system->id;
                                    $suppliers = \App\Models\Supplier::where('system_id',$system_id)->get();

                                @endphp
                                @foreach($suppliers as $supplier)
                                    @php
                                        $supplier_id = $supplier->id;
                                            $amount = \App\Models\BankReconciliation::select(\Illuminate\Support\Facades\DB::raw("SUM(bank_reconciliations.debit) as total_debit"))->join('suppliers','suppliers.id','=','bank_reconciliations.supplier_id')->where('bank_reconciliations.supplier_id',$supplier_id)->where('suppliers.supplier_depend_on_system','BONGE')->whereBetween('bank_reconciliations.date',[$start_date,$end_date])->get()->first()->total_debit;

                                    @endphp
                                    @if($amount > 0)
                                        @php
                                            $total_payment += $amount;
                                        @endphp
                                        <tr>
                                            <td class="text-center" >{{$loop->iteration}}</td>
                                            <td>{{$supplier->name}}</td>
                                            <td class="text-right">{{number_format($amount)}}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endforeach
                            <tr>
                                <th class="text-right" colspan="2">Total</th>
                                <th class="text-right">{{number_format($total_payment)}}</th>
                            </tr>
                            <tr>
                                <th class="text-right" colspan="2">Difference</th>
                                <th class="text-right">{{number_format($total_debit - $total_payment)}}</th>
                            </tr>




                        @else
                                @php
                                    $white = \App\Models\BankReconciliation::getTotalDepositWhitestar($start_date,$end_date,16) - \App\Models\BankReconciliation::getTotalDepositWhitestarAuto($start_date,$end_date,16);
                                $total_debit = $white;
                                @endphp
                                <tr>
                                    <td class="text-center" >1</td>
                                    <td>WHITESTAR</td>
                                    <td class="text-right">{{number_format($white)}}</td>
                                </tr>
                            <tr>
                                <th colspan="2" class="text-right">Total</th>
                                <th style="display: none"></th>
                                <th class="text-right">{{number_format($total_debit)}}</th>
                            </tr>
                            <tr>
                                <th class="text-center" colspan="6">PAYMENT REPORT</th>
                            </tr>
                            <tr>
                                <td class="text-center" >#</td>
                                <td>Supplier Name</td>
                                <td>Amount</td>
                            </tr>
                            @php
                                $total_payment = 0;
                            @endphp
                            @foreach($few_systems as $system)
                                @php
                                    $system_id = $system->id;
                                    $suppliers = \App\Models\Supplier::where('system_id',$system_id)->get();

                                @endphp
                                @foreach($suppliers as $supplier)
                                    @php
                                        $supplier_id = $supplier->id;
                                            $amount = \App\Models\BankReconciliation::select(\Illuminate\Support\Facades\DB::raw("SUM(debit) as total_debit"))->where('supplier_id',$supplier_id)->whereBetween('date',[$start_date,$end_date])->get()->first()->total_debit;

                                    @endphp
                                    @if($amount > 0)
                                        @php
                                            $total_payment += $amount;
                                        @endphp
                                        <tr>
                                            <td class="text-center" >{{$loop->iteration}}</td>
                                            <td>{{$supplier->name}}</td>
                                            <td class="text-right">{{number_format($amount)}}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endforeach
                            <tr>
                                <th class="text-right" colspan="2">Total</th>
                                <th class="text-right">{{number_format($total_payment)}}</th>
                            </tr>
                            <tr>
                                <th class="text-right" colspan="2">Difference</th>
                                <th class="text-right">{{number_format($total_debit - $total_payment)}}</th>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
