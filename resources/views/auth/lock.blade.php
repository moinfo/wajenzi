@extends('layouts.simple')

@section('content')

    <div class="bg-image bg-pattern" style="background-image: url('assets/media/photos/photo34@2x.jpg');">
    <div class="row mx-0 justify-content-center bg-white-op-95">
        <div class="hero-static col-lg-6 col-xl-4">
            <div class="content content-full overflow-hidden">
                <!-- Header -->
                <div class="py-30 text-center">
                    <a class="link-effect text-pulse font-w700" href="/">
                        <i class="si si-user"></i>
                        <span class="font-size-xl text-pulse-dark">Swift</span><span class="font-size-xl">HRMS</span>
                    </a>
                    <h1 class="h4 font-w700 mt-30 mb-10">Welcome back, {{Auth::user()->name }}</h1>
                    <h2 class="h5 font-w400 text-muted mb-0">Please enter your password</h2>
                </div>
                <!-- END Header -->

                <!-- Unlock Form -->
                <!-- jQuery Validation functionality is initialized with .js-validation-lock class in js/pages/op_auth_lock.min.js which was auto compiled from _es6/pages/op_auth_lock.js -->
                <!-- For more examples you can check out https://github.com/jzaefferer/jquery-validation -->
                <form class="js-validation-lock" action="be_pages_auth_all.html" method="post">
                    <div class="block block-themed block-rounded block-shadow">
                        <div class="block-header bg-gd-pulse">
                            <h3 class="block-title">Unlock Account</h3>
                            <div class="block-options">
                                <button type="button" class="btn-block-option">
                                    <i class="si si-wrench"></i>
                                </button>
                            </div>
                        </div>
                        <div class="block-content">
                            <div class="form-group text-center">
                                <img class="img-avatar img-avatar96" src="assets/media/avatars/avatar15.jpg" alt="">
                            </div>
                            <div class="form-group row">
                                <div class="col-12">
                                    <label for="lock-password">Password</label>
                                    <input type="password" class="form-control" id="lock-password" name="lock-password">
                                </div>
                            </div>
                            <div class="form-group text-center">
                                <button type="submit" class="btn btn-alt-danger">
                                    <i class="si si-lock-open mr-10"></i> Unlock
                                </button>
                            </div>
                        </div>
                        <div class="block-content bg-body-light">
                            <div class="form-group text-center">
                                <a class="link-effect text-muted mr-10 mb-5 d-inline-block" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="si si-user text-muted mr-5"></i> Not you? Sign In
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </div>
                    </div>
                </form>
                <!-- END Unlock Form -->
            </div>
        </div>
    </div>
</div>
@endsection
