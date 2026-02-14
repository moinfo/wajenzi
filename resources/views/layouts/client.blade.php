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

    <style>
        /* ═══════════════════════════════════════════
           Mantine Design System Tokens
           Exact values from mantine.dev/styles/css-variables
           ═══════════════════════════════════════════ */
        :root {
            /* Font */
            --m-font: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji';
            --m-font-mono: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;

            /* Font sizes */
            --m-fz-xs: 0.75rem;
            --m-fz-sm: 0.875rem;
            --m-fz-md: 1rem;
            --m-fz-lg: 1.125rem;
            --m-fz-xl: 1.25rem;

            /* Line heights */
            --m-lh: 1.55;
            --m-lh-xs: 1.4;
            --m-lh-sm: 1.45;

            /* Heading sizes */
            --m-h1-fz: 2.125rem;
            --m-h2-fz: 1.625rem;
            --m-h3-fz: 1.375rem;
            --m-h4-fz: 1.125rem;
            --m-h5-fz: 1rem;
            --m-h6-fz: 0.875rem;

            /* Blue (primary) */
            --m-blue-0: #e7f5ff;
            --m-blue-1: #d0ebff;
            --m-blue-2: #a5d8ff;
            --m-blue-3: #74c0fc;
            --m-blue-4: #4dabf7;
            --m-blue-5: #339af0;
            --m-blue-6: #228be6;
            --m-blue-7: #1c7ed6;
            --m-blue-8: #1971c2;
            --m-blue-9: #1864ab;

            /* Teal */
            --m-teal-0: #e6fcf5;
            --m-teal-1: #c3fae8;
            --m-teal-6: #12b886;
            --m-teal-9: #099268;

            /* Green */
            --m-green-0: #ebfbee;
            --m-green-6: #40c057;

            /* Violet */
            --m-violet-0: #f3f0ff;
            --m-violet-6: #7950f2;
            --m-violet-9: #6741d9;

            /* Orange */
            --m-orange-0: #fff4e6;
            --m-orange-6: #fd7e14;

            /* Red */
            --m-red-0: #fff5f5;
            --m-red-6: #fa5252;
            --m-red-9: #e03131;

            /* Yellow */
            --m-yellow-0: #fff9db;
            --m-yellow-6: #fab005;
            --m-yellow-9: #e67700;

            /* Gray */
            --m-gray-0: #f8f9fa;
            --m-gray-1: #f1f3f5;
            --m-gray-2: #e9ecef;
            --m-gray-3: #dee2e6;
            --m-gray-4: #ced4da;
            --m-gray-5: #adb5bd;
            --m-gray-6: #868e96;
            --m-gray-7: #495057;
            --m-gray-8: #343a40;
            --m-gray-9: #212529;

            /* Spacing (exact Mantine values) */
            --m-xs: 0.625rem;
            --m-sm: 0.75rem;
            --m-md: 1rem;
            --m-lg: 1.25rem;
            --m-xl: 2rem;

            /* Radius */
            --m-radius-xs: 0.125rem;
            --m-radius-sm: 0.25rem;
            --m-radius-md: 0.5rem;
            --m-radius-lg: 1rem;
            --m-radius-xl: 2rem;

            /* Shadows (exact Mantine values) */
            --m-shadow-xs: 0 1px 3px rgba(0,0,0,.05), 0 1px 2px rgba(0,0,0,.1);
            --m-shadow-sm: 0 1px 3px rgba(0,0,0,.05), rgba(0,0,0,.05) 0 10px 15px -5px, rgba(0,0,0,.04) 0 7px 7px -5px;
            --m-shadow-md: 0 1px 3px rgba(0,0,0,.05), rgba(0,0,0,.05) 0 20px 25px -5px, rgba(0,0,0,.04) 0 10px 10px -5px;
            --m-shadow-lg: 0 1px 3px rgba(0,0,0,.05), rgba(0,0,0,.05) 0 28px 23px -7px, rgba(0,0,0,.04) 0 12px 12px -7px;

            /* Layout */
            --m-navbar-w: 300px;
            --m-header-h: 60px;
        }

        /* ═══════════════════════════════════════════
           Base
           ═══════════════════════════════════════════ */
        *, *::before, *::after { box-sizing: border-box; }

        body {
            font-family: var(--m-font);
            font-size: var(--m-fz-md);
            line-height: var(--m-lh);
            background-color: #fff;
            color: var(--m-gray-9);
            margin: 0;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* ═══════════════════════════════════════════
           AppShell
           ═══════════════════════════════════════════ */
        .m-app-shell {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .m-app-shell-body {
            display: flex;
            flex: 1;
        }

        /* ── Header ── */
        .m-header {
            height: var(--m-header-h);
            background: #fff;
            border-bottom: 1px solid var(--m-gray-3);
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            padding: 0 var(--m-md);
        }

        .m-header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .m-header-brand {
            display: flex;
            align-items: center;
            gap: var(--m-sm);
            text-decoration: none;
            color: inherit;
        }

        .m-header-brand img { width: 30px; height: 30px; }

        .m-header-brand-name {
            font-size: var(--m-fz-lg);
            font-weight: 700;
            color: var(--m-gray-9);
        }

        .m-code {
            font-family: var(--m-font-mono);
            font-size: var(--m-fz-xs);
            font-weight: 700;
            padding: 0.125rem 0.4375rem;
            border-radius: var(--m-radius-sm);
            background: var(--m-gray-0);
            color: var(--m-gray-7);
            line-height: var(--m-lh);
        }

        .m-header-right {
            display: flex;
            align-items: center;
            gap: var(--m-sm);
        }

        /* ── Navbar (300px, Mantine NavbarNested) ── */
        .m-navbar {
            width: var(--m-navbar-w);
            height: calc(100vh - var(--m-header-h));
            position: sticky;
            top: var(--m-header-h);
            background: #fff;
            border-right: 1px solid var(--m-gray-3);
            display: flex;
            flex-direction: column;
            padding: var(--m-md);
            padding-bottom: 0;
            flex-shrink: 0;
            overflow: hidden;
        }

        .m-navbar-main {
            flex: 1;
            margin-left: calc(var(--m-md) * -1);
            margin-right: calc(var(--m-md) * -1);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--m-gray-3) transparent;
        }

        .m-navbar-main::-webkit-scrollbar { width: 6px; }
        .m-navbar-main::-webkit-scrollbar-thumb { background: var(--m-gray-3); border-radius: 3px; }

        .m-navbar-links {
            padding: var(--m-xl) 0;
        }

        .m-navbar-footer {
            margin-left: calc(var(--m-md) * -1);
            margin-right: calc(var(--m-md) * -1);
            border-top: 1px solid var(--m-gray-3);
        }

        /* ── NavLink control (LinksGroup) ── */
        .m-control {
            display: flex;
            align-items: center;
            width: 100%;
            padding: var(--m-xs) var(--m-md);
            font-size: var(--m-fz-sm);
            font-weight: 500;
            color: var(--m-gray-9);
            text-decoration: none;
            border: none;
            background: none;
            cursor: pointer;
            border-radius: 0;
            transition: background-color 150ms ease;
        }

        .m-control:hover {
            background-color: var(--m-gray-0);
        }

        .m-control.active {
            background-color: var(--m-blue-0);
            color: var(--m-blue-7);
        }

        /* ThemeIcon (light variant) */
        .m-theme-icon {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--m-radius-sm);
            font-size: 1rem;
            flex-shrink: 0;
            margin-right: var(--m-md);
        }

        .m-theme-icon-blue { background: var(--m-blue-0); color: var(--m-blue-6); }
        .m-theme-icon-teal { background: var(--m-teal-0); color: var(--m-teal-6); }
        .m-theme-icon-violet { background: var(--m-violet-0); color: var(--m-violet-6); }
        .m-theme-icon-orange { background: var(--m-orange-0); color: var(--m-orange-6); }
        .m-theme-icon-red { background: var(--m-red-0); color: var(--m-red-6); }
        .m-theme-icon-gray { background: var(--m-gray-1); color: var(--m-gray-6); }

        .m-control-label { flex: 1; text-align: left; }

        .m-control-chevron {
            font-size: 0.875rem;
            color: var(--m-gray-5);
            transition: transform 200ms ease;
            margin-left: auto;
        }

        .m-control-chevron.open {
            transform: rotate(90deg);
        }

        /* Nested links (border-left pattern from Mantine) */
        .m-link {
            display: block;
            padding: var(--m-xs) var(--m-md);
            padding-left: var(--m-md);
            margin-left: var(--m-xl);
            font-size: var(--m-fz-sm);
            font-weight: 500;
            color: var(--m-gray-7);
            text-decoration: none;
            border-left: 1px solid var(--m-gray-3);
            transition: background-color 150ms ease, color 150ms ease;
        }

        .m-link:hover {
            background-color: var(--m-gray-0);
            color: var(--m-gray-9);
        }

        .m-link.active {
            color: var(--m-blue-7);
            background-color: var(--m-blue-0);
            border-left-color: var(--m-blue-6);
        }

        /* Section label */
        .m-navbar-label {
            font-size: var(--m-fz-xs);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--m-gray-5);
            padding: var(--m-xs) var(--m-md);
        }

        /* UserButton (Mantine pattern) */
        .m-user-btn {
            display: flex;
            align-items: center;
            width: 100%;
            padding: var(--m-md);
            text-decoration: none;
            color: var(--m-gray-9);
            transition: background-color 150ms ease;
        }

        .m-user-btn:hover { background-color: var(--m-gray-0); }

        .m-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--m-blue-6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 600;
            font-size: var(--m-fz-sm);
            flex-shrink: 0;
        }

        .m-avatar-sm {
            width: 28px;
            height: 28px;
            font-size: var(--m-fz-xs);
        }

        .m-user-info {
            flex: 1;
            min-width: 0;
            margin-left: var(--m-sm);
        }

        .m-user-name {
            font-size: var(--m-fz-sm);
            font-weight: 500;
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .m-user-email {
            font-size: var(--m-fz-xs);
            color: var(--m-gray-6);
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* ── Main content ── */
        .m-main {
            flex: 1;
            min-width: 0;
            padding: calc(var(--m-xl) * 1.5);
            max-width: 1100px;
        }

        /* ── Burger (mobile) ── */
        .m-burger {
            display: none;
            background: none;
            border: none;
            padding: 0.5rem;
            font-size: 1.25rem;
            color: var(--m-gray-7);
            cursor: pointer;
            margin-right: var(--m-xs);
        }

        .m-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.5);
            z-index: 99;
        }

        /* ═══════════════════════════════════════════
           Component Styles
           ═══════════════════════════════════════════ */

        /* Paper (Mantine Paper withBorder) */
        .m-paper {
            background: #fff;
            border: 1px solid var(--m-gray-3);
            border-radius: var(--m-radius-md);
        }

        .m-paper-shadow { box-shadow: var(--m-shadow-xs); }

        .m-paper-header {
            padding: var(--m-md) var(--m-lg);
            border-bottom: 1px solid var(--m-gray-3);
        }

        .m-paper-header h5 {
            margin: 0;
            font-weight: 600;
            font-size: var(--m-fz-sm);
            color: var(--m-gray-8);
        }

        .m-paper-body { padding: var(--m-lg); }

        /* Stat (Mantine StatsGrid pattern) */
        .m-stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: var(--m-md);
        }

        .m-stat {
            background: #fff;
            border: 1px solid var(--m-gray-3);
            border-radius: var(--m-radius-md);
            padding: var(--m-md);
        }

        .m-stat-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5625rem;
        }

        .m-stat-title {
            font-size: var(--m-fz-xs);
            font-weight: 700;
            text-transform: uppercase;
            color: var(--m-gray-6);
            letter-spacing: 0.01em;
        }

        .m-stat-icon {
            color: var(--m-gray-4);
            font-size: 1.375rem;
        }

        .m-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
            color: var(--m-gray-9);
        }

        .m-stat-desc {
            font-size: var(--m-fz-xs);
            color: var(--m-gray-6);
            margin-top: 0.4375rem;
        }

        .m-stat-diff {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: var(--m-fz-sm);
            font-weight: 500;
            line-height: 1;
        }

        .m-stat-diff.up { color: var(--m-teal-6); }
        .m-stat-diff.down { color: var(--m-red-6); }

        /* Badge (Mantine Badge) */
        .m-badge {
            display: inline-flex;
            align-items: center;
            height: 1.375rem;
            padding: 0 0.5rem;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            border-radius: var(--m-radius-xl);
            line-height: 1;
        }

        .m-badge-blue { background: var(--m-blue-0); color: var(--m-blue-7); }
        .m-badge-teal, .m-badge-green { background: var(--m-teal-0); color: var(--m-teal-9); }
        .m-badge-yellow { background: var(--m-yellow-0); color: var(--m-yellow-9); }
        .m-badge-red { background: var(--m-red-0); color: var(--m-red-9); }
        .m-badge-violet { background: var(--m-violet-0); color: var(--m-violet-9); }
        .m-badge-gray { background: var(--m-gray-1); color: var(--m-gray-6); }
        .m-badge-orange { background: var(--m-orange-0); color: var(--m-orange-6); }

        /* Table (Mantine Table) */
        .m-table { width: 100%; border-collapse: collapse; }

        .m-table thead th {
            color: var(--m-gray-7);
            font-weight: 700;
            font-size: var(--m-fz-xs);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.4375rem var(--m-md);
            border-bottom: 1px solid var(--m-gray-3);
            text-align: left;
        }

        .m-table tbody td {
            padding: 0.4375rem var(--m-md);
            font-size: var(--m-fz-sm);
            color: var(--m-gray-7);
            border-bottom: 1px solid var(--m-gray-2);
            vertical-align: middle;
        }

        .m-table tbody tr:last-child td { border-bottom: none; }
        .m-table tbody tr:hover { background: var(--m-gray-0); }
        .m-table tfoot td, .m-table tfoot th { border-top: 1px solid var(--m-gray-3); }

        /* Button (Mantine Button) */
        .m-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            height: 2.25rem;
            padding: 0 1.125rem;
            font-size: var(--m-fz-sm);
            font-weight: 600;
            border-radius: var(--m-radius-sm);
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background 150ms ease;
            font-family: var(--m-font);
        }

        .m-btn-filled { background: var(--m-blue-6); color: #fff; }
        .m-btn-filled:hover { background: var(--m-blue-7); color: #fff; }
        .m-btn-light { background: var(--m-blue-0); color: var(--m-blue-6); }
        .m-btn-light:hover { background: var(--m-blue-1); color: var(--m-blue-7); }
        .m-btn-outline { background: transparent; color: var(--m-blue-6); border: 1px solid var(--m-blue-6); }
        .m-btn-outline:hover { background: var(--m-blue-0); }
        .m-btn-subtle { background: transparent; color: var(--m-gray-7); }
        .m-btn-subtle:hover { background: var(--m-gray-0); }
        .m-btn-fullwidth { width: 100%; }
        .m-btn-sm { height: 1.875rem; padding: 0 0.875rem; font-size: var(--m-fz-xs); }

        /* Tabs (Mantine Tabs, default variant) */
        .m-tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid var(--m-gray-3);
            margin-bottom: var(--m-lg);
            overflow-x: auto;
        }

        .m-tab {
            padding: var(--m-sm) var(--m-md);
            font-size: var(--m-fz-sm);
            font-weight: 500;
            color: var(--m-gray-6);
            text-decoration: none;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            white-space: nowrap;
            transition: color 150ms ease, border-color 150ms ease;
        }

        .m-tab:hover { color: var(--m-gray-9); }
        .m-tab.active { color: var(--m-blue-6); border-bottom-color: var(--m-blue-6); }

        /* Text utilities */
        .m-dimmed { color: var(--m-gray-6); }
        .m-text-xs { font-size: var(--m-fz-xs); }
        .m-text-sm { font-size: var(--m-fz-sm); }
        .m-text-lg { font-size: var(--m-fz-lg); }
        .m-fw-500 { font-weight: 500; }
        .m-fw-600 { font-weight: 600; }
        .m-fw-700 { font-weight: 700; }

        /* Title (Mantine Title) */
        .m-title { font-weight: 700; line-height: 1.3; color: var(--m-gray-9); margin: 0; }
        .m-title-1 { font-size: var(--m-h1-fz); }
        .m-title-2 { font-size: var(--m-h2-fz); line-height: 1.35; }
        .m-title-3 { font-size: var(--m-h3-fz); line-height: 1.4; }
        .m-title-4 { font-size: var(--m-h4-fz); line-height: 1.45; }

        /* Divider */
        .m-divider { border: none; border-top: 1px solid var(--m-gray-3); margin: var(--m-md) 0; }

        /* Group (flex row) */
        .m-group {
            display: flex;
            align-items: center;
            gap: var(--m-md);
        }

        /* Text (dimmed descriptors) */
        .m-text-dimmed { color: var(--m-gray-6); }

        /* ═══════════════════════════════════════════
           Responsive
           ═══════════════════════════════════════════ */
        @media (max-width: 992px) {
            .m-navbar {
                position: fixed;
                left: -100%;
                top: var(--m-header-h);
                z-index: 100;
                transition: left 250ms ease;
                height: calc(100vh - var(--m-header-h));
                box-shadow: var(--m-shadow-lg);
            }
            .m-navbar.open { left: 0; }
            .m-overlay.open { display: block; z-index: 99; }
            .m-burger { display: block; }
            .m-main { padding: var(--m-md); }
            .m-stat-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 576px) {
            .m-stat-grid { grid-template-columns: 1fr; }
        }
    </style>
    @yield('css')
</head>
<body>
    @php
        $clientUser = auth('client')->user();
        $initials = strtoupper(substr($clientUser->first_name, 0, 1)) . strtoupper(substr($clientUser->last_name, 0, 1));
    @endphp

    <div class="m-app-shell">
        {{-- ── Header ── --}}
        <header class="m-header">
            <div class="m-header-inner">
                <div style="display: flex; align-items: center;">
                    <button class="m-burger" onclick="toggleSidebar()" aria-label="Toggle navigation">
                        <i class="fas fa-bars"></i>
                    </button>
                    <a href="{{ route('client.dashboard') }}" class="m-header-brand">
                        <img src="{{ asset('media/logo/wajenzilogo.png') }}" alt="Wajenzi">
                        <span class="m-header-brand-name">Wajenzi</span>
                    </a>
                    <span class="m-code" style="margin-left: var(--m-sm);">Client Portal</span>
                </div>

                <div class="m-header-right">
                    <div class="m-group d-none d-md-flex" style="gap: var(--m-sm);">
                        <div class="m-avatar m-avatar-sm">{{ $initials }}</div>
                        <span class="m-fw-500 m-text-sm">{{ $clientUser->full_name }}</span>
                    </div>
                    <form action="{{ route('client.logout') }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="m-btn m-btn-subtle m-btn-sm">
                            <i class="fas fa-sign-out-alt"></i>
                            <span class="d-none d-md-inline">Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <div class="m-app-shell-body">
            {{-- ── Overlay (mobile) ── --}}
            <div class="m-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

            {{-- ── Navbar (Mantine NavbarNested pattern) ── --}}
            <nav class="m-navbar" id="sidebar">
                {{-- Navbar main (scrollable) --}}
                <div class="m-navbar-main">
                    <div class="m-navbar-links">
                        {{-- Dashboard --}}
                        <a href="{{ route('client.dashboard') }}" class="m-control {{ request()->routeIs('client.dashboard') ? 'active' : '' }}">
                            <span class="m-theme-icon m-theme-icon-blue"><i class="fas fa-gauge-high" style="font-size: 1rem;"></i></span>
                            <span class="m-control-label">Dashboard</span>
                        </a>

                        {{-- Projects --}}
                        @isset($sidebarProjects)
                            @foreach($sidebarProjects as $navProject)
                                @php
                                    $isActiveProject = request()->routeIs('client.project.*') && request()->route('id') == $navProject->id;
                                    $statusColors = ['APPROVED' => 'teal', 'PENDING' => 'orange', 'COMPLETED' => 'blue'];
                                    $iconColor = $statusColors[$navProject->status] ?? 'gray';
                                @endphp

                                <a href="{{ route('client.project.show', $navProject->id) }}"
                                   class="m-control {{ $isActiveProject && request()->routeIs('client.project.show') ? 'active' : '' }}"
                                   style="{{ $isActiveProject ? 'font-weight: 600;' : '' }}">
                                    <span class="m-theme-icon m-theme-icon-{{ $iconColor }}">
                                        <i class="fas fa-building" style="font-size: 1rem;"></i>
                                    </span>
                                    <span class="m-control-label">{{ $navProject->project_name }}</span>
                                    @if($isActiveProject)
                                        <i class="fas fa-chevron-right m-control-chevron open"></i>
                                    @endif
                                </a>

                                {{-- Nested sub-links with left border --}}
                                @if($isActiveProject)
                                    <a href="{{ route('client.project.show', $navProject->id) }}" class="m-link {{ request()->routeIs('client.project.show') ? 'active' : '' }}">Overview</a>
                                    <a href="{{ route('client.project.boq', $navProject->id) }}" class="m-link {{ request()->routeIs('client.project.boq') ? 'active' : '' }}">BOQ</a>
                                    <a href="{{ route('client.project.schedule', $navProject->id) }}" class="m-link {{ request()->routeIs('client.project.schedule') ? 'active' : '' }}">Schedule</a>
                                    <a href="{{ route('client.project.financials', $navProject->id) }}" class="m-link {{ request()->routeIs('client.project.financials') ? 'active' : '' }}">Financials</a>
                                    <a href="{{ route('client.project.documents', $navProject->id) }}" class="m-link {{ request()->routeIs('client.project.documents') ? 'active' : '' }}">Documents</a>
                                    <a href="{{ route('client.project.reports', $navProject->id) }}" class="m-link {{ request()->routeIs('client.project.reports') ? 'active' : '' }}">Reports</a>
                                @endif
                            @endforeach

                            @if($sidebarProjects->isEmpty())
                                <div style="padding: var(--m-xs) var(--m-md); font-size: var(--m-fz-sm); color: var(--m-gray-5);">
                                    No projects assigned
                                </div>
                            @endif
                        @endisset
                    </div>
                </div>

                {{-- Navbar footer (UserButton) --}}
                <div class="m-navbar-footer">
                    <div class="m-user-btn">
                        <div class="m-avatar">{{ $initials }}</div>
                        <div class="m-user-info">
                            <div class="m-user-name">{{ $clientUser->full_name }}</div>
                            <div class="m-user-email">{{ $clientUser->email ?? $clientUser->phone_number }}</div>
                        </div>
                        <i class="fas fa-chevron-right" style="font-size: 0.875rem; color: var(--m-gray-5); margin-left: auto;"></i>
                    </div>
                </div>
            </nav>

            {{-- ── Main content ── --}}
            <main class="m-main">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('open');
        }
    </script>
    @yield('js')
</body>
</html>
