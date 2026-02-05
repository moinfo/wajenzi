@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Notifications
                <div class="float-right">
                        <a href="{{route('read_all_notifications')}}" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn"><i class="si si-plus">&nbsp;</i>Clear</a>
                </div>
            </div>
            <div>
                <div class="block block-themed">
                    <div class="block-header bg-gd-lake">
                        <h3 class="block-title">All Notifications</h3>
                    </div>
                    <div class="block-content">
{{--                        <div class="row no-print m-t-10">--}}
{{--                            <div class="class col-md-12">--}}
{{--                                <div class="class card-box">--}}
{{--                                    <form  name="system_capital_search" action="" id="filter-form" method="post" autocomplete="off">--}}
{{--                                        @csrf--}}
{{--                                        <div class="row">--}}
{{--                                            <div class="class col-md-3">--}}
{{--                                                <div class="input-group mb-3">--}}
{{--                                                    <div class="input-group-prepend">--}}
{{--                                                        <span class="input-group-text" id="basic-addon1">Start Date</span>--}}
{{--                                                    </div>--}}
{{--                                                    <input type="text" name="start_date" id="start_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon1" value="{{date('Y-m-d')}}">--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                            <div class="class col-md-3">--}}
{{--                                                <div class="input-group mb-3">--}}
{{--                                                    <div class="input-group-prepend">--}}
{{--                                                        <span class="input-group-text" id="basic-addon2">End Date</span>--}}
{{--                                                    </div>--}}
{{--                                                    <input type="text" name="end_date" id="end_date" class="form-control datepicker-index-form datepicker" aria-describedby="basic-addon2" value="{{date('Y-m-d')}}">--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                            <div class="class col-md-2">--}}
{{--                                                <div>--}}
{{--                                                    <button type="submit" name="submit"  class="btn btn-sm btn-primary">Show</button>--}}
{{--                                                </div>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                    </form>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Date</th>
                                    <th>Title</th>
                                    <th>Body</th>
                                    <th>Entry Id</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach( Auth::user()->unreadNotifications as $notification)
                                    <?php
                                    $link = $notification->data['link']
                                    ?>
                                    <tr>
                                        <td>{{$loop->iteration}}</td>
                                        <td>{{$notification->updated_at}}</td>
                                        <td>{{$notification->data['title']}}</td>
                                        <td>{{$notification->data['body']}}</td>
                                        <td class="text-center">{{$notification->data['document_id']}}</td>
                                        <td class="text-center">
                                            <a class="btn btn-sm btn-success js-tooltip-enabled" href="{{url($link)}}"><i class="fa fa-eye"></i></a>
                                        </td>
                                    </tr>

                                @endforeach
                                </tbody>
                                <tfoot>

                                </tfoot>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection


