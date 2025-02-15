@extends('layouts.backend')

@section('content')
<div class="main-container">
    <div class="content">
        <div class="content-heading">Reports
        </div>
        <div>
            <div class="block block-themed">
                <div class="block-content">
                    <div class="row no-print m-t-10">
                        <div class="class col-md-12">
                            @include('components.headed_paper')
                            <br/>
                            <div class="class card-box">
                                <form  name="collection_search" action="" id="filter-form" method="post" autocomplete="off">
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
                                                <button type="submit" name="submit" value="0" class="btn btn-sm btn-primary">Show</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="block-header text-center">
                                <h3 class="block-title">Allowance Subscriptions Report</h3>
                            </div>
                        </div>
                    </div>
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                       <thead>
                           <tr>
                               <th class="text-center" style="width: 100px;">#</th>
                               <th>Supervisor Name</th>
                               @foreach($allowances as $allowance)
                                   <th>{{$allowance->name}}</th>
                               @endforeach
                               <th>Total Allowances</th>
                           </tr>
                       </thead>
                        <tboady>
                            @foreach($staffs as $staff)
                                <tr>
                                    <td>{{$loop->iteration}}</td>
                                    <td>{{$staff->name}}</td>
                                    @foreach($allowances as $allowance)
                                        <?php
                                       // use Illuminate\Support\Facades\DB;
                                        $allowance_subscription = \App\Models\AllowanceSubscription::Where('staff_id',$staff->id)->Where('allowance_id',$allowance->id)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('staff_id')->get()->first();
                                        ?>
                                        <td class="text-right">{{number_format($allowance_subscription['total_amount'] ?? 0)}}</td>
                                    @endforeach
                                    <?php
                                    $allowance_subscription_per_staff = \App\Models\AllowanceSubscription::Where('staff_id',$staff->id)->select([DB::raw("SUM(amount) as total_amount")])->groupBy('staff_id')->get()->first()['total_amount'] ?? 0;

                                    ?>
                                    <th class="text-right">{{number_format($allowance_subscription_per_staff)}}</th>
                                </tr>
                            @endforeach

                        </tboady>
                        <tfoot>

                                <tr>
                                    <td></td>
                                    <td></td>
                                    @foreach($allowances as $allowance)
                                        <?php
                                        // use Illuminate\Support\Facades\DB;
                                        $allowance_subscription_per_allowance = \App\Models\AllowanceSubscription::Where('allowance_id',$allowance->id)->select([DB::raw("SUM(amount) as total_amount")])->get()->first();
                                        ?>
                                        <th class="text-right">{{number_format($allowance_subscription_per_allowance['total_amount'] ?? 0)}}</th>
                                    @endforeach
                                    <?php
                                    $allowance_subscription_all_staff = \App\Models\AllowanceSubscription::select([DB::raw("SUM(amount) as total_amount")])->get()->first()['total_amount'] ?? 0;

                                    ?>
                                    <th class="text-right">{{number_format($allowance_subscription_all_staff)}}</th>
                                </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
