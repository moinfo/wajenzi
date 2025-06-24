@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @can('Add Stock')
                        <button type="button" onclick="loadFormModal('settings_stock_form', {className: 'Stock'}, 'Create New Stock', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>New Stock</button>
                    @endcan
                </div>
            </div>
            <div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Stock</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 100px;">#</th>
                                <th>Date</th>
                                <th>Stock Type</th>
                                <th class="d-none d-sm-table-cell" style="width: 30%;">Amount</th>
                                <td>Attachment</td>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $sum = 0;
                            ?>
                            @foreach($stocks as $stock)
                                <?php
                                    $sum += $stock->amount;
                                ?>
                                <tr id="stock-tr-{{$stock->id}}">
                                    <td class="text-center">
                                        {{$loop->index + 1}}
                                    </td>
                                    <td class="font-w600">{{ $stock->date}}</td>
                                    <td class="font-w600">{{ $stock->stock_type}}</td>
                                    <td class="text-right">{{ number_format($stock->amount) }}
                                    </td>
                                    <td class="text-center">
                                        @if($stock->file != null)
                                            <a href="{{ url("$stock->file") }}">Attachment</a>
                                        @else
                                            No File
                                        @endif
                                    </td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Stock')
                                                <button type="button" onclick="loadFormModal('settings_stock_form', {className: 'Stock', id: {{$stock->id}}}, 'Edit {{$stock->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                                @can('Delete Stock')
                                                    <button type="button" onclick="deleteModelItem('Stock', {{$stock->id}}, 'stock-tr-{{$stock->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="text-right">{{number_format($sum)}}</td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
