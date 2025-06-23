<!doctype html>
<html lang="{{ config('app.locale') }}" class="no-focus">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">

    <title>{{  settings('SYSTEM_NAME') }}</title>

    <meta name="description" content="{{  settings('SYSTEM_NAME') }}">
    <meta name="author" content="Mohamed Amiry">
    <meta name="robots" content="noindex, nofollow">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Icons -->
    <link rel="shortcut icon" href="{{ asset('media/favicons/favicon.png') }}">
    <link rel="icon" sizes="192x192" type="image/png" href="{{ asset('media/favicons/android-chrome-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('media/favicons/apple-touch-icon.png') }}">

    <!-- Fonts and Styles - Order matters! External frameworks first, then custom styles -->
    @yield('css_before')

    <!-- Bootstrap CSS - Loading this first as it's the base framework -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito+Sans:300,400,400i,600,700">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Plugin CSS -->
    <link rel="stylesheet" href="{{ asset('js/plugins/sweetalert2/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}">
    <link rel="stylesheet" href="{{ asset('js/plugins/datatables/dataTables.bootstrap4.css') }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.dataTables.min.css">

    <!-- Main CSS - Load after frameworks and plugins to override them -->
    <link rel="stylesheet" id="css-main" href="{{ mix('/css/codebase.css') }}">

    <!-- Page specific CSS -->
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
<div id="page-container" class="enable-cookies sidebar-o enable-page-overlay side-scroll page-header-modern main-content-narrow
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
        <div class='notifications top-right'></div>
        @yield('content')
    </main>
    <!-- END Main Container -->

    <!-- Footer -->
    <footer id="page-footer" class="opacity-0">
        <div class="content py-20 font-size-sm clearfix">
            <div class="float-right">
                Crafted with <i class="fa fa-heart text-pulse"></i> by <a class="font-w600" href="https://moinfo.co.tz" target="_blank">MoinfoTech Company Limited</a>
            </div>
            <div class="float-left">
                <a class="font-w600" href="#" target="_blank">Financial Analysis</a> &copy; <span class="js-year-copy"></span>
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
            <div class="block block-themed mb-0">
                <div class="block-header bg-gd-dusk">
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

<!-- JavaScript Libraries - Organized in proper loading order -->
<!-- Core JS Libraries -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ mix('js/codebase.app.js') }}"></script>

<!-- Plugin JS - Core functionality plugins -->
<script src="{{ asset('js/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ asset('js/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js') }}"></script>
<script src="{{ asset('js/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('js/plugins/bootstrap-notify/bootstrap-notify.min.js') }}"></script>
<script src="{{ asset('js/plugins/es6-promise/es6-promise.auto.min.js') }}"></script>

<!-- Pusher for real-time notifications -->
<script src="//js.pusher.com/3.1/pusher.min.js"></script>

<!-- DataTables JS - These should be loaded together -->
<script src="{{ asset('js/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('js/plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('js/pages/tables_datatables.js') }}"></script>

<!-- DataTables Extensions - Keep these together -->
<script src="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.print.min.js"></script>

<!-- Page JS Helpers -->
<script>jQuery(function(){ Codebase.helpers('notify'); });</script>

<!-- Custom Scripts -->
<script>
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // Enhance Comments Display
    function showComments(comment) {
        Swal.fire({
            title: 'Comments',
            text: comment,
            confirmButtonColor: '#2563eb',
            customClass: {
                popup: 'swal-wide',
                title: 'swal-title',
                content: 'swal-content'
            }
        });
    }

    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
    $(".select2").select2({
        theme: "bootstrap",
        placeholder: "Choose",
        width: 'auto',
        dropdownAutoWidth: true,
        allowClear: true,
    });

    var csrf_token = '{{csrf_token()}}';

    @if(Session::get('notifications') != null)
    @foreach(Session::get('notifications') as $notification)
    Swal.fire('{{$notification['title']}}', '{{$notification['text']}}', '{{$notification['type']}}').then((res) => {
        @endforeach
        @foreach(Session::get('notifications') as $notification)
    });
    @endforeach
        <?php  Session::put('notifications', []); ?>
    @endif
</script>

<script>
        <?php
        $timer = 1000;
        $delay = 5000;

    foreach( Auth::user()->unreadNotifications()->take(4)->get() as $notification){
        $link = $notification->data['link']
        ?>
    $.notify({
            title: "<strong>{{$notification->data['title']}}:</strong></br> ",
            message: "{{$notification->data['body']}}",
            url: "{{url("$link")}}",
        },{
            type: 'success',
            placement: {
                from: "bottom",
                align: "right"
            },
            delay: {{$delay}},
            timer: {{$timer}},
        },
    );
        <?php
        $delay+=30;
        $timer+=30;
    }
        ?>

    /**
     * Load a form into a modal
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

    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.nextElementSibling && this.nextElementSibling.classList.contains('nav-treeview')) {
                e.preventDefault();
                this.closest('.nav-item').classList.toggle('active');
            }
        });
    });
</script>

@yield('js_after')
</body>
</html>
