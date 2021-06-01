@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">Reports
        </div>
        <div>
            <div class="block block-themed">
                <div class="block-header bg-gd-dusk">
                    <h3 class="block-title">Supplier Credit Report</h3>
                </div>
                <div class="block-content">
                    <div class="row no-print m-t-10">
                        <div class="class col-md-12">
                            <div class="class card-box">
                                <form  name="supplier_receiving_search" action="" id="filter-form" method="post" autocomplete="off">
                                    @csrf
                                    <div class="row">
                                        <div class="class col-md-3">
                                            <div class="input-group mb-3">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text" id="basic-addon2">Date</span>
                                                </div>
                                                <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">
                                            </div>
                                        </div>
                                        <div class="class col-md-3">
                                            <div class="input-group mb-3">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text" id="basic-addon2">System</span>
                                                </div>
                                                <select name="system_id" id="input-system-id" class="form-control" required>
                                                    <option value="0">All System</option>
                                                    @foreach ($systems as $system)
                                                        <option value="{{ $system->id }}"> {{ $system->name }} </option>
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
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                        <tr>
                            <th class="text-center" style="width: 100px;">#</th>
                            <th>Supplier Name</th>
                            <th>Receiving</th>
                            <th>Transaction</th>
                            <th class="text-left">Credit Balance</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        use App\Models\Supplier;use App\Models\SupplierReceiving;use Illuminate\Support\Facades\DB;
                        $system_id = $_POST['system_id'] ?? 5;
                        if($system_id == 0){
                            $suppliers = Supplier::all();
                        }else{
                            $suppliers =  Supplier::Where('system_id',$system_id)->select([DB::raw("*")])->get();
                        }
                        $sum = 0;
                        $receiving_total = 0;
                        $transaction_total = 0;
                        ?>
                            @foreach($suppliers as $supplier)
                                <?php
                                    $end_date = $_POST['end_date'] ?? date('Y-m-d');
                                    $supplier_id = $supplier->id;
                                $receiving = \App\Models\SupplierReceiving::getSupplierReceivingAmount($supplier_id,$end_date);
                                $transaction = \App\Models\TransactionMovement::getSupplierTransactionAmount($supplier_id,$end_date);
                                $balance = $receiving - $transaction;
                                $sum += $balance;
                                $receiving_total += $receiving;
                                $transaction_total += $transaction;
                                ?>
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$supplier->name}}</td>
                                <td class="text-right">{{number_format($receiving)}}</td>
                                <td class="text-right">{{number_format($transaction)}}</td>
                                <td class="text-right">{{number_format($balance)}}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td></td>
                                <td></td>
                                <td class="text-right">{{number_format($receiving_total)}}</td>
                                <td class="text-right">{{number_format($transaction_total)}}</td>
                                <td class="text-right">{{number_format($sum)}}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
