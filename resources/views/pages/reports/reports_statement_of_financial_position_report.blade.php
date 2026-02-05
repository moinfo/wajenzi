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
                        <h3 class="block-title">Statement of Financial Position As At {{date('jS-M-Y', strtotime('last day of december last year'))}}</h3>
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
                                    <th class="text-center">NOTE</th>
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
                                        <td><b>ASSETS</b></td>
                                        <td class="text-center"></td>
                                        <td class="text-right"></td>
                                        <td class="text-right"></td>
                                    </tr>
                                    <tr>
                                        <td><b>Non Current Assets</b></td>
                                        <td class="text-center"></td>
                                        <td class="text-right"></td>
                                        <td class="text-right"></td>
                                    </tr>
                                    <tr>
                                        <td>Property, Plant & Equipment</td>
                                        <td class="text-center">7</td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td><b>Current Assets</b></td>
                                        <td class="text-center"></td>
                                        <td class="text-right"></td>
                                        <td class="text-right"></td>
                                    </tr>
                                    <tr>
                                        <td>Closing Stock</td>
                                        <td class="text-center"></td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                    </tr>
                                    <tr>
                                        <td>Trade and Other Receivables</td>
                                        <td class="text-center"></td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                    </tr>
                                    <tr>
                                        <td>Cash and Bank Balances</td>
                                        <td class="text-center">6</td>
                                        <td class="text-right font-weight-bold">{{number_format(0)}}</td>
                                        <td class="text-right font-weight-bold">{{number_format(0)}}</td>
                                    </tr>
                                    <tr>
                                        <td>Tax Asset</td>
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
                                        <td>Total Assets</td>
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
                                        <td class="font-weight-bold">EQUITY AND LIABILITIES</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td class="font-weight-bold">Capital and Reserves</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>Capital</td>
                                        <td class="text-center"></td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                    </tr>
                                    <tr>
                                        <td>Accumulated Profit</td>
                                        <td class="text-center"></td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                    </tr>
                                    <tr>
                                        <td>Total Equity</td>
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
                                        <td class="font-weight-bold">Current Liabilities</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>Trade and Other Payables</td>
                                        <td class="text-center"></td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                        <td class="text-right">{{number_format(0)}}</td>
                                    </tr>
                                    <tr>
                                        <td>Tax Liability</td>
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
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>Total Equity and Liabilities</td>
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



