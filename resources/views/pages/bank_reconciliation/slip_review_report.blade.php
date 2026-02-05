@extends('layouts.backend')

@section('content')

    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Bank Reconciliation
                <div class="float-right">

                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Slip Review</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-4">
                                                <div class="input-group mb-3">
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">

                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">

                                                </div>

                                            </div>

                                            <div class="class col-md-1">
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
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full"  data-ordering="false">
                                            <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Date</th>
                                                <th>EFD Name</th>
                                                <th>Supplier Name</th>
                                                <th>Beneficiary Name</th>
                                                <th>Beneficiary Account</th>
                                                <th>Wakala</th>
                                                <th>Reference</th>
                                                <th>Payment Type</th>
                                                <th>Payment Mode</th>
                                                <th>Supplier Means</th>
                                                <th>Description</th>
                                                <th>Debit</th>
                                                <th>Credit</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($bank_reconciliations as $bank_reconciliation)
                                                <tr>
                                                    <td>{{$loop->iteration}}</td>
                                                    <td>{{$bank_reconciliation->date}}</td>
                                                    <td>{{$bank_reconciliation->efd}}</td>
                                                    <td>{{$bank_reconciliation->supplier}}</td>
                                                    <td>{{$bank_reconciliation->beneficiary}}</td>
                                                    <td>{{$bank_reconciliation->account_number}}</td>
                                                    <td>{{$bank_reconciliation->wakala}}</td>
                                                    <td>{{$bank_reconciliation->reference}}</td>
                                                    <td>{{$bank_reconciliation->bank}}</td>
                                                    <td>{{$bank_reconciliation->type}}</td>
                                                    <td>{{$bank_reconciliation->payment_type}}</td>
                                                    <td>{{$bank_reconciliation->description}}</td>
                                                    <td class="text-right">{{ number_format($bank_reconciliation->debit, 2) }}</td>
                                                    <td class="text-right">{{ number_format($bank_reconciliation->credit, 2) }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>



                                        </table>
                                    </div>
                                </div>
                                <div class="col-sm-2"></div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection


