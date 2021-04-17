@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Sales
                <div class="float-right">
                    <button type="button" onclick="loadFormModal('sale_form', {className: 'Sale'}, 'Create New Sales', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="si si-plus">&nbsp;</i>New Sales</button>
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Sales</h3>
                    </div>
                    <div class="block-content">
                        <p>This is a list containing all sales</p>
                        <div class="table-responsive">
                            <table class="table table-striped table-vcenter">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 100px;">#</th>
                                    <th>Date</th>
                                    <th>EFD Name</th>
                                    <th>Turnover</th>
                                    <th>NET (A+B+C)</th>
                                    <th>Tax</th>
                                    <th>Turnover (EX + SR)</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($sales as $sale)
                                    <tr id="sale-tr-{{$sale->id}}">
                                        <td class="text-center">
                                            {{$loop->index + 1}}
                                        </td>
                                        <td class="font-w600">{{ $sale->date }}</td>
                                        <td class="font-w600">{{ $sale->efd->name }}</td>
                                        <td class="text-right">{{ number_format($sale->amount, 2) }}</td>
                                        <td class="text-right">{{ number_format($sale->net, 2) }}</td>
                                        <td class="text-right">{{ number_format($sale->tax, 2) }}</td>
                                        <td class="text-right">{{ number_format($sale->turn_over, 2) }}</td>

                                        <td class="text-center">
                                            <div class="btn-group">
                                                <button type="button"
                                                        onclick="loadFormModal('sale_form', {className: 'Sale', id: {{$sale->id}}}, 'Edit {{$sale->name}}', 'modal-md');"
                                                        class="btn btn-sm btn-primary js-tooltip-enabled"
                                                        data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                                <button type="button"
                                                        onclick="deleteModelItem('Sale', {{$sale->id}}, 'sale-tr-{{$sale->id}}');"
                                                        class="btn btn-sm btn-danger js-tooltip-enabled"
                                                        data-toggle="tooltip" title="Delete"
                                                        data-original-title="Delete">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection


