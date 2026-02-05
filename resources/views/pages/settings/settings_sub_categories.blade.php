@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Settings
                <div class="float-right">
                    @can('Add Statutory Payment Sub Category')
                        <button type="button" onclick="loadFormModal('settings_sub_category_form', {className: 'SubCategory'}, 'Create New Sub Category', 'modal-md');" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Sub Category</button> @endcan

                </div>
            </div>
            <div>

                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Sub Categories</h3>
                    </div>
                    @include('components.headed_paper_settings')
                    <br/>
                    <div class="block-content">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Date</th>
                                <th scope="col">Name</th>
                                <th scope="col">Description</th>
                                <th scope="col">Category</th>
                                <th scope="col">Billing_cycle</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Annually</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($sub_categories as $key => $value)
                                @php
                                    if($value->billing_cycle == 0){
                                        $billing_cycle = 'One Time';
                                        $annualy = ($value->price);
                                    } elseif($value->billing_cycle == 1){
                                        $billing_cycle = 'Annually';
                                        $annualy = ($value->price);
                                    }elseif($value->billing_cycle == 3){
                                        $billing_cycle = 'Quarterly';
                                        $annualy = ($value->price)*4;
                                    }elseif($value->billing_cycle == 6){
                                        $billing_cycle = 'Semi-Annually';
                                        $annualy = ($value->price)*2;
                                    }elseif($value->billing_cycle == 12){
                                        $billing_cycle = 'Monthly';
                                        $annualy = ($value->price)*12;
                                    }else{
                                        $billing_cycle = 'Nothing';
                                        $annualy = ($value->price);
                                    }
                                @endphp




                                <tr>
                                    <th scope="row">
                                        <a href="#">{{$loop->iteration}}</a>
                                    </th>
                                    <td>{{ $value->updated_at }}</td>
                                    <td>{{ $value->name }}</td>
                                    <td>{{ \Str::limit($value->description, 100) }}</td>
                                    <td>{{ $value->category->name}}</td>
                                    <td>{{ $billing_cycle}}</td>
                                    <td>{{ number_format($value->price)}}</td>
                                    <td>{{ number_format($annualy)}}</td>
                                    <td class="text-center" >
                                        <div class="btn-group">
                                            @can('Edit Statutory Payment Sub Category')
                                                <button type="button" onclick="loadFormModal('settings_sub_category_form', {className: 'SubCategory', id: {{$value->id}}}, 'Edit {{$value->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                            @can('Delete Statutory Payment Sub Category')
                                                <button type="button" onclick="deleteModelItem('SubCategory', {{$value->id}}, 'category-tr-{{$value->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            @endcan

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
@endsection

