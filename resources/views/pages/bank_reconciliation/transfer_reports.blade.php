@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Transfers
                <div class="float-right">
                    <a href="{{route('bank_reconciliation')}}" class="btn btn-rounded btn-outline-secondary min-width-125 mb-10"><i class="si si-arrow-left">&nbsp;</i>Back</a>
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Transfers</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                <div class="class card-box">
                                    <form  name="collection_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
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
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped table-vcenter "  data-ordering="false">
                                        <thead>
                                        <tr>
                                            <th class="text-center" style="width: 100px;">#</th>
                                            <th>Date</th>
                                            <th>REFERENCE</th>
                                            <th>Description</th>
                                            <th>Supplier Name</th>
                                            <th>EFD Name</th>
                                            <th>Payment Type</th>
                                            <th>Credit</th>
                                            <th>Debit</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php

                                        $total_credit = 0;
                                        $total_debit = 0;
                                        $total_tax = 0;
                                        $total_turn_over = 0;
                                        ?>
                                        @foreach($bank_reconciliations as $bank_reconciliation)
                                            <?php
                                                    $credit = $bank_reconciliation->credit;
                                                if($credit > 0){
                                                    $total_credit += $credit;
                                                }
                                            $debit = $bank_reconciliation->debit;
                                            if($debit > 0){
                                            $total_debit += $debit;
                                            }

                                            ?>
                                            <tr id="bank_reconciliation-tr-{{$bank_reconciliation->id}}">
                                                <td class="text-center">
                                                    {{$loop->iteration}}
                                                </td>
                                                <td class="font-w600">{{ $bank_reconciliation->date }}</td>
                                                <td class="font-w600">{{ $bank_reconciliation->reference }}</td>
                                                <td class="font-w600">{{ $bank_reconciliation->description }}</td>
                                                <td class="font-w600">{{ $bank_reconciliation->supplier }}</td>
                                                <td class="font-w600">{{ $bank_reconciliation->efd }}</td>
                                                <td class="font-w600">{{ $bank_reconciliation->payment_type }}</td>
                                                <td class="text-right">{{ number_format($bank_reconciliation->credit, 2) }}</td>
                                                <td class="text-right">{{ number_format($bank_reconciliation->debit, 2) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                        <tfoot>
                                        <tr>
                                            <td colspan="7"></td>
                                            <td class="text-right">{{ number_format($total_credit, 2) }}</td>
                                            <td class="text-right">{{ number_format($total_debit, 2) }}</td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection


