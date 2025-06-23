<nav id="sidebar" class="modern-sidebar">
    <div class="sidebar-content">
        <!-- Logo Header -->
        <div class="sidebar-header">
            <div class="logo-container">
                <i class="si si-users text-accent"></i>
                <span class="logo-text">
                    <span class="text-dark">Financial</span>
                    <span class="text-accent">Analysis</span>
                </span>
            </div>
        </div>
        @php
            $profile = Auth::user()->profile ?? 'media/avatars/avatar15.jpg';

        @endphp

        <!-- User Profile -->
        <div class="user-profile">
            <div class="profile-image">
                <img src="{{ asset("$profile") }}" alt="Profile" width="100">
            </div>
            <h3 class="user-name">{{ Auth::user()->name }}</h3>
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
        --sidebar-bg: #ffffff;
        --sidebar-width: 280px;
        --text-primary: #333333;
        --text-secondary: #666666;
        --accent-green: #32CD32;
        --hover-bg: #f8f9fa;
        --active-color: #32CD32;
        --border-color: #f0f0f0;
    }

    .modern-sidebar {
        width: var(--sidebar-width);
        background: var(--sidebar-bg);
        height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        border-right: 1px solid var(--border-color);
        display: flex;
        flex-direction: column;
        padding: 1.5rem;
    }

    /* Logo Header */
    .sidebar-header {
        padding-bottom: 1.5rem;
        border-bottom: 1px solid var(--border-color);
    }

    .logo-container {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .logo-container i {
        font-size: 1.5rem;
        color: var(--accent-green);
    }

    .logo-text {
        font-size: 1.25rem;
        font-weight: 600;
    }

    .text-accent {
        color: var(--accent-green);
    }

    /* User Profile */
    .user-profile {
        padding: 2rem 0;
        text-align: center;
        border-bottom: 1px solid var(--border-color);
    }

    .profile-image {
        width: 80px;
        height: 80px;
        margin: 0 auto 1rem;
    }

    .profile-image img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--border-color);
    }

    .user-name {
        font-size: 1rem;
        font-weight: 600;
        color: var(--text-primary);
        margin: 0;
    }

    /* Navigation Menu */
    .menu-header {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-secondary);
        margin: 1.5rem 0 1rem;
        padding-left: 0.5rem;
    }

    .nav-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .nav-item {
        margin: 0.25rem 0;
    }

    .nav-link {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        color: var(--text-primary);
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .nav-link i {
        font-size: 1.1rem;
        margin-right: 0.75rem;
        color: var(--text-secondary);
        transition: all 0.3s ease;
    }

    .nav-link span {
        font-size: 0.9375rem;
    }

    .nav-link:hover {
        background: var(--hover-bg);
        color: var(--active-color);
    }

    .nav-link:hover i {
        color: var(--active-color);
    }

    .nav-link.active {
        background: var(--hover-bg);
        color: var(--active-color);
    }

    .nav-link.active i {
        color: var(--active-color);
    }

    /* Responsive Design */
    @media (max-width: 991.98px) {
        .modern-sidebar {
            width: 100%;
            position: relative;
            height: auto;
            padding: 1rem;
        }

        .profile-image {
            width: 60px;
            height: 60px;
        }

        .nav-link {
            padding: 0.625rem 0.875rem;
        }
    }

    /* Animation for hover states */
    .nav-link {
        position: relative;
        overflow: hidden;
    }

    .nav-link::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        height: 2px;
        width: 0;
        background-color: var(--active-color);
        transition: width 0.3s ease;
    }

    .nav-link:hover::after,
    .nav-link.active::after {
        width: 100%;
    }

    /* Smooth transitions */
    * {
        transition: background-color 0.3s ease,
        color 0.3s ease,
        transform 0.3s ease,
        border-color 0.3s ease;
    }
</style>
