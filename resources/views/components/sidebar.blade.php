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
            /* Enhanced Navigation Styles */
            .nav-item.has-children {
                position: relative;
            }

            .nav-treeview {
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.3s ease, opacity 0.3s ease, padding 0.3s ease;
                opacity: 0;
                padding: 0;
                margin-top: 0;
                background: rgba(37, 99, 235, 0.02);
                border-radius: 0 0 12px 12px;
                margin-left: 0.5rem;
                margin-right: 0.5rem;
                border-left: 3px solid var(--wajenzi-blue-primary);
                list-style: none;
            }

            .nav-treeview.show {
                max-height: 300px;
                opacity: 1;
                padding: 0.5rem 0;
                margin-top: 0.5rem;
            }

            /* Force show for active children */
            .nav-item.has-children.active .nav-treeview,
            .nav-treeview.show {
                max-height: 300px !important;
                opacity: 1 !important;
                padding: 0.5rem 0 !important;
                margin-top: 0.5rem !important;
                display: block !important;
            }

            /* Parent Menu Items with Children */
            .nav-item.has-children > .nav-link {
                font-weight: 600;
                position: relative;
            }

            /* Parent menu item when it has active children */
            .nav-item.has-children.active > .nav-link {
                background: rgba(37, 99, 235, 0.1);
                color: var(--wajenzi-blue-primary);
                border-radius: 12px 12px 0 0;
            }

            .nav-item.has-children.active > .nav-link i,
            .nav-item.has-children.active > .nav-link .submenu-arrow {
                color: var(--wajenzi-blue-primary);
            }

            /* Submenu Arrow */
            .submenu-arrow {
                margin-left: auto !important;
                margin-right: 0 !important;
                font-size: 0.75rem !important;
                transition: transform 0.3s ease;
                width: auto !important;
                color: var(--wajenzi-gray-500);
            }

            .nav-item.active .submenu-arrow {
                transform: rotate(180deg);
            }

            /* Submenu Items */
            .submenu-item {
                margin: 0;
            }

            .submenu-link {
                padding: 0.75rem 1.5rem !important;
                border-radius: 8px !important;
                margin: 0.25rem 0.5rem;
                position: relative;
                transition: all 0.2s ease;
                background: transparent;
                font-size: 0.8125rem !important;
                color: var(--wajenzi-gray-600) !important;
            }

            .submenu-link:hover {
                background: rgba(37, 99, 235, 0.08) !important;
                transform: translateX(4px);
                color: var(--wajenzi-blue-primary) !important;
            }

            .submenu-link:hover i {
                color: var(--wajenzi-blue-primary) !important;
            }

            .submenu-link.active {
                background: linear-gradient(135deg, var(--wajenzi-blue-primary) 0%, var(--wajenzi-green) 100%) !important;
                color: white !important;
                transform: translateX(4px);
                box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
                font-weight: 600 !important;
            }

            .submenu-link.active i {
                color: white !important;
            }

            .submenu-link i {
                font-size: 0.875rem !important;
                margin-right: 0.75rem !important;
                width: 16px !important;
                color: var(--wajenzi-gray-500) !important;
            }

            /* Active submenu indicator */
            .submenu-link.active::after {
                content: '';
                position: absolute;
                right: 8px;
                top: 50%;
                transform: translateY(-50%);
                width: 3px;
                height: 16px;
                background: rgba(255, 255, 255, 0.4);
                border-radius: 2px;
            }

            /* Menu item focus states for accessibility */
            .nav-link:focus {
                outline: 2px solid var(--wajenzi-blue-primary);
                outline-offset: 2px;
            }

            .submenu-link:focus {
                outline: 2px solid var(--wajenzi-blue-primary);
                outline-offset: 2px;
            }

            /* Improve submenu visual hierarchy */
            .nav-treeview {
                border-left-color: rgba(37, 99, 235, 0.2);
                background: rgba(248, 250, 252, 0.8);
                backdrop-filter: blur(10px);
            }
        </style>
        <!-- Navigation Menu -->
        <div class="sidebar-menu">
            <div class="menu-header">MENU</div>
            <ul class="nav-list">
                @foreach($user_menu as $menu)
                    @if(auth()->user()->can($menu['name']))
                        @php
                            // Check if current route matches menu or any of its children
                            $isActive = request()->routeIs($menu['route']);
                            $hasActiveChild = false;
                            
                            if(isset($menu['children'])) {
                                foreach($menu['children'] as $submenu) {
                                    if(request()->routeIs($submenu['route'])) {
                                        $hasActiveChild = true;
                                        break;
                                    }
                                }
                            }
                            
                            $parentActive = $isActive || $hasActiveChild;
                        @endphp
                        
                        <li class="nav-item {{ $parentActive ? 'active' : '' }} {{ isset($menu['children']) && count($menu['children']) > 0 ? 'has-children' : '' }}">
                            @if(isset($menu['children']) && count($menu['children']) > 0)
                                <a href="javascript:void(0)" 
                                   class="nav-link parent-link {{ $parentActive ? 'active' : '' }}"
                                   data-toggle="submenu">
                                    <i class="{{$menu['icon']}}"></i>
                                    <span>{{$menu['name']}}</span>
                                    <i class="fa fa-chevron-down submenu-arrow"></i>
                                </a>
                            @else
                                <a href="{{ route($menu['route']) }}"
                                   class="nav-link {{ $isActive ? 'active' : '' }}">
                                    <i class="{{$menu['icon']}}"></i>
                                    <span>{{$menu['name']}}</span>
                                </a>
                            @endif
                            
                            @if(isset($menu['children']) && count($menu['children']) > 0)
                                <ul class="nav-treeview {{ $hasActiveChild ? 'show' : '' }}">
                                    @foreach($menu['children'] as $submenu)
                                        @if(auth()->user()->can($submenu['name']))
                                            <li class="nav-item submenu-item">
                                                <a href="{{ route($submenu['route']) }}"
                                                   class="nav-link submenu-link {{ request()->routeIs($submenu['route']) ? 'active' : '' }}">
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
        margin: 0.25rem 0;
        position: relative;
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
        background: transparent;
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
        color: inherit;
    }

    /* Hover States */
    .nav-link:hover {
        background: rgba(37, 99, 235, 0.08);
        color: var(--wajenzi-blue-primary);
        transform: translateX(2px);
    }

    .nav-link:hover i {
        color: var(--wajenzi-blue-primary);
    }

    /* Active States for Regular Menu Items */
    .nav-item:not(.has-children) .nav-link.active {
        background: linear-gradient(135deg, var(--wajenzi-blue-primary) 0%, var(--wajenzi-green) 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
        font-weight: 600;
    }

    .nav-item:not(.has-children) .nav-link.active i {
        color: white;
    }

    /* Active indicator for regular items */
    .nav-item:not(.has-children) .nav-link.active::after {
        content: '';
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 24px;
        background: rgba(255, 255, 255, 0.4);
        border-radius: 2px;
    }

    /* Enhanced Submenu Styles */
    .nav-treeview {
        list-style: none !important;
    }

    /* Consistent spacing for all menu items */
    .nav-item {
        border-radius: 12px;
        overflow: hidden;
    }

    /* Better visual separation between menu sections */
    .nav-item + .nav-item {
        margin-top: 0.25rem;
    }

    /* Improved contrast for better readability */
    .nav-link span {
        font-weight: 500;
        letter-spacing: 0.025em;
    }

    /* Ensure consistent icon alignment */
    .nav-link i {
        display: flex;
        align-items: center;
        justify-content: center;
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
