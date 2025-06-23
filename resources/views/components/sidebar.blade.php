<nav id="sidebar" class="wajenzi-sidebar">
    <div class="sidebar-content">
        <!-- Logo Header -->
        <div class="sidebar-header">
            <div class="logo-container">
                <img src="{{ asset('media/logo/wajenzilogo.png') }}" alt="Wajenzi Logo" class="logo-image">
                <div class="logo-text">
                    <span class="brand-name">Wajenzi</span>
                    <span class="brand-tagline">Construction Management</span>
                </div>
            </div>
        </div>
        @php
            $profile = Auth::user()->profile ?? 'media/avatars/avatar15.jpg';

        @endphp

        <!-- User Profile -->
        <div class="user-profile">
            <div class="profile-image">
                <img src="{{ asset("$profile") }}" alt="Profile">
                <div class="profile-status"></div>
            </div>
            <div class="profile-info">
                <h3 class="user-name">{{ Auth::user()->name }}</h3>
                <p class="user-role">{{ Auth::user()->roles->first()->name ?? 'User' }}</p>
            </div>
        </div>
        <style>
            .nav-treeview {
                padding-left: 1rem;
                display: none;
            }

            .nav-item.active > .nav-treeview {
                display: block;
            }

            .nav-link .right {
                float: right;
                transition: transform .3s ease-in-out;
            }

            .nav-item.active > .nav-link .right {
                transform: rotate(90deg);
            }
        </style>
        <!-- Navigation Menu -->
        <div class="sidebar-menu">
            <div class="menu-header">MENU</div>
            <ul class="nav-list">
                @foreach($user_menu as $menu)
                    @if(auth()->user()->can($menu['name']))
                        <li class="nav-item {{ request()->is($menu['route'] .'/*') ? 'active' : '' }}">
                            <a href="{{ route($menu['route']) }}"
                               class="nav-link {{ request()->is($menu['route']) ? 'active' : '' }}">
                                <i class="{{$menu['icon']}}"></i>
                                <span>{{$menu['name']}}</span>
                                @if(isset($menu['children']) && count($menu['children']) > 0)
                                    <i class="fa fa-angle-right right"></i>
                                @endif
                            </a>
                            @if(isset($menu['children']) && count($menu['children']) > 0)
                                <ul class="nav nav-treeview">
                                    @foreach($menu['children'] as $submenu)
                                        @if(auth()->user()->can($submenu['name']))
                                            <li class="nav-item">
                                                <a href="{{ route($submenu['route']) }}"
                                                   class="nav-link {{ request()->is($submenu['route']) ? 'active' : '' }}">
                                                    <i class="{{$submenu['icon']}}"></i>
                                                    <span>{{$submenu['name']}}</span>
                                                </a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
</nav>

<style>
    :root {
        --wajenzi-blue-primary: #2563EB;
        --wajenzi-blue-dark: #1D4ED8;
        --wajenzi-green: #22C55E;
        --wajenzi-green-dark: #16A34A;
        --wajenzi-gray-50: #F8FAFC;
        --wajenzi-gray-100: #F1F5F9;
        --wajenzi-gray-600: #475569;
        --wajenzi-gray-700: #334155;
        --wajenzi-gray-800: #1E293B;
        --wajenzi-gray-900: #0F172A;
        --sidebar-width: 280px;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .wajenzi-sidebar {
        width: var(--sidebar-width);
        background: linear-gradient(180deg, var(--wajenzi-gray-50) 0%, white 100%);
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        border-right: 1px solid var(--wajenzi-gray-100);
        display: flex;
        flex-direction: column;
        box-shadow: var(--shadow-lg);
        z-index: 1000;
    }

    .sidebar-content {
        height: 100%;
        display: flex;
        flex-direction: column;
        padding: 1.5rem;
    }

    /* Logo Header */
    .sidebar-header {
        padding-bottom: 2rem;
        border-bottom: 2px solid var(--wajenzi-gray-100);
        margin-bottom: 1.5rem;
    }

    .logo-container {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .logo-image {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        box-shadow: var(--shadow-sm);
        flex-shrink: 0;
    }

    .logo-text {
        display: flex;
        flex-direction: column;
    }

    .brand-name {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--wajenzi-blue-primary);
        line-height: 1.2;
    }

    .brand-tagline {
        font-size: 0.75rem;
        color: var(--wajenzi-gray-600);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* User Profile */
    .user-profile {
        background: linear-gradient(135deg, var(--wajenzi-blue-primary) 0%, var(--wajenzi-green) 100%);
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }

    .user-profile::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        z-index: 0;
    }

    .profile-image {
        position: relative;
        width: 60px;
        height: 60px;
        margin: 0 auto 1rem;
        z-index: 1;
    }

    .profile-image img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid rgba(255, 255, 255, 0.3);
        box-shadow: var(--shadow-sm);
    }

    .profile-status {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 16px;
        height: 16px;
        background: var(--wajenzi-green);
        border: 2px solid white;
        border-radius: 50%;
    }

    .profile-info {
        text-align: center;
        position: relative;
        z-index: 1;
    }

    .user-name {
        font-size: 1rem;
        font-weight: 600;
        color: white;
        margin: 0 0 0.25rem 0;
    }

    .user-role {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.8);
        margin: 0;
        font-weight: 500;
    }

    /* Navigation Menu */
    .sidebar-menu {
        flex: 1;
        overflow-y: auto;
    }

    .menu-header {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--wajenzi-gray-600);
        margin: 0 0 1rem 0;
        padding: 0 1rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .nav-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .nav-item {
        margin: 0.125rem 0;
    }

    .nav-link {
        display: flex;
        align-items: center;
        padding: 0.875rem 1rem;
        color: var(--wajenzi-gray-700);
        text-decoration: none;
        border-radius: 12px;
        transition: all 0.2s ease;
        position: relative;
        font-weight: 500;
    }

    .nav-link i {
        font-size: 1.125rem;
        margin-right: 0.875rem;
        color: var(--wajenzi-gray-600);
        transition: all 0.2s ease;
        width: 20px;
        text-align: center;
    }

    .nav-link span {
        font-size: 0.875rem;
        flex: 1;
    }

    .nav-link .right {
        margin-left: auto;
        margin-right: 0;
        font-size: 0.75rem;
        color: var(--wajenzi-gray-600);
    }

    .nav-link:hover {
        background: linear-gradient(135deg, var(--wajenzi-blue-primary) 0%, var(--wajenzi-green) 100%);
        color: white;
        transform: translateX(4px);
        box-shadow: var(--shadow-sm);
    }

    .nav-link:hover i,
    .nav-link:hover .right {
        color: white;
    }

    .nav-link.active {
        background: linear-gradient(135deg, var(--wajenzi-blue-primary) 0%, var(--wajenzi-green) 100%);
        color: white;
        box-shadow: var(--shadow-sm);
    }

    .nav-link.active i,
    .nav-link.active .right {
        color: white;
    }

    /* Submenu Styles */
    .nav-treeview {
        padding-left: 2.5rem;
        margin-top: 0.5rem;
        border-left: 2px solid var(--wajenzi-gray-100);
        margin-left: 1rem;
    }

    .nav-treeview .nav-link {
        padding: 0.625rem 1rem;
        font-size: 0.8125rem;
        background: transparent;
    }

    .nav-treeview .nav-link i {
        font-size: 0.875rem;
        margin-right: 0.625rem;
    }

    /* Responsive Design */
    @media (max-width: 991.98px) {
        .wajenzi-sidebar {
            width: 100%;
            position: relative;
            height: auto;
            box-shadow: none;
            border-right: none;
            border-bottom: 1px solid var(--wajenzi-gray-100);
        }

        .sidebar-content {
            padding: 1rem;
        }

        .sidebar-header {
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }

        .logo-image {
            width: 40px;
            height: 40px;
        }

        .brand-name {
            font-size: 1.25rem;
        }

        .user-profile {
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .profile-image {
            width: 50px;
            height: 50px;
        }

        .nav-link {
            padding: 0.75rem 0.875rem;
        }
    }

    @media (max-width: 576px) {
        .logo-container {
            gap: 0.75rem;
        }

        .logo-image {
            width: 35px;
            height: 35px;
        }

        .brand-name {
            font-size: 1.125rem;
        }

        .brand-tagline {
            font-size: 0.6875rem;
        }
    }

    /* Scrollbar Styling */
    .sidebar-menu::-webkit-scrollbar {
        width: 4px;
    }

    .sidebar-menu::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar-menu::-webkit-scrollbar-thumb {
        background: var(--wajenzi-gray-300);
        border-radius: 2px;
    }

    .sidebar-menu::-webkit-scrollbar-thumb:hover {
        background: var(--wajenzi-gray-400);
    }
</style>
