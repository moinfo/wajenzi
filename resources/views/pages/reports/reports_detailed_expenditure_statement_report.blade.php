@extends('layouts.backend')
@section('css_before')
    <!-- Page JS Plugins CSS -->
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables/dataTables.bootstrap4.css') }}">
@endsection

@section('js_after')
    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>

    <!-- Page JS Code -->
    <script src="{{ asset('js/pages/tables_datatables.js') }}"></script>

    <script>
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd'
        });
    </script>
@endsection
@section('content')

    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Reports
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-wajenzi-gradient">
                        <h3 class="block-title">Detailed Expenditure Statement For the Year ended {{date('jS-M-Y', strtotime('last day of december last year'))}}</h3>
                    </div>
                    <div class="block-content">
                        <div class="row no-print m-t-10">
                            <div class="class col-md-12">
                                @include('components.headed_paper')
                                <br/>
                                <div class="class card-box">
                                    <form  name="gross_search" action="" id="filter-form" method="post" autocomplete="off">
                                        @csrf
                                        <div class="row">
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon1">Start Date</span>
                                                    </div>
                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d', strtotime('first day of january this year'))}}">
                                                </div>
                                            </div>
                                            <div class="class col-md-3">
                                                <div class="input-group mb-3">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text" id="basic-addon2">End Date</span>
                                                    </div>
                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d', strtotime('last day of december this year'))}}">
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
                            <table class="table table-bordered table-striped table-vcenter ">
                                <thead>
                                <tr>
                                    <th></th>
                                    <th class="text-center"></th>
                                    <th class="text-right">{{date('Y-m-d', strtotime('last day of december this year'))}}</th>
                                    <th class="text-right">{{date('Y-m-d', strtotime('last day of december last year'))}}</th>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td></td>
                                    <td class="text-right">TShs</td>
                                    <td class="text-right">TShs</td>
                                </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><b>8. COST OF SALES</b></td>
                                        <td class="text-center"></td>
                                        <td class="text-right"></td>
                                        <td class="text-right"></td>
                                    </tr>
                                    <tr>
                                        <td>Opening Stock</td>
                                        <td class="text-center"></td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                    </tr>
                                    <tr>
                                        <td>Purchases</td>
                                        <td class="text-center"></td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                    </tr>
                                    <tr>
                                        <td><b>Less:</b> Closing Stock</td>
                                        <td class="text-center"></td>
                                        <td class="text-right font-weight-bold">{{number_format(0)}}</td>
                                        <td class="text-right font-weight-bold">{{number_format(0)}}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="text-center"></td>
                                        <td class="text-right font-weight-bold">{{number_format(0)}}</td>
                                        <td class="text-right font-weight-bold">{{number_format(0)}}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">9. FINANCIAL CHARGES</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>Bank Charges</td>
                                        <td class="text-center"></td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="text-center"></td>
                                        <td class="text-right font-weight-bold">{{number_format(0)}}</td>
                                        <td class="text-right font-weight-bold">{{number_format(0)}}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">10. ADMINISTRATION EXPENSES</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>Audit Fee</td>
                                        <td class="text-center"></td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td class="text-center"></td>
                                        <td class="text-right font-weight-bold">{{number_format(0)}}</td>
                                        <td class="text-right font-weight-bold">{{number_format(0)}}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>DEPRECIATION</td>
                                        <td class="text-center"></td>
                                      <td class="text-right">{{number_format(0)}}</td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>

                                </tbody>

                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection



