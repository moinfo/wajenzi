@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">Reports
        </div>
        <div>
            <div class="block block-themed">
                <div class="block-header bg-gd-dusk">
                    <h3 class="block-title">Total Credit Supplier Report</h3>
                </div>
                <div class="block-content">
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Supplier Name</th>
                            <th>Credit</th>
                            <th>Debit</th>
                            <th>Balance</th>
                            <th>System Supplier Type</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($suppliers as $supplier)
                            <tr>
                                <td>{{$loop->iteration}}</td>
                                <td>{{$supplier->name}}</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>{{$supplier->supplier_depend_on_system}}</td>
                            </tr>
                        @endforeach

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
