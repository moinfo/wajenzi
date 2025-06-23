@extends('layouts.backend')

@section('content')

    <div class="main-container">
        <div class="content">
            <div class="content-heading">Payroll Allowances
                {{--                @include('components.headed_paper_settings')--}}
                <br/>
            </div>
            <div>
                <!-- Block Tabs Alternative Style -->
                <div class="block">
                    <ul class="nav nav-tabs nav-tabs-block" data-toggle="tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" href="#btabs-alt-static-allowance">Allowance</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#btabs-alt-static-subscription">Allowance Subscriptions</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#btabs-alt-static-payments">Allowance Payments</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#btabs-alt-static-summary">Allowance Subscriptions Summary</a>
                        </li>
                        <li class="nav-item ml-auto">
                            <a class="nav-link" href="#btabs-alt-static-settings"><i class="si si-settings"></i></a>
                        </li>
                    </ul>
                    <div class="block-content tab-content">
                        <div class="tab-pane active" id="btabs-alt-static-allowance" role="tabpanel">
                            <div class="class card-box">
                                <div class="row" style="border-bottom: 3px solid gray">
                                    <div class="col-md-3 text-right">
                                        <img class="" src="{{ asset('media/logo/wajenzilogo.png') }}" alt="" height="100">
                                    </div>
                                    <div class="col-md-6 text-center">
                                           <span class="text-center font-size-h3">{{settings('ORGANIZATION_NAME')}}</span><br/>
            <span class="text-center font-size-h5">{{settings('COMPANY_ADDRESS_LINE_1')}}</span><br/>
            <span class="text-center font-size-h5">{{settings('COMPANY_ADDRESS_LINE_2')}}</span><br/>
            <span class="text-center font-size-h5">{{settings('COMPANY_PHONE_NUMBER')}}</span><br/>
            <span class="text-center font-size-h5">{{settings('TAX_IDENTIFICATION_NUMBER')}}</span><br/>
                                    </div>
                                    <div class="col-md-3 text-right">
                                        {{--                                        <a href="{{route('hr_settings')}}"   type="button" class="btn btn-sm btn-danger"><i class="fa fa arrow-left"></i>Back</a>--}}
                                    </div>
                                </div>
                            </div>
                            <br/>
                            <br/>
                            <div>
                                <div class="float-right">
                                    @can('Add Allowance')
                                        <button type="button" onclick="loadFormModal('settings_allowance_form', {className: 'Allowance'}, 'Create New Allowance', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10">
                                            <i class="si si-plus">&nbsp;</i>New Allowance</button>
                                    @endcan
                                </div>
                                <div >
                                    <h4 class="font-w400">Allowance Content</h4>
                                </div>
                            </div>

                            <br/>
                            <br/>
                            <div class="block">
                                <div class="block-content">
                                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                        <thead>
                                        <tr>
                                            <th class="text-center" style="width: 100px;">#</th>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th class="d-none d-sm-table-cell" style="width: 30%;">Description</th>
                                            <th class="text-center" style="width: 100px;">Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($allowances as $allowance)
                                            <tr id="allowance-tr-{{$allowance->id}}">
                                                <td class="text-center">
                                                    {{$loop->index + 1}}
                                                </td>
                                                <td class="font-w600">{{ $allowance->name }}</td>
                                                <td class="font-w600">{{ $allowance->allowance_type }}</td>
                                                <td class="d-none d-sm-table-cell">{{ $allowance->description }}
                                                </td>
                                                <td class="text-center" >
                                                    <div class="btn-group">
                                                        @can('Edit Allowance')
                                                            <button type="button" onclick="loadFormModal('settings_allowance_form', {className: 'Allowance', id: {{$allowance->id}}}, 'Edit {{$allowance->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                                <i class="fa fa-pencil"></i>
                                                            </button>
                                                        @endcan

                                                        @can('Delete Allowance')
                                                            <button type="button" onclick="deleteModelItem('Allowance', {{$allowance->id}}, 'allowance-tr-{{$allowance->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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
                        <div class="tab-pane" id="btabs-alt-static-subscription" role="tabpanel">
                            <div class="tab-pane active" id="btabs-alt-static-allocate" role="tabpanel">
                                <div class="class card-box">
                                    <div class="row" style="border-bottom: 3px solid gray">
                                        <div class="col-md-3 text-right">
                                            <img class="" src="{{ asset('media/logo/wajenzilogo.png') }}" alt="" height="100">
                                        </div>
                                        <div class="col-md-6 text-center">
                                            <span class="text-center font-size-h3">LERUMA ENTERPRISES</span><br/>
                                            <span class="text-center font-size-h5">BOX 30133, KIBAHA - COAST, Mobile 0657 798 062</span><br/>
                                            <span class="text-center font-size-h5">TIN 113 - 882 - 384</span>
                                        </div>
                                        <div class="col-md-3 text-right">
                                            {{--                                        <a href="{{route('hr_settings')}}"   type="button" class="btn btn-sm btn-danger"><i class="fa fa arrow-left"></i>Back</a>--}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br/>
                            <br/>
                            <div class="float-right">
                                @can('Add Allowance Subscription')
                                    <button type="button" onclick="loadFormModal('settings_allowance_subscriptions_form', {className: 'AllowanceSubscription'}, 'Create New AllowanceSubscription', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10">
                                        <i class="si si-plus">&nbsp;</i>New AllowanceSubscription</button>@endcan

                            </div>
                            <div >
                                <h4 class="font-w400">Staff Allowance Subscriptions Content</h4>
                            </div>
                            <br/>
                            <br/>
                            <div class="block">

                                <div class="block-content">
                                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full" data-ordering="false">
                                        <thead>
                                        <tr>
                                            <th class="text-center">#</th>
                                            <th>Sn/Name</th>
                                            <th>Date</th>
                                            <th>Allowance</th>
                                            <th>Amount</th>
                                            <th class="text-center" style="width: 100px;">Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $sum = 0;
                                        ?>
                                        {{--                            @dd($allowance_subscriptions)--}}
                                        @foreach($staffs as $staff)
                                            <tr>
                                                <td class="text-center">
                                                    {{$loop->iteration}}
                                                </td>
                                                <td class="font-w600">{{ $staff->name ?? null}}</td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>

                                            @foreach($staff->allowance_subscriptions as $allowance_subscription)
                                                @php
                                                    $month = date('m');
                                                    $staff_id = $staff->id ?? null;
                                                    $allowance_type = $allowance_subscription->allowance->allowance_type;
                                                    $allowance_amount_first = $allowance_subscription->amount;
                                                        $allowance_amount = \App\Models\Allowance::getAllowanceAmountPerType($allowance_type,$allowance_amount_first,$month);
                                                   $sum += $allowance_amount;
                                                @endphp
                                                <tr id="staff-tr-{{$allowance_subscription->id}}">
                                                    <td class="font-w600"></td>
                                                    <td class="text-right">
                                                        {{$loop->iteration}}
                                                    </td>
                                                    <td class="font-w600">{{ $allowance_subscription->date }}</td>
                                                    <td class="font-w600">{{ $allowance_subscription->allowance->name ?? null}}</td>
                                                    <td class="text-right">{{ number_format($allowance_amount ?? null)}}</td>
                                                    <td class="text-center" >
                                                        <div class="btn-group">
                                                            @can('Edit Allowance Subscription')
                                                                <button type="button" onclick="loadFormModal('settings_allowance_subscriptions_form', {className: 'AllowanceSubscription', id: {{$allowance_subscription->id}}}, 'Edit {{$staff->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                                    <i class="fa fa-pencil"></i>
                                                                </button>
                                                            @endcan
                                                            @can('Delete Allowance Subscription')
                                                                <button type="button" onclick="deleteModelItem('AllowanceSubscription', {{$allowance_subscription->id}}, 'staff-tr-{{$allowance_subscription->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
                                                                    <i class="fa fa-times"></i>
                                                                </button>
                                                            @endcan

                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                        </tbody>
{{--                                        <tfoot>--}}
{{--                                        <tr>--}}
{{--                                            <td colspan="5" class="text-right font-size-h4">TOTAL</td>--}}
{{--                                            <td class="text-right font-size-h4">{{number_format($sum)}}</td>--}}
{{--                                        </tr>--}}
{{--                                        </tfoot>--}}
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="btabs-alt-static-payments" role="tabpanel">
                            <div class="class card-box">
                                <div class="row" style="border-bottom: 3px solid gray">
                                    <div class="col-md-3 text-right">
                                        <img class="" src="{{ asset('media/logo/wajenzilogo.png') }}" alt="" height="100">
                                    </div>
                                    <div class="col-md-6 text-center">
                                        <span class="text-center font-size-h3">LERUMA ENTERPRISES</span><br/>
                                        <span class="text-center font-size-h5">BOX 30133, KIBAHA - COAST, Mobile 0657 798 062</span><br/>
                                        <span class="text-center font-size-h5">TIN 113 - 882 - 384</span>
                                    </div>
                                    <div class="col-md-3 text-right">
                                        {{--                                        <a href="{{route('hr_settings')}}"   type="button" class="btn btn-sm btn-danger"><i class="fa fa arrow-left"></i>Back</a>--}}
                                    </div>
                                </div>
                            </div>
                            <br/>
                            <br/>
                            <div class="float-right">
                                <?php
                                $start_date = date('Y-m-01');
                                $end_date = date('Y-m-t');
                                $allowance_created = \App\Models\AllowancePayment::isCurrentAllowancePaid($start_date,$end_date);
                                if(Auth::user()->id == 1){
                                ?>
                                <button type="button" onclick="loadFormModal('settings_allowance_payment_form', {className: 'AllowancePayment'}, 'Create New Allowance Payment', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10">
                                    <i class="si si-plus">&nbsp;</i>New Allowance Payment</button>
                                <?php
                                }else{

                                if($allowance_created){
                                ?>
                                <button type="button" class="btn btn-rounded btn-outline-success min-width-125 mb-10">
                                    <i class="si si-plus">&nbsp;</i>Already Paid Allowance This Month</button>
                                <?php
                                }else{
                                ?>
                                @can('Add Allowance Payment')
                                    <button type="button" onclick="loadFormModal('settings_allowance_payment_form', {className: 'AllowancePayment'}, 'Create New Allowance Payment', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10">
                                        <i class="si si-plus">&nbsp;</i>New Allowance Payment</button> @endcan
                                <?php
                                }
                                }
                                ?>

                            </div>
                            <div >
                                <h4 class="font-w400">Allowance Payments Content</h4>
                            </div>
                            <br/>
                            <br/>
                            <br/>
                            <div class="block">
                                <div class="block-content">
                                    <div class="row no-print m-t-10">
                                        <div class="class col-md-12">
                                            <div class="class card-box">
                                                <form  name="advance_salary_search" action="" id="filter-form" method="post" autocomplete="off">
                                                    @csrf
                                                    <div class="row">
                                                        <div class="class col-md-3">
                                                            <div class="input-group mb-3">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text" id="basic-addon1">Start Date</span>
                                                                </div>
                                                                <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">
                                                            </div>
                                                        </div>
                                                        <div class="class col-md-3">
                                                            <div class="input-group mb-3">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text" id="basic-addon2">End Date</span>
                                                                </div>
                                                                <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">
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
                                            <th class="text-center" >#</th>
                                            <th>Date</th>
                                            <th class="d-none d-sm-table-cell" >Amount</th>
                                            <th class="text-center" style="width: 100px;">Actions</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        $sum = 0;
                                        ?>
                                        @foreach($allowance_payments as $allowance_payment)
                                            <?php
                                            $sum += $allowance_payment->amount;
                                            ?>
                                            <tr id="allowance_payment-tr-{{$allowance_payment->id}}">
                                                <td class="text-center">
                                                    {{$loop->index + 1}}
                                                </td>
                                                <td class="font-w600">{{ $allowance_payment->date }}</td>
                                                <td class="text-right">{{number_format($allowance_payment->amount)}}
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group">
                                                        @can('Edit Allowance Payment')
                                                            <button type="button" onclick="loadFormModal('settings_allowance_payment_form', {className: 'AllowancePayment', id: {{$allowance_payment->id}}}, 'Edit Allowance Payment', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                                <i class="fa fa-pencil"></i>
                                                            </button>
                                                        @endcan

                                                        @can('Delete Allowance Payment')
                                                            <button type="button" onclick="deleteModelItem('AllowancePayment', {{$allowance_payment->id}}, 'allowance_payment-tr-{{$allowance_payment->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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
                                            <td class="text-right">{{number_format($sum)}}</td>
                                            <td></td>
                                        </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="btabs-alt-static-summary" role="tabpanel">
                            <div class="class card-box">
                                <div class="row" style="border-bottom: 3px solid gray">
                                    <div class="col-md-3 text-right">
                                        <img class="" src="{{ asset('media/logo/wajenzilogo.png') }}" alt="" height="100">
                                    </div>
                                    <div class="col-md-6 text-center">
                                        <span class="text-center font-size-h3">LERUMA ENTERPRISES</span><br/>
                                        <span class="text-center font-size-h5">BOX 30133, KIBAHA - COAST, Mobile 0657 798 062</span><br/>
                                        <span class="text-center font-size-h5">TIN 113 - 882 - 384</span>
                                    </div>
                                    <div class="col-md-3 text-right">
                                        {{--                                        <a href="{{route('hr_settings')}}"   type="button" class="btn btn-sm btn-danger"><i class="fa fa arrow-left"></i>Back</a>--}}
                                    </div>
                                </div>
                            </div>
                            <br/>
                            <br/>
                            <div class="float-right">


                            </div>
                            <div >
                                <h4 class="font-w400">Allowance Subscripitons Summary Content</h4>
                            </div>
                            <br/>
                            <br/>
                            <br/>
                            <div class="block">
                                <div class="block-content">
                                    <div class="row no-print m-t-10">
                                        <div class="class col-md-12">
                                            <div class="class card-box">
                                                <form  name="advance_salary_search" action="" id="filter-form" method="post" autocomplete="off">
                                                    @csrf
                                                    <div class="row">
                                                        <div class="class col-md-4">
                                                            <div class="input-group mb-3">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text" id="basic-addon1">Monthly</span>
                                                                </div>
                                                                @php
                                                                $selected = date('m');
                                                                @endphp
                                                                <select name="monthly" id="monthly" class="form-control" required>
{{--                                                                    <option value="">Select Month</option>--}}
                                                                    <option value="1" {{($selected == 1) ? 'selected' : ''}}>January</option>
                                                                    <option value="2" {{($selected == 2) ? 'selected' : ''}}>February</option>
                                                                    <option value="3" {{($selected == 3) ? 'selected' : ''}}>March</option>
                                                                    <option value="4" {{($selected == 4) ? 'selected' : ''}}>April</option>
                                                                    <option value="5" {{($selected == 5) ? 'selected' : ''}}>May</option>
                                                                    <option value="6" {{($selected == 6) ? 'selected' : ''}}>June</option>
                                                                    <option value="7" {{($selected == 7) ? 'selected' : ''}}>July</option>
                                                                    <option value="8" {{($selected == 8) ? 'selected' : ''}}>August</option>
                                                                    <option value="9" {{($selected == 9) ? 'selected' : ''}}>September</option>
                                                                    <option value="10" {{($selected == 10) ? 'selected' : ''}}>October</option>
                                                                    <option value="11" {{($selected == 11) ? 'selected' : ''}}>November</option>
                                                                    <option value="12" {{($selected == 12) ? 'selected' : ''}}>December</option>
                                                                </select>                                                            </div>
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
                                            <th class="text-center">#</th>
                                            <th>Name</th>
                                            @foreach($allowances as $allowance)
                                                <th>{{$allowance->name}}</th>
                                            @endforeach
                                            <th>Total Allowance</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @php
                                            $sum_allowance_amount = 0;
                                        @endphp
                                        @foreach($only_staffs as $staff)
                                            <tr id="allowance_payment-tr-{{$staff->id}}">
                                                <td class="text-center">
                                                    {{$loop->iteration}}
                                                </td>
                                                <td class="font-w600">{{ $staff->name }}</td>
                                                @php
                                                    $total_allowance_amount = 0;
                                                @endphp
                                                @foreach($allowances as $allowance)
                                                    @php
                                                        $monthly = $_POST['monthly'] ?? date('m');
                                                        $staff_id = $staff->id;
                                                        $allowance_id = $allowance->id;
                                                        $allowance_amount_first = \App\Models\Staff::getStaffAllowanceSubscribed($staff_id, $allowance_id);
                                                        $allowance_type = \App\Models\Allowance::getAllowanceType($allowance_id);
                                                        $allowance_amount = \App\Models\Allowance::getAllowanceAmountPerType($allowance_type, $allowance_amount_first, $monthly);
                                                        $total_allowance_amount += $allowance_amount;
                                                        $sum_allowance_amount += $allowance_amount;
                                                    @endphp
                                                    <td class="text-right">{{ number_format($allowance_amount) }}</td>
                                                @endforeach
                                                <td class="text-right">{{ number_format($total_allowance_amount) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                        <tfoot>
                                        <tr>
                                            <th></th>
                                            <th></th>
                                            @foreach($allowances as $allowance)
                                                <th></th>
                                            @endforeach
                                            <th class="text-right">{{ number_format($sum_allowance_amount) }}</th>
                                        </tr>
                                        </tfoot>
                                    </table>

                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="btabs-alt-static-settings" role="tabpanel">
                            <div class="tab-pane active" id="btabs-alt-static-allocate" role="tabpanel">
                                <div class="class card-box">
                                    <div class="row" style="border-bottom: 3px solid gray">
                                        <div class="col-md-3 text-right">
                                            <img class="" src="{{ asset('media/logo/wajenzilogo.png') }}" alt="" height="100">
                                        </div>
                                        <div class="col-md-6 text-center">
                                            <span class="text-center font-size-h3">LERUMA ENTERPRISES</span><br/>
                                            <span class="text-center font-size-h5">BOX 30133, KIBAHA - COAST, Mobile 0657 798 062</span><br/>
                                            <span class="text-center font-size-h5">TIN 113 - 882 - 384</span>
                                        </div>
                                        <div class="col-md-3 text-right">
                                            {{--                                        <a href="{{route('hr_settings')}}"   type="button" class="btn btn-sm btn-danger"><i class="fa fa arrow-left"></i>Back</a>--}}
                                        </div>
                                    </div>
                                </div>
                                <br/>
                                <br/>
                                <div class="float-right">
                                    @can('Add Deduction Setting')
                                        <button type="button" onclick="loadFormModal('settings_deduction_settings_form', {className: 'DeductionSetting'}, 'Create New DeductionSetting', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10">
                                            <i class="si si-plus">&nbsp;</i>New DeductionSetting</button>
                                    @endcan

                                </div>
                                <div >
                                    <h4 class="font-w400">Deduction Settings Content</h4>
                                </div>
                                <br/>
                                <br/>
                                <div class="block">

                                    <div class="block-content">
                                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                            <thead>
                                            <tr>
                                                <th class="text-center" style="width: 100px;">#</th>
                                                <th>Deduction</th>
                                                <th>Minimum Amount</th>
                                                <th>Maximum Amount</th>
                                                <th>Employee Percentage %</th>
                                                <th>Employer Percentage %</th>
                                                <th>Additional Amount</th>
                                                <th class="text-center" style="width: 100px;">Actions</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($deduction_settings as $deduction_setting)
                                                <tr id="deduction_setting-tr-{{$deduction_setting->id}}">
                                                    <td class="text-center">
                                                        {{$loop->index + 1}}
                                                    </td>
                                                    <td class="font-w600">{{ $deduction_setting->deduction->name ?? null}}</td>
                                                    <td class="text-right">{{ number_format($deduction_setting->minimum_amount)}}</td>
                                                    <td class="text-right">{{ number_format($deduction_setting->maximum_amount)}}</td>
                                                    <td class="text-right">{{ number_format($deduction_setting->employee_percentage)}}</td>
                                                    <td class="text-right">{{ number_format($deduction_setting->employer_percentage)}}</td>
                                                    <td class="text-right">{{ number_format($deduction_setting->additional_amount)}}</td>
                                                    <td class="text-center" >
                                                        <div class="btn-group">
                                                            @can('Edit Deduction Setting')
                                                                <button type="button" onclick="loadFormModal('settings_deduction_settings_form', {className: 'DeductionSetting', id: {{$deduction_setting->id}}}, 'Edit {{$deduction_setting->name}}', 'modal-md');" class="btn btn-sm btn-primary js-tooltip-enabled" data-toggle="tooltip" title="Edit" data-original-title="Edit">
                                                                    <i class="fa fa-pencil"></i>
                                                                </button>
                                                            @endcan
                                                            @can('Delete Deduction Setting')
                                                                <button type="button" onclick="deleteModelItem('DeductionSetting', {{$deduction_setting->id}}, 'deduction_setting-tr-{{$deduction_setting->id}}');" class="btn btn-sm btn-danger js-tooltip-enabled" data-toggle="tooltip" title="Delete" data-original-title="Delete">
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
                    <!-- END Block Tabs Alternative Style -->

                </div>
            </div>
        </div>

    </div>
@endsection
