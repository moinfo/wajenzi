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

    <!-- Wajenzi Footer Styles -->
    <style>
        :root {
            --wajenzi-blue-primary: #2563EB;
            --wajenzi-blue-dark: #1D4ED8;
            --wajenzi-green: #22C55E;
            --wajenzi-green-dark: #16A34A;
            --wajenzi-gray-50: #F8FAFC;
            --wajenzi-gray-100: #F1F5F9;
            --wajenzi-gray-200: #E2E8F0;
            --wajenzi-gray-600: #475569;
            --wajenzi-gray-700: #334155;
            --wajenzi-gray-800: #1E293B;
            --wajenzi-gray-900: #0F172A;
        }

        /* Footer Styles */
        .wajenzi-footer {
            background: linear-gradient(135deg, var(--wajenzi-blue-primary) 0%, var(--wajenzi-green) 100%);
            color: white;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1020;
            padding: 0.75rem 0;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            height: 100%;
        }


        .footer-copyright p,
        .footer-credits p {
            margin: 0;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .footer-copyright strong {
            color: white;
            font-weight: 600;
        }

        .text-wajenzi-green {
            color: var(--wajenzi-green);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .developer-link {
            color: var(--wajenzi-blue-primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .developer-link:hover {
            color: var(--wajenzi-green);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .wajenzi-footer {
                padding: 0.5rem 0;
            }

            .footer-content {
                flex-direction: column;
                gap: 0.25rem;
                text-align: center;
                padding: 0 1rem;
            }

            .footer-copyright p,
            .footer-credits p {
                font-size: 0.75rem;
            }
        }

        /* Main Layout Structure */
        .wajenzi-main {
            min-height: calc(100vh - 80px - 60px);
            background: var(--wajenzi-gray-50);
            transition: margin-left 0.3s ease;
            width: calc(100vw - 280px);
            display: flex;
            justify-content: center;
            align-items: flex-start;
            box-sizing: border-box;
            padding: 0;
            padding-bottom: 60px;
        }

        .main-content {
            width: 100%;
            margin: 0 auto;
            padding: 2rem;
            box-sizing: border-box;
        }

        /* Ensure centering works for ID selector */
        #main-container {
            display: flex !important;
            justify-content: center !important;
            align-items: flex-start !important;
            padding: 0 !important;
            box-sizing: border-box !important;
        }

        /* Page Container Adjustments */
        #page-container {
            background: var(--wajenzi-gray-50);
        }

        /* Notifications positioning */
        .notifications {
            position: fixed !important;
            top: calc(80px + 1rem) !important;
            right: 1rem !important;
            z-index: 9999 !important;
        }

        /* Content spacing improvements */
        .content {
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            margin-bottom: 2rem;
        }

        .block {
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border: 1px solid var(--wajenzi-gray-200);
            margin-bottom: 2rem;
        }

        .block-header {
            background: linear-gradient(135deg, var(--wajenzi-gray-50) 0%, white 100%);
            border-bottom: 1px solid var(--wajenzi-gray-200);
            border-radius: 16px 16px 0 0;
            padding: 1.5rem 2rem;
        }

        .block-title {
            color: var(--wajenzi-gray-800);
            font-weight: 600;
            font-size: 1.125rem;
            margin: 0;
        }

        .block-content {
            padding: 2rem;
        }

        /* Dashboard Improvements */
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border: 1px solid var(--wajenzi-gray-200);
        }

        /* Table Improvements */
        .table-responsive {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            margin-bottom: 1.5rem;
        }

        table.table {
            margin-bottom: 0;
        }

        .table th {
            background: var(--wajenzi-gray-50);
            color: var(--wajenzi-gray-700);
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
            border: none;
            vertical-align: middle;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid var(--wajenzi-gray-100);
        }

        .table tbody tr:hover {
            background-color: var(--wajenzi-gray-50);
        }

        /* Badge and Status Improvements */
        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2rem;
            height: 2rem;
            border-radius: 50%;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .badge-primary {
            background: linear-gradient(135deg, var(--wajenzi-blue-primary) 0%, var(--wajenzi-green) 100%);
            color: white;
        }

        .badge-secondary {
            background: var(--wajenzi-gray-100);
            color: var(--wajenzi-gray-700);
        }

        .badge-success {
            background: var(--wajenzi-green);
            color: white;
        }

        .badge-warning {
            background: #F59E0B;
            color: white;
        }

        .badge-danger {
            background: #EF4444;
            color: white;
        }

        /* Section Headers */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--wajenzi-gray-100);
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--wajenzi-gray-800);
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Card Grid System */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .dashboard-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border: 1px solid var(--wajenzi-gray-200);
            overflow: hidden;
        }

        .dashboard-card-header {
            background: linear-gradient(135deg, var(--wajenzi-gray-50) 0%, white 100%);
            padding: 1.5rem;
            border-bottom: 1px solid var(--wajenzi-gray-200);
        }

        .dashboard-card-content {
            padding: 1.5rem;
        }

        /* List Improvements */
        .list-group-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border: none;
            border-bottom: 1px solid var(--wajenzi-gray-100);
        }

        .list-group-item:last-child {
            border-bottom: none;
        }

        .list-group-item:hover {
            background-color: var(--wajenzi-gray-50);
        }

        /* Responsive Layout */
        @media (max-width: 991.98px) {
            .wajenzi-main,
            #main-container {
                margin-left: 0;
                margin-top: 80px;
                width: 100vw;
                padding: 0;
                padding-bottom: 80px;
                display: flex;
                justify-content: center;
                align-items: flex-start;
            }

            .main-content {
                width: 100%;
                max-width: 800px;
                margin: 0 auto;
                padding: 1.5rem;
                box-sizing: border-box;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                max-width: 600px;
                padding: 1rem;
            }

            .block-header {
                padding: 1rem 1.5rem;
            }

            .block-content {
                padding: 1.5rem;
            }

            .dashboard-stats {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .table th,
            .table td {
                padding: 0.75rem;
                font-size: 0.875rem;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                max-width: none;
                padding: 0.75rem;
                margin: 0 auto;
            }

            .block-header {
                padding: 0.875rem 1rem;
            }

            .block-content {
                padding: 1rem;
            }

            .block-title {
                font-size: 1rem;
            }

            .stat-card {
                padding: 1rem;
            }

            .dashboard-card-header,
            .dashboard-card-content {
                padding: 1rem;
            }

            .table th,
            .table td {
                padding: 0.5rem;
                font-size: 0.8125rem;
            }

            .section-title {
                font-size: 1rem;
            }
        }
    </style>

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
    <main id="main-container" class="wajenzi-main">
        <div class='notifications top-right'></div>
        <div class="main-content">
            @yield('content')
        </div>
    </main>
    <!-- END Main Container -->

    <!-- Footer -->
    <footer id="page-footer" class="wajenzi-footer">
        <div class="footer-content">
            <div class="footer-copyright">
                <p>&copy; {{ date('Y') }} <strong>Wajenzi</strong> Construction Management System. All rights reserved.</p>
            </div>
            <div class="footer-credits">
                <p>Developed by <a href="https://moinfo.co.tz" target="_blank" class="developer-link">MoinfoTech Company Limited</a></p>
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

    // Enhanced menu functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Handle submenu toggling
        const submenuToggles = document.querySelectorAll('[data-toggle="submenu"]');

        submenuToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();

                const navItem = this.closest('.nav-item');
                const submenu = navItem.querySelector('.nav-treeview');

                // Close other open submenus
                document.querySelectorAll('.nav-item.has-children').forEach(item => {
                    if (item !== navItem) {
                        item.classList.remove('active');
                        const otherSubmenu = item.querySelector('.nav-treeview');
                        if (otherSubmenu) {
                            otherSubmenu.classList.remove('show');
                        }
                    }
                });

                // Toggle current submenu
                navItem.classList.toggle('active');
                if (submenu) {
                    submenu.classList.toggle('show');
                }
            });
        });

        // Auto-expand submenu if it contains active item
        const allSubmenus = document.querySelectorAll('.nav-treeview');

        allSubmenus.forEach(submenu => {
            const activeChild = submenu.querySelector('.nav-link.active');
            if (activeChild) {
                submenu.classList.add('show');
                submenu.closest('.nav-item').classList.add('active');
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    });
</script>

@yield('js_after')
</body>
</html>
