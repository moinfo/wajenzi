@extends('layouts.backend')

@section('content')
    <?php
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
    $last_month_last_date = date("Y-m-t", strtotime("last month"));
    $last_month_first_date = date("Y-m-01", strtotime("last month"));
    $sales = \App\Models\Sale::getTotalTax($start_date,$end_date);
    $purchases = \App\Models\Purchase::getTotalPurchasesWithVAT($end_date,null,null,$start_date,);

    $vat_analysis = new \App\Models\VatAnalysis();

    $this_month_tax_payable = $vat_analysis->getTaxPayable($end_date);
    $last_month_tax_payable = \App\Models\VatPayment::getTotalPaymentOfLastMonth($last_month_first_date,$last_month_last_date)

    ?>
<!-- Page Content -->
<div class="content">
    <div class="row invisible" data-toggle="appear">
        <!-- Row #1 -->
        <div class="col-6 col-xl-3">
            <a class="block block-rounded block-bordered block-link-shadow" href="javascript:void(0)">
                <div class="block-content block-content-full clearfix">
                    <div class="float-right mt-15 d-none d-sm-block">
                        <i class="si si-bag fa-2x text-primary-light"></i>
                    </div>
                    <div class="font-size-h3 font-w600 text-primary" data-toggle="countTo" data-speed="1000" data-to="{{$sales}}">{{$sales}}</div>
                    <div class="font-size-sm font-w600 text-uppercase text-muted">Sales</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a class="block block-rounded block-bordered block-link-shadow" href="javascript:void(0)">
                <div class="block-content block-content-full clearfix">
                    <div class="float-right mt-15 d-none d-sm-block">
                        <i class="si si-wallet fa-2x text-earth-light"></i>
                    </div>
                    <div class="font-size-h3 font-w600 text-earth"><span data-toggle="countTo" data-speed="1000" data-to="{{$purchases}}">{{$purchases}}</span></div>
                    <div class="font-size-sm font-w600 text-uppercase text-muted">Purchases</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a class="block block-rounded block-bordered block-link-shadow" href="javascript:void(0)">
                <div class="block-content block-content-full clearfix">
                    <div class="float-right mt-15 d-none d-sm-block">
                        <i class="si si-globe-alt fa-2x text-elegance-light"></i>
                    </div>
                    <div class="font-size-h3 font-w600 text-elegance" data-toggle="countTo" data-speed="1000" data-to="{{$last_month_tax_payable}}">{{$last_month_tax_payable}}</div>
                    <div class="font-size-sm font-w600 text-uppercase text-muted">Last Month VAT</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a class="block block-rounded block-bordered block-link-shadow" href="javascript:void(0)">
                <div class="block-content block-content-full clearfix">
                    <div class="float-right mt-15 d-none d-sm-block">
                        <i class="si si-bar-chart fa-2x text-pulse"></i>
                    </div>
                    <div class="font-size-h3 font-w600 text-pulse" data-toggle="countTo" data-speed="1000" data-to="{{$this_month_tax_payable}}">{{$this_month_tax_payable}}</div>
                    <div class="font-size-sm font-w600 text-uppercase text-muted">This Month VAT</div>
                </div>
            </a>
        </div>
        <!-- END Row #1 -->
    </div>
    <div class="row invisible" data-toggle="appear">
        <!-- Row #2 -->
        <div class="col-md-6">
            <div class="block block-rounded block-bordered">
                <div class="block-header block-header-default border-b">
                    <h3 class="block-title">
                        Sales <small>This week</small>
                    </h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                            <i class="si si-refresh"></i>
                        </button>
                        <button type="button" class="btn-block-option">
                            <i class="si si-wrench"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content block-content-full">
                    <div class="pull-all pt-50">
                        <!-- Lines Chart Container functionality is initialized in js/pages/be_pages_dashboard.min.js which was auto compiled from _es6/pages/be_pages_dashboard.js -->
                        <!-- For more info and examples you can check out http://www.chartjs.org/docs/ -->
                        <canvas class="js-chartjs-dashboard-lines"></canvas>
                    </div>
                </div>
                <div class="block-content">
                    <div class="row items-push text-center">
                        <div class="col-6 col-sm-6">
                            <div class="font-w600 text-success">
                                <i class="fa fa-caret-up"></i> +16%
                            </div>
                            <div class="font-size-h4 font-w600">{{$collection_in_month['total_amount']}}</div>
                            <div class="font-size-sm font-w600 text-uppercase text-muted">This Month</div>
                        </div>
                        <div class="col-6 col-sm-6">
                            <div class="font-w600 text-danger">
                                <i class="fa fa-caret-down"></i> -3%
                            </div>
                            <div class="font-size-h4 font-w600">{{$collection_in_week['total_amount']}}</div>
                            <div class="font-size-sm font-w600 text-uppercase text-muted">This Week</div>
                        </div>
{{--                        <div class="col-12 col-sm-4">--}}
{{--                            <div class="font-w600 text-success">--}}
{{--                                <i class="fa fa-caret-up"></i> +9%--}}
{{--                            </div>--}}
{{--                            <div class="font-size-h4 font-w600">24.3</div>--}}
{{--                            <div class="font-size-sm font-w600 text-uppercase text-muted">Average</div>--}}
{{--                        </div>--}}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="block block-rounded block-bordered">
                <div class="block-header block-header-default border-b">
                    <h3 class="block-title">
                        Purchases <small>This week</small>
                    </h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                            <i class="si si-refresh"></i>
                        </button>
                        <button type="button" class="btn-block-option">
                            <i class="si si-wrench"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content block-content-full">
                    <div class="pull-all pt-50">
                        <!-- Lines Chart Container functionality is initialized in js/pages/be_pages_dashboard.min.js which was auto compiled from _es6/pages/be_pages_dashboard.js -->
                        <!-- For more info and examples you can check out http://www.chartjs.org/docs/ -->
                        <canvas class="js-chartjs-dashboard-lines2"></canvas>
                    </div>
                </div>
                <div class="block-content bg-white">
                    <div class="row items-push text-center">
                        <div class="col-6 col-sm-6">
                            <div class="font-w600 text-success">
                                <i class="fa fa-caret-up"></i> +4%
                            </div>
                            <div class="font-size-h4 font-w600">{{$expenses_in_month['total_amount']}}</div>
                            <div class="font-size-sm font-w600 text-uppercase text-muted">This Month</div>
                        </div>
                        <div class="col-6 col-sm-6">
                            <div class="font-w600 text-danger">
                                <i class="fa fa-caret-down"></i> -7%
                            </div>
                            <div class="font-size-h4 font-w600">{{$expenses_in_month['total_amount']}}</div>
                            <div class="font-size-sm font-w600 text-uppercase text-muted">This Week</div>
                        </div>
{{--                        <div class="col-12 col-sm-4">--}}
{{--                            <div class="font-w600 text-success">--}}
{{--                                <i class="fa fa-caret-up"></i> +35%--}}
{{--                            </div>--}}
{{--                            <div class="font-size-h4 font-w600">$ 9,352</div>--}}
{{--                            <div class="font-size-sm font-w600 text-uppercase text-muted">Balance</div>--}}
{{--                        </div>--}}
                    </div>
                </div>
            </div>
        </div>
        <!-- END Row #2 -->
    </div>
{{--    <div class="row invisible" data-toggle="appear">--}}
{{--        <!-- Row #3 -->--}}
{{--        <div class="col-md-6">--}}
{{--            <div class="block block-rounded block-bordered">--}}
{{--                <div class="block-header block-header-default border-b">--}}
{{--                    <h3 class="block-title">Latest Orders</h3>--}}
{{--                    <div class="block-options">--}}
{{--                        <button type="button" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">--}}
{{--                            <i class="si si-refresh"></i>--}}
{{--                        </button>--}}
{{--                        <button type="button" class="btn-block-option">--}}
{{--                            <i class="si si-wrench"></i>--}}
{{--                        </button>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--                <div class="block-content">--}}
{{--                    <table class="table table-borderless table-striped">--}}
{{--                        <thead>--}}
{{--                        <tr>--}}
{{--                            <th style="width: 100px;">ID</th>--}}
{{--                            <th>Status</th>--}}
{{--                            <th class="d-none d-sm-table-cell">Customer</th>--}}
{{--                            <th class="text-right">Value</th>--}}
{{--                        </tr>--}}
{{--                        </thead>--}}
{{--                        <tbody>--}}
{{--                        <tr>--}}
{{--                            <td>--}}
{{--                                <a class="font-w600" href="be_pages_ecom_order.html">ORD.1851</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <span class="badge badge-info">Processing</span>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a href="be_pages_ecom_customer.html">Carol Ray</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-right">--}}
{{--                                <span class="text-black">$584</span>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        <tr>--}}
{{--                            <td>--}}
{{--                                <a class="font-w600" href="be_pages_ecom_order.html">ORD.1850</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <span class="badge badge-danger">Canceled</span>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a href="be_pages_ecom_customer.html">Marie Duncan</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-right">--}}
{{--                                <span class="text-black">$383</span>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        <tr>--}}
{{--                            <td>--}}
{{--                                <a class="font-w600" href="be_pages_ecom_order.html">ORD.1849</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <span class="badge badge-danger">Canceled</span>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a href="be_pages_ecom_customer.html">Scott Young</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-right">--}}
{{--                                <span class="text-black">$335</span>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        <tr>--}}
{{--                            <td>--}}
{{--                                <a class="font-w600" href="be_pages_ecom_order.html">ORD.1848</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <span class="badge badge-info">Processing</span>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a href="be_pages_ecom_customer.html">Justin Hunt</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-right">--}}
{{--                                <span class="text-black">$840</span>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        <tr>--}}
{{--                            <td>--}}
{{--                                <a class="font-w600" href="be_pages_ecom_order.html">ORD.1847</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <span class="badge badge-success">Completed</span>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a href="be_pages_ecom_customer.html">Thomas Riley</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-right">--}}
{{--                                <span class="text-black">$434</span>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        <tr>--}}
{{--                            <td>--}}
{{--                                <a class="font-w600" href="be_pages_ecom_order.html">ORD.1846</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <span class="badge badge-info">Processing</span>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a href="be_pages_ecom_customer.html">Carol White</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-right">--}}
{{--                                <span class="text-black">$142</span>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        <tr>--}}
{{--                            <td>--}}
{{--                                <a class="font-w600" href="be_pages_ecom_order.html">ORD.1845</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <span class="badge badge-danger">Canceled</span>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a href="be_pages_ecom_customer.html">Melissa Rice</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-right">--}}
{{--                                <span class="text-black">$116</span>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        <tr>--}}
{{--                            <td>--}}
{{--                                <a class="font-w600" href="be_pages_ecom_order.html">ORD.1844</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <span class="badge badge-warning">Pending</span>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a href="be_pages_ecom_customer.html">Wayne Garcia</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-right">--}}
{{--                                <span class="text-black">$317</span>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        <tr>--}}
{{--                            <td>--}}
{{--                                <a class="font-w600" href="be_pages_ecom_order.html">ORD.1843</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <span class="badge badge-info">Processing</span>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a href="be_pages_ecom_customer.html">Barbara Scott</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-right">--}}
{{--                                <span class="text-black">$256</span>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        <tr>--}}
{{--                            <td>--}}
{{--                                <a class="font-w600" href="be_pages_ecom_order.html">ORD.1842</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <span class="badge badge-warning">Pending</span>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a href="be_pages_ecom_customer.html">Brian Cruz</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-right">--}}
{{--                                <span class="text-black">$568</span>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        </tbody>--}}
{{--                    </table>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        <div class="col-md-6">--}}
{{--            <div class="block block-rounded block-bordered">--}}
{{--                <div class="block-header block-header-default border-b">--}}
{{--                    <h3 class="block-title">Top Products</h3>--}}
{{--                    <div class="block-options">--}}
{{--                        <button type="button" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">--}}
{{--                            <i class="si si-refresh"></i>--}}
{{--                        </button>--}}
{{--                        <button type="button" class="btn-block-option">--}}
{{--                            <i class="si si-wrench"></i>--}}
{{--                        </button>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--                <div class="block-content">--}}
{{--                    <table class="table table-borderless table-striped">--}}
{{--                        <thead>--}}
{{--                        <tr>--}}
{{--                            <th class="d-none d-sm-table-cell" style="width: 100px;">ID</th>--}}
{{--                            <th>Product</th>--}}
{{--                            <th class="text-center">Orders</th>--}}
{{--                            <th class="d-none d-sm-table-cell text-center">Rating</th>--}}
{{--                        </tr>--}}
{{--                        </thead>--}}
{{--                        <tbody>--}}
{{--                        <tr>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a class="font-w600" href="be_pages_ecom_product_edit.html">PID.258</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <a href="be_pages_ecom_product_edit.html">Dark Souls III</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-center">--}}
{{--                                <a class="text-gray-dark" href="be_pages_ecom_orders.html">912</a>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell text-center">--}}
{{--                                <div class="text-warning">--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                </div>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        <tr>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a class="font-w600" href="be_pages_ecom_product_edit.html">PID.198</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <a href="be_pages_ecom_product_edit.html">Bioshock Collection</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-center">--}}
{{--                                <a class="text-gray-dark" href="be_pages_ecom_orders.html">895</a>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell text-center">--}}
{{--                                <div class="text-warning">--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                </div>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        <tr>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a class="font-w600" href="be_pages_ecom_product_edit.html">PID.852</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <a href="be_pages_ecom_product_edit.html">Alien Isolation</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-center">--}}
{{--                                <a class="text-gray-dark" href="be_pages_ecom_orders.html">820</a>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell text-center">--}}
{{--                                <div class="text-warning">--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                </div>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        <tr>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a class="font-w600" href="be_pages_ecom_product_edit.html">PID.741</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <a href="be_pages_ecom_product_edit.html">Bloodborne</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-center">--}}
{{--                                <a class="text-gray-dark" href="be_pages_ecom_orders.html">793</a>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell text-center">--}}
{{--                                <div class="text-warning">--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                </div>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        <tr>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a class="font-w600" href="be_pages_ecom_product_edit.html">PID.985</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <a href="be_pages_ecom_product_edit.html">Forza Motorsport 7</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-center">--}}
{{--                                <a class="text-gray-dark" href="be_pages_ecom_orders.html">782</a>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell text-center">--}}
{{--                                <div class="text-warning">--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                </div>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        <tr>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a class="font-w600" href="be_pages_ecom_product_edit.html">PID.056</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <a href="be_pages_ecom_product_edit.html">Fifa 18</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-center">--}}
{{--                                <a class="text-gray-dark" href="be_pages_ecom_orders.html">776</a>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell text-center">--}}
{{--                                <div class="text-warning">--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                </div>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        <tr>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a class="font-w600" href="be_pages_ecom_product_edit.html">PID.036</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <a href="be_pages_ecom_product_edit.html">Gears of War 4</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-center">--}}
{{--                                <a class="text-gray-dark" href="be_pages_ecom_orders.html">680</a>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell text-center">--}}
{{--                                <div class="text-warning">--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                </div>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        <tr>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a class="font-w600" href="be_pages_ecom_product_edit.html">PID.682</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <a href="be_pages_ecom_product_edit.html">Minecraft</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-center">--}}
{{--                                <a class="text-gray-dark" href="be_pages_ecom_orders.html">670</a>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell text-center">--}}
{{--                                <div class="text-warning">--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                </div>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        <tr>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a class="font-w600" href="be_pages_ecom_product_edit.html">PID.478</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <a href="be_pages_ecom_product_edit.html">Dishonored 2</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-center">--}}
{{--                                <a class="text-gray-dark" href="be_pages_ecom_orders.html">640</a>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell text-center">--}}
{{--                                <div class="text-warning">--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                </div>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        <tr>--}}
{{--                            <td class="d-none d-sm-table-cell">--}}
{{--                                <a class="font-w600" href="be_pages_ecom_product_edit.html">PID.952</a>--}}
{{--                            </td>--}}
{{--                            <td>--}}
{{--                                <a href="be_pages_ecom_product_edit.html">Gran Turismo Sport</a>--}}
{{--                            </td>--}}
{{--                            <td class="text-center">--}}
{{--                                <a class="text-gray-dark" href="be_pages_ecom_orders.html">630</a>--}}
{{--                            </td>--}}
{{--                            <td class="d-none d-sm-table-cell text-center">--}}
{{--                                <div class="text-warning">--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                    <i class="fa fa-star"></i>--}}
{{--                                </div>--}}
{{--                            </td>--}}
{{--                        </tr>--}}
{{--                        </tbody>--}}
{{--                    </table>--}}
{{--                   --}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--        <!-- END Row #3 -->--}}
{{--    </div>--}}
    <?php
    use App\Models\Collection;use Illuminate\Support\Facades\DB;
    $monday = strtotime("last monday");
    $monday = date('w', $monday)==date('w') ? $monday+7*86400 : $monday;
    $sunday = strtotime(date("Y-m-d",$monday)." +6 days");
    $this_week_sd = date("Y-m-d",$monday);
    $this_week_ed = date("Y-m-d",$sunday);
    //        echo "Current week range from $this_week_sd to $this_week_ed ";

    $first_date = explode("-", $this_week_sd);
    $last_date = explode("-", $this_week_ed);

    for($i = $first_date[2]; $i <=  $last_date[2]; $i++)
    {
        // add the date to the dates array
        $dates[] = date('Y') . "-" . date('m') . "-" . str_pad($i, 2, '0', STR_PAD_LEFT);
    }
    //dump($dates);
    //                    $no = 1;
    if (isset($dates)) {
        foreach ($dates as $index => $date) {
            // echo $date;
            $collections_per_week[] = \App\Models\Sale::Where('date',$date)->select([DB::raw("SUM(tax) as total_amount")])->groupBy('date')->get()->first()['total_amount'] ?? 0;
            $expenses_per_week[] = \App\Models\Purchase::Where('date',$date)->select([DB::raw("SUM(vat_amount) as total_amount")])->groupBy('date')->get()->first()['total_amount'] ?? 0;

        }
    }

    if (!empty($collections_per_week)) {
        $collection_in_a_day_per_week = implode (", ", $collections_per_week);
    }
    if (!empty($expenses_per_week)) {
        $expense_in_a_day_per_week = implode (", ", $expenses_per_week);
    }

    ?>
</div>
<!-- END Page Content -->
@endsection

@section('js_after')
    <!-- Page JS Plugins -->
    <script src="{{ asset('js/plugins/chartjs/Chart.bundle.min.js')}}"></script>
    <script>
        var x = new Chart($('.js-chartjs-dashboard-lines'), {
            type: "line",
            data: {
                labels: ["MON", "TUE", "WED", "THU", "FRI", "SAT", "SUN"],
                datasets: [
                    {
                        label: "This Week",
                        fill: !0,
                        backgroundColor: "rgba(66,165,245,.45)",
                        borderColor: "rgba(66,165,245,1)",
                        pointBackgroundColor: "rgba(66,165,245,1)",
                        pointBorderColor: "#fff",
                        pointHoverBackgroundColor: "#fff",
                        pointHoverBorderColor: "rgba(66,165,245,1)",
                        data: [<?=$collection_in_a_day_per_week ?? 0?>],
                    },
                ],
            },
            options: {
                scales: { yAxes: [{ ticks: { suggestedMax: 50 } }] },
                tooltips: {
                    callbacks: {
                        label: function (e, r) {
                            return " " + e.yLabel + " Sales";
                        },
                    },
                },
            },
        });

        var y = new Chart($('.js-chartjs-dashboard-lines2'), {
            type: "line",
            data: {
                labels: ["MON", "TUE", "WED", "THU", "FRI", "SAT", "SUN"],
                datasets: [
                    {
                        label: "This Week",
                        fill: !0,
                        backgroundColor: "rgba(156,204,101,.45)",
                        borderColor: "rgba(156,204,101,1)",
                        pointBackgroundColor: "rgba(156,204,101,1)",
                        pointBorderColor: "#fff",
                        pointHoverBackgroundColor: "#fff",
                        pointHoverBorderColor: "rgba(156,204,101,1)",
                        data: [<?=$expense_in_a_day_per_week ?? 0?>],
                    },
                ],
            },
            options: {
                scales: { yAxes: [{ ticks: { suggestedMax: 480 } }] },
                tooltips: {
                    callbacks: {
                        label: function (e, r) {
                            return " $ " + e.yLabel;
                        },
                    },
                },
            },
        });
    </script>


@endsection
