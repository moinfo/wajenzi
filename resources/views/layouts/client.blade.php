<!doctype html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>@yield('title', 'Client Portal') - {{ settings('SYSTEM_NAME') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="shortcut icon" href="{{ asset('media/favicons/favicon.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito+Sans:300,400,400i,600,700">

    <style>
        :root {
            --wajenzi-blue: #2563EB;
            --wajenzi-blue-dark: #1D4ED8;
            --wajenzi-green: #22C55E;
            --wajenzi-green-dark: #16A34A;
            --wajenzi-gray-50: #F8FAFC;
            --wajenzi-gray-100: #F1F5F9;
            --wajenzi-gray-200: #E2E8F0;
            --wajenzi-gray-600: #475569;
            --wajenzi-gray-700: #334155;
            --wajenzi-gray-800: #1E293B;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Nunito Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--wajenzi-gray-50) 0%, #f0f9ff 50%, #e0f2fe 100%);
            min-height: 100vh;
            margin: 0;
        }

        /* Top Navigation */
        .client-nav {
            background: white;
            border-bottom: 1px solid var(--wajenzi-gray-200);
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .client-nav .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }

        .client-nav .navbar-brand img {
            width: 36px;
            height: 36px;
            border-radius: 8px;
        }

        .client-nav .navbar-brand span {
            font-weight: 700;
            font-size: 1.125rem;
            color: var(--wajenzi-gray-800);
        }

        .client-nav .navbar-brand small {
            font-size: 0.75rem;
            color: var(--wajenzi-blue);
            font-weight: 600;
            background: #EFF6FF;
            padding: 2px 8px;
            border-radius: 4px;
        }

        .client-nav .nav-link {
            color: var(--wajenzi-gray-600);
            font-weight: 500;
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem !important;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .client-nav .nav-link:hover,
        .client-nav .nav-link.active {
            color: var(--wajenzi-blue);
            background: #EFF6FF;
        }

        .client-user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .client-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--wajenzi-blue), var(--wajenzi-green));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 0.875rem;
        }

        /* Main Content */
        .client-main {
            padding: 1.5rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Project Tabs */
        .project-tabs {
            background: white;
            border-radius: 12px;
            padding: 0.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
        }

        .project-tabs a {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            color: var(--wajenzi-gray-600);
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .project-tabs a:hover {
            background: var(--wajenzi-gray-50);
            color: var(--wajenzi-gray-800);
        }

        .project-tabs a.active {
            background: var(--wajenzi-blue);
            color: white;
        }

        /* Cards */
        .portal-card {
            background: white;
            border-radius: 16px;
            border: 1px solid var(--wajenzi-gray-200);
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            overflow: hidden;
        }

        .portal-card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--wajenzi-gray-100);
            background: var(--wajenzi-gray-50);
        }

        .portal-card-header h5 {
            margin: 0;
            font-weight: 700;
            font-size: 1rem;
            color: var(--wajenzi-gray-800);
        }

        .portal-card-body {
            padding: 1.5rem;
        }

        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 16px;
            border: 1px solid var(--wajenzi-gray-200);
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }

        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        .stat-card .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--wajenzi-gray-800);
        }

        .stat-card .stat-label {
            font-size: 0.8125rem;
            color: var(--wajenzi-gray-600);
            font-weight: 500;
        }

        /* Tables */
        .portal-table {
            width: 100%;
            margin: 0;
        }

        .portal-table thead th {
            background: var(--wajenzi-gray-50);
            color: var(--wajenzi-gray-700);
            font-weight: 600;
            font-size: 0.8125rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--wajenzi-gray-200);
        }

        .portal-table tbody td {
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: var(--wajenzi-gray-700);
            border-bottom: 1px solid var(--wajenzi-gray-100);
            vertical-align: middle;
        }

        .portal-table tbody tr:hover {
            background: var(--wajenzi-gray-50);
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-badge.success { background: #DCFCE7; color: #166534; }
        .status-badge.warning { background: #FEF3C7; color: #92400E; }
        .status-badge.danger { background: #FEE2E2; color: #991B1B; }
        .status-badge.info { background: #DBEAFE; color: #1E40AF; }
        .status-badge.secondary { background: var(--wajenzi-gray-100); color: var(--wajenzi-gray-700); }

        /* Footer */
        .client-footer {
            background: white;
            border-top: 1px solid var(--wajenzi-gray-200);
            padding: 1rem 0;
            text-align: center;
            color: var(--wajenzi-gray-600);
            font-size: 0.8125rem;
            margin-top: 3rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .client-main { padding: 1rem; }
            .project-tabs { gap: 0; }
            .project-tabs a { font-size: 0.8125rem; padding: 0.375rem 0.625rem; }
        }
    </style>
    @yield('css')
</head>
<body>
    <!-- Navigation -->
    <nav class="client-nav">
        <div class="container-fluid px-3">
            <div class="d-flex justify-content-between align-items-center py-2">
                <a href="{{ route('client.dashboard') }}" class="navbar-brand">
                    <img src="{{ asset('media/logo/wajenzilogo.png') }}" alt="Wajenzi">
                    <span>Wajenzi</span>
                    <small>Client Portal</small>
                </a>

                <div class="d-flex align-items-center gap-3">
                    <div class="client-user-info d-none d-md-flex">
                        <div class="client-avatar">
                            {{ strtoupper(substr(auth('client')->user()->first_name, 0, 1)) }}{{ strtoupper(substr(auth('client')->user()->last_name, 0, 1)) }}
                        </div>
                        <div>
                            <div style="font-weight: 600; font-size: 0.875rem; color: var(--wajenzi-gray-800);">
                                {{ auth('client')->user()->full_name }}
                            </div>
                            <div style="font-size: 0.75rem; color: var(--wajenzi-gray-600);">
                                {{ auth('client')->user()->email }}
                            </div>
                        </div>
                    </div>
                    <form action="{{ route('client.logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="d-none d-md-inline ms-1">Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="client-main">
        @yield('content')
    </div>

    <!-- Footer -->
    <footer class="client-footer">
        <p class="mb-0">&copy; {{ date('Y') }} <strong>Wajenzi</strong> Construction Management. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @yield('js')
</body>
</html>
