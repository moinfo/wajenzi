@extends('layouts.backend')

@section('content')
    <!-- Page Content -->
    <div class="content">
        <h2 class="content-heading">Financial Analysis Related Settings <small>| All</small>
            <div class="float-right">

            </div>
        </h2>

        <div class="row js-appear-enabled animated fadeIn" data-toggle="appear">
            <!-- Row #5 -->
            @foreach($settings as $item)
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="block block-link-shadow text-center" href="{{ route($item['route']) }}">
                        <div class="block-content">
                            <p class="mt-5">
                                <i class="{{ $item['icon'] }} fa-3x"></i>
                            </p>
                            <p class="font-w600">{{ $item['name'] }}</p>
                        </div>
                    </a>
                </div>
            @endforeach
                <div class="col-6 col-md-4 col-xl-2">
                    <a class="block block-link-shadow text-center" href="#">
                        <div class="block-content ribbon ribbon-bookmark ribbon-success ribbon-left">
                            <div class="ribbon-box">15</div>
                            <p class="mt-5">
                                <i class="si si-envelope-letter fa-3x"></i>
                            </p>
                            <p class="font-w600">Test</p>
                        </div>
                    </a>
                </div>
        </div>


    </div>


    <!-- END Row #5 -->
    </div>

@endsection
