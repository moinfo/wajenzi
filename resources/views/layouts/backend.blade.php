<!doctype html>
<html lang="{{ config('app.locale') }}" class="no-focus">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">

        <title>{{  $page_title }}</title>

        <meta name="description" content="HRMS - Bootstrap 4 Admin Template &amp; UI Framework created by pixelcave and published on Themeforest">
        <meta name="author" content="pixelcave">
        <meta name="robots" content="noindex, nofollow">

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Icons -->
        <link rel="shortcut icon" href="{{ asset('media/favicons/favicon.png') }}">
        <link rel="icon" sizes="192x192" type="image/png" href="{{ asset('media/favicons/favicon-192x192.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('media/favicons/apple-touch-icon-180x180.png') }}">

        <!-- Fonts and Styles -->
        @yield('css_before')
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito+Sans:300,400,400i,600,700">
        <link rel="stylesheet" id="css-main" href="{{ mix('/css/codebase.css') }}">
        <link rel="stylesheet" id="css-sweetalert2" href="{{ asset('js/plugins/sweetalert2/sweetalert2.min.css') }}">
        <link rel="stylesheet" id="css-datepicker" href="{{ asset('js/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}">
        <!-- You can include a specific file from public/css/themes/ folder to alter the default color theme of the template. eg: -->

        @yield('css_after')

        <!-- Scripts -->
        <script>window.Laravel = {!! json_encode(['csrfToken' => csrf_token(),]) !!};</script>
    </head>
    <body>
        <!-- Page Container -->
        <!--
            Available classes for #page-container:

        GENERIC

            'enable-cookies'                            Remembers active color theme between pages (when set through color theme helper Template._uiHandleTheme())

        SIDEBAR & SIDE OVERLAY

            'sidebar-r'                                 Right Sidebar and left Side Overlay (default is left Sidebar and right Side Overlay)
            'sidebar-mini'                              Mini hoverable Sidebar (screen width > 991px)
            'sidebar-o'                                 Visible Sidebar by default (screen width > 991px)
            'sidebar-o-xs'                              Visible Sidebar by default (screen width < 992px)
            'sidebar-inverse'                           Dark themed sidebar

            'side-overlay-hover'                        Hoverable Side Overlay (screen width > 991px)
            'side-overlay-o'                            Visible Side Overlay by default

            'enable-page-overlay'                       Enables a visible clickable Page Overlay (closes Side Overlay on click) when Side Overlay opens

            'side-scroll'                               Enables custom scrolling on Sidebar and Side Overlay instead of native scrolling (screen width > 991px)

        HEADER

            ''                                          Static Header if no class is added
            'page-header-fixed'                         Fixed Header

        HEADER STYLE

            ''                                          Classic Header style if no class is added
            'page-header-modern'                        Modern Header style
            'page-header-inverse'                       Dark themed Header (works only with classic Header style)
            'page-header-glass'                         Light themed Header with transparency by default
                                                        (absolute position, perfect for light images underneath - solid light background on scroll if the Header is also set as fixed)
            'page-header-glass page-header-inverse'     Dark themed Header with transparency by default
                                                        (absolute position, perfect for dark images underneath - solid dark background on scroll if the Header is also set as fixed)

        MAIN CONTENT LAYOUT

            ''                                          Full width Main Content if no class is added
            'main-content-boxed'                        Full width Main Content with a specific maximum width (screen width > 1200px)
            'main-content-narrow'                       Full width Main Content with a percentage width (screen width > 1200px)
        -->
        <div id="page-container" class="sidebar-o enable-page-overlay side-scroll page-header-modern main-content-boxed
        {{$theme['header']['inverse'] ? 'page-header-inverse' : ''}}
        {{$theme['header']['fixed'] ? 'page-header-fixed' : ''}}
        {{$theme['sidebar']['inverse'] ? 'sidebar-inverse' : ''}}
        {{$theme['sidebar']['mini'] ? 'sidebar-mini' : ''}}
            ">
            <!-- Side Overlay-->
            @include('components.sideoverlay')
            <!-- END Side Overlay -->

            @include('components.sidebar')
            <!-- END Sidebar -->

            <!-- Header -->
            @include('components.header')
            <!-- END Header -->

            <!-- Main Container -->
            <main id="main-container">
                @yield('content')
            </main>
            <!-- END Main Container -->

            <!-- Footer -->
            <footer id="page-footer" class="opacity-0">
                <div class="content py-20 font-size-sm clearfix">
                    <div class="float-right">
                        Crafted with <i class="fa fa-heart text-pulse"></i> by <a class="font-w600" href="https://kibahaonline.co.tz" target="_blank">KibahaOnline</a>
                    </div>
                    <div class="float-left">
                        <a class="font-w600" href="#" target="_blank">Transaction Analysis</a> &copy; <span class="js-year-copy"></span>
                    </div>
                </div>
            </footer>
            <!-- END Footer -->
        </div>
        <!-- END Page Container -->
        <!-- Reusable Modal -->
        <!-- Pop In Modal -->
        <div class="modal fade" id="ajax-loader-modal" tabindex="-1" role="dialog" aria-labelledby="ajax-loader-modal" aria-hidden="true">
            <div class="modal-dialog modal-dialog-popin" role="document">
                <div class="modal-content">
                    <div class="block block-themed block-transparent mb-0">
                        <div class="block-header bg-primary-dark">
                            <h3 class="block-title" id="ajax-loader-modal-title">New</h3>
                            <div class="block-options">
                                <button type="button" class="btn-block-option" data-dismiss="modal" aria-label="Close">
                                    <i class="si si-close"></i>
                                </button>
                            </div>
                        </div>
                        <div class="block-content" id="ajax-loader-modal-content">

                        </div>
                    </div>
                    <div class="modal-footer" id="ajax-loader-modal-footer">
                    </div>
                </div>
            </div>
        </div>
        <!-- END Pop In Modal -->

        <!-- HRMS Core JS -->

        <script src="{{ mix('js/codebase.app.js') }}"></script>
        <script src="{{ asset('js/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
        <script src="{{ asset('js/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js') }}"></script>



        <script>

            var csrf_token = '{{csrf_token()}}';
            @foreach(Session::get('notifications') as $notification)
                Swal.fire('{{$notification['title']}}', '{{$notification['text']}}', '{{$notification['type']}}').then((res) => {
            @endforeach
            @foreach(Session::get('notifications') as $notification)
                });
            @endforeach
            <?php  Session::put('notifications', []); ?>
        </script>

        <script>

            /**
             *
             */
            function loadFormModal(form_name, params = null, title, modal_size = 'modal-md') {
                Utility.ajaxLoadForm(form_name, params, '#ajax-loader-modal-content', function(res){
                    if(res !== true) {
                        console.log('FormModal Error', res);
                    } else {
                        $("#ajax-loader-modal #ajax-loader-modal-title").html(title);
                        $("#ajax-loader-modal .modal-dialog").removeClass('modal-lg');
                        $("#ajax-loader-modal .modal-dialog").removeClass('modal-md');
                        $("#ajax-loader-modal .modal-dialog").addClass(modal_size);
                        $("#ajax-loader-modal input[name='_token']").val(csrf_token);
                        $("#ajax-loader-modal").modal('show');
                    }
                });
            }

            /**
             * Delete A modal Item using ajax
             * @param className
             * @param id
             * @param row_id
             */
            function deleteModelItem(className, id, row_id){
                Utility.swalConfirm('Are you sure you want to delete this ' + className + '?', 'Delete ' + className, {type: 'question'}, function(res) {
                    if(res){
                        Utility.deleteModelObject(className, id, function(result) {
                            if(result) {
                                Swal.fire('Deleted!', className + ' deleted successfully', 'success');
                                $("#" + row_id).hide();
                            } else {
                                Swal.fire('Deleted!', 'Failed to delete ' + className, 'error');
                            }
                        }, function(err){
                            Utility.swal('Error', 'Something went wrong!', 'error');
                        }, false);
                    } else {}
                });
            }
        </script>
        <!-- Laravel Scaffolding JS -->
        <!-- <script src="{{ mix('js/laravel.app.js') }}"></script> -->

        @yield('js_after')
    </body>
</html>
