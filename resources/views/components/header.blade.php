<?php
use Illuminate\Support\Facades\Auth;
$notification_unread = \App\Models\Notification::getLatestUnreadNotifications(Auth::user()->id);
$count_notification_unread = \App\Models\Notification::getUnreadNotificationsCount(Auth::user()->id);
//                    dump($notification_unread);
?>
<header id="page-header" class="wajenzi-header">
    <!-- Header Content -->
    <div class="content-header">
        <!-- Left Section -->
        <div class="header-left">
            <!-- Toggle Sidebar -->
            <button type="button" class="header-btn sidebar-toggle" data-toggle="layout" data-action="sidebar_toggle" 
                    aria-label="Toggle navigation menu" title="Open/close navigation">
                <span class="hamburger-icon">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </span>
            </button>
            
            <!-- Page Title -->
            <div class="page-title-section">
                <h1 class="page-title">{{ $page_title ?? 'Dashboard' }}</h1>
                <span class="page-subtitle">{{ $page_subtitle ?? 'Welcome back!' }}</span>
            </div>
        </div>

        <!-- Right Section -->
        <div class="header-right">
            <!-- Search -->
            <div class="header-search">
                <form action="/dashboard" method="POST" class="search-form">
                    @csrf
                    <div class="search-input-wrapper">
                        <i class="fa fa-search search-icon"></i>
                        <input type="text" class="search-input" name="search" placeholder="Search everything..." autocomplete="off">
                        <div class="search-scope" title="Choose what to search">
                            <select name="scope" class="search-scope-select" aria-label="Search scope filter">
                                <option value="all">Everything</option>
                                <option value="projects">Projects</option>
                                <option value="tasks">Tasks</option>
                                <option value="team">Team Members</option>
                                <option value="files">Documents</option>
                            </select>
                            <i class="fa fa-chevron-down search-scope-icon"></i>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Quick Actions -->
            <div class="header-actions">
                <!-- Add New Button -->
                <div class="header-dropdown">
                    <button type="button" class="header-btn action-btn" data-toggle="dropdown">
                        <i class="fa fa-plus"></i>
                        <span class="btn-text">New</span>
                    </button>
                    <div class="dropdown-menu action-dropdown dropdown-menu-right">
                        <h6 class="dropdown-header">Quick Actions</h6>
                        <a class="dropdown-item" href="#">
                            <i class="fa fa-project-diagram"></i> New Project
                        </a>
                        <a class="dropdown-item" href="#">
                            <i class="fa fa-tasks"></i> New Task
                        </a>
                        <a class="dropdown-item" href="#">
                            <i class="fa fa-users"></i> Add Team Member
                        </a>
                        <a class="dropdown-item" href="#">
                            <i class="fa fa-file-invoice"></i> Create Invoice
                        </a>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="header-dropdown">
                <button type="button" class="header-btn notification-btn" id="page-header-notifications" data-toggle="dropdown">
                    <i class="fa fa-bell"></i>
                    @if($count_notification_unread > 0)
                        <span class="notification-badge">{{$count_notification_unread}}</span>
                    @endif
                </button>
                <div class="dropdown-menu notification-dropdown dropdown-menu-right" aria-labelledby="page-header-notifications">
                    <h6 class="dropdown-header">
                        Notifications
                        @if($count_notification_unread > 0)
                            <span class="badge">{{$count_notification_unread}} new</span>
                        @endif
                    </h6>
                    <div class="notifications-list">
                        @foreach(Auth::user()->unreadNotifications()->take(3)->get() as $notification)
                            <a class="notification-item" href="{{url($notification->data['link'])}}">
                                <div class="notification-icon">
                                    <i class="fa fa-bell"></i>
                                </div>
                                <div class="notification-content">
                                    <h6>{{$notification->data['title']}}</h6>
                                    <p>{{$notification->data['body']}}</p>
                                    <span class="notification-time">{{$notification->updated_at->diffForHumans()}}</span>
                                </div>
                            </a>
                        @endforeach
                        @if($count_notification_unread == 0)
                            <div class="no-notifications">
                                <i class="fa fa-check-circle"></i>
                                <p>All caught up!</p>
                            </div>
                        @endif
                    </div>
                    <a class="dropdown-item text-center view-all" href="{{ route('user_notifications') }}">
                        View All Notifications
                    </a>
                </div>
            </div>

            <!-- User Dropdown -->
            <div class="header-dropdown">
                <button type="button" class="header-btn user-btn" id="page-header-user-dropdown" data-toggle="dropdown">
                    @php
                        $profile = Auth::user()->profile ?? 'media/avatars/avatar15.jpg';
                    @endphp
                    <img src="{{ asset("$profile") }}" alt="Profile" class="user-avatar">
                    <div class="user-info">
                        <span class="user-name">{{ Auth::user()->name }}</span>
                        <span class="user-role">{{ Auth::user()->roles->first()->name ?? 'User' }}</span>
                    </div>
                    <i class="fa fa-chevron-down"></i>
                </button>
                <div class="dropdown-menu user-dropdown dropdown-menu-right" aria-labelledby="page-header-user-dropdown">
                    <a class="dropdown-item" href="{{ route('user_profile') }}">
                        <i class="si si-user"></i> Profile
                    </a>
                    <a class="dropdown-item" href="{{ route('user_inbox') }}">
                        <i class="fa fa-envelope"></i> Inbox
                    </a>
                    <a class="dropdown-item" href="{{ route('user_settings') }}">
                        <i class="fa fa-cog"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fa fa-sign-out-alt"></i> Sign Out
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay (hidden by default) -->
    <div id="page-header-loader" class="modern-loader" style="display: none;">
        <div class="loader-content">
            <div class="loader-spinner"></div>
            <span class="loader-text">Loading...</span>
        </div>
    </div>
</header>

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
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    /* Header Container */
    .wajenzi-header {
        background: linear-gradient(135deg, var(--wajenzi-gray-50) 0%, white 100%);
        height: 80px;
        border-bottom: 1px solid var(--wajenzi-gray-200);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1030;
        box-shadow: var(--shadow-sm);
        backdrop-filter: blur(8px);
    }

    .content-header {
        height: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 2rem;
        max-width: 100%;
        min-height: 80px;
        box-sizing: border-box;
    }

    /* Header Sections */
    .header-left {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        flex: 1;
        height: 100%;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-shrink: 0;
        height: 100%;
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        height: 100%;
    }

    /* Buttons */
    .header-btn {
        background: transparent;
        border: none;
        padding: 0.75rem;
        border-radius: 12px;
        color: var(--wajenzi-gray-700);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
        font-weight: 500;
        font-size: 0.875rem;
    }

    .header-btn:hover {
        background: var(--wajenzi-gray-100);
        color: var(--wajenzi-blue-primary);
        transform: translateY(-1px);
    }

    .header-btn i {
        font-size: 1.125rem;
    }

    /* Sidebar Toggle */
    .sidebar-toggle {
        background: var(--wajenzi-gray-100);
        color: var(--wajenzi-gray-700);
        padding: 0.875rem;
        border-radius: 12px;
        position: relative;
        min-width: 44px;
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .sidebar-toggle:hover {
        background: linear-gradient(135deg, var(--wajenzi-blue-primary) 0%, var(--wajenzi-green) 100%);
        color: white;
        transform: translateY(-1px);
        box-shadow: var(--shadow-md);
    }

    .sidebar-toggle:active {
        transform: translateY(0);
    }

    /* Hamburger Animation */
    .hamburger-icon {
        display: flex;
        flex-direction: column;
        gap: 3px;
        width: 18px;
        height: 14px;
    }

    .hamburger-line {
        display: block;
        width: 100%;
        height: 2px;
        background-color: currentColor;
        border-radius: 1px;
        transition: all 0.3s ease;
        transform-origin: center;
    }

    .sidebar-toggle:hover .hamburger-line:nth-child(1) {
        transform: translateY(1px);
    }

    .sidebar-toggle:hover .hamburger-line:nth-child(3) {
        transform: translateY(-1px);
    }

    /* Active state animation (when sidebar is open) */
    .sidebar-toggle.active .hamburger-line:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px);
    }

    .sidebar-toggle.active .hamburger-line:nth-child(2) {
        opacity: 0;
        transform: scaleX(0);
    }

    .sidebar-toggle.active .hamburger-line:nth-child(3) {
        transform: rotate(-45deg) translate(5px, -5px);
    }

    /* Page Title Section */
    .page-title-section {
        display: flex;
        flex-direction: column;
        gap: 0.125rem;
        justify-content: center;
        height: 100%;
    }

    .page-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--wajenzi-gray-800);
        margin: 0;
        line-height: 1.3;
    }

    .page-subtitle {
        font-size: 0.8125rem;
        color: var(--wajenzi-gray-600);
        font-weight: 500;
        line-height: 1.2;
    }

    /* Search Bar */
    .header-search {
        min-width: 320px;
        max-width: 400px;
        display: flex;
        align-items: center;
        height: 100%;
    }

    .search-form {
        width: 100%;
        height: 44px;
        display: flex;
        align-items: center;
    }

    .search-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        background: white;
        border: 2px solid var(--wajenzi-gray-200);
        border-radius: 12px;
        transition: all 0.2s ease;
        box-shadow: var(--shadow-sm);
        height: 44px;
        width: 100%;
    }

    .search-input-wrapper:focus-within {
        border-color: var(--wajenzi-blue-primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .search-icon {
        color: var(--wajenzi-gray-600);
        margin: 0 0.75rem;
        font-size: 0.875rem;
    }

    .search-input {
        border: none;
        outline: none;
        background: transparent;
        flex: 1;
        font-size: 0.875rem;
        color: var(--wajenzi-gray-800);
        padding: 0.75rem 0;
        min-width: 0;
    }

    .search-input::placeholder {
        color: var(--wajenzi-gray-600);
    }

    .search-scope {
        border-left: 1px solid var(--wajenzi-gray-200);
        padding-left: 0.75rem;
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .search-scope-select {
        border: none;
        outline: none;
        background: transparent;
        color: var(--wajenzi-gray-600);
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.5rem 0;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }

    .search-scope-select:focus {
        color: var(--wajenzi-blue-primary);
    }

    .search-scope-select:hover {
        color: var(--wajenzi-blue-primary);
    }

    .search-scope-icon {
        font-size: 0.625rem;
        color: var(--wajenzi-gray-500);
        pointer-events: none;
        transition: color 0.2s ease;
    }

    .search-scope:hover .search-scope-icon {
        color: var(--wajenzi-blue-primary);
    }

    /* Action Button */
    .action-btn {
        background: linear-gradient(135deg, var(--wajenzi-blue-primary) 0%, var(--wajenzi-green) 100%);
        color: white;
        border-radius: 12px;
        padding: 0.75rem 1rem;
        font-weight: 600;
    }

    .action-btn:hover {
        background: linear-gradient(135deg, var(--wajenzi-blue-dark) 0%, var(--wajenzi-green-dark) 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }

    /* Notification Button */
    .notification-btn {
        position: relative;
    }

    .notification-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        background: var(--wajenzi-green);
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.5rem;
        border-radius: 10px;
        min-width: 20px;
        text-align: center;
        line-height: 1;
    }

    /* User Button */
    .user-btn {
        background: var(--wajenzi-gray-50);
        border: 1px solid var(--wajenzi-gray-200);
        padding: 0.5rem 1rem;
        border-radius: 16px;
        gap: 0.75rem;
    }

    .user-btn:hover {
        background: white;
        border-color: var(--wajenzi-blue-primary);
        transform: none;
    }

    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--wajenzi-gray-200);
    }

    .user-info {
        display: flex;
        flex-direction: column;
        text-align: left;
    }

    .user-name {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--wajenzi-gray-800);
        line-height: 1.2;
    }

    .user-role {
        font-size: 0.75rem;
        color: var(--wajenzi-gray-600);
        line-height: 1.2;
    }

    /* Dropdowns */
    .dropdown-menu {
        background: white;
        border: 1px solid var(--wajenzi-gray-200);
        border-radius: 16px;
        box-shadow: var(--shadow-lg);
        padding: 0.75rem 0;
        min-width: 280px;
        margin-top: 0.5rem;
    }

    .dropdown-header {
        color: var(--wajenzi-gray-600);
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.75rem 1rem 0.5rem;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid var(--wajenzi-gray-100);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .dropdown-header .badge {
        background: var(--wajenzi-green);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 8px;
        font-size: 0.6875rem;
        font-weight: 600;
    }

    .dropdown-item {
        padding: 0.75rem 1rem;
        color: var(--wajenzi-gray-700);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: all 0.2s ease;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .dropdown-item:hover {
        background: var(--wajenzi-gray-50);
        color: var(--wajenzi-blue-primary);
        transform: translateX(4px);
    }

    .dropdown-item i {
        font-size: 1rem;
        color: var(--wajenzi-gray-600);
        width: 20px;
        text-align: center;
    }

    .dropdown-divider {
        height: 1px;
        background: var(--wajenzi-gray-100);
        margin: 0.5rem 0;
    }

    /* Action Dropdown */
    .action-dropdown {
        min-width: 220px;
    }

    .action-dropdown .dropdown-item:hover {
        background: linear-gradient(135deg, var(--wajenzi-blue-primary) 0%, var(--wajenzi-green) 100%);
        color: white;
        transform: translateX(4px);
    }

    .action-dropdown .dropdown-item:hover i {
        color: white;
    }

    /* User Dropdown */
    .user-dropdown {
        min-width: 200px;
    }

    /* Notification Dropdown */
    .notification-dropdown {
        min-width: 320px;
        max-width: 400px;
    }

    .notifications-list {
        max-height: 300px;
        overflow-y: auto;
    }

    .notification-item {
        display: flex;
        padding: 1rem;
        gap: 0.75rem;
        border-bottom: 1px solid var(--wajenzi-gray-100);
        text-decoration: none;
        color: var(--wajenzi-gray-700);
        transition: all 0.2s ease;
    }

    .notification-item:hover {
        background: var(--wajenzi-gray-50);
        transform: none;
    }

    .notification-item:last-child {
        border-bottom: none;
    }

    .notification-icon {
        color: var(--wajenzi-blue-primary);
        font-size: 1.125rem;
        margin-top: 0.125rem;
    }

    .notification-content {
        flex: 1;
    }

    .notification-content h6 {
        margin: 0 0 0.25rem 0;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--wajenzi-gray-800);
    }

    .notification-content p {
        margin: 0 0 0.25rem 0;
        font-size: 0.8125rem;
        color: var(--wajenzi-gray-600);
        line-height: 1.4;
    }

    .notification-time {
        font-size: 0.75rem;
        color: var(--wajenzi-gray-500);
    }

    .no-notifications {
        text-align: center;
        padding: 2rem 1rem;
        color: var(--wajenzi-gray-600);
    }

    .no-notifications i {
        font-size: 2rem;
        color: var(--wajenzi-green);
        margin-bottom: 0.5rem;
        display: block;
    }

    .no-notifications p {
        margin: 0;
        font-size: 0.875rem;
    }

    .view-all {
        background: var(--wajenzi-gray-50);
        font-weight: 600;
        color: var(--wajenzi-blue-primary);
        border-top: 1px solid var(--wajenzi-gray-100);
        margin-top: 0.5rem;
        text-align: center;
        justify-content: center;
    }

    /* Page Header Spacing */
    #page-header {
        /* Removed margin-bottom to prevent gap between header and content */
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .content-header {
            padding: 0.875rem 1.5rem;
        }
        
        .header-search {
            min-width: 250px;
            max-width: 300px;
        }

        .page-title {
            font-size: 1.25rem;
        }

        .search-scope {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .content-header {
            padding: 0.75rem 1rem;
            gap: 1rem;
        }

        .header-left {
            gap: 1rem;
        }

        .page-title-section {
            display: none;
        }

        .header-search {
            min-width: 200px;
            max-width: 250px;
        }

        .search-form {
            height: 40px;
        }
        
        .search-input-wrapper {
            height: 40px;
        }

        .search-input::placeholder {
            content: "Search...";
        }

        .btn-text,
        .user-info {
            display: none;
        }

        .user-btn {
            padding: 0.5rem;
            gap: 0;
        }

        .dropdown-menu {
            position: fixed;
            top: var(--header-height);
            left: 0;
            right: 0;
            margin: 0;
            border-radius: 0;
            max-height: calc(100vh - var(--header-height));
            overflow-y: auto;
        }
    }

    @media (max-width: 576px) {
        .content-header {
            padding: 0.625rem 0.75rem;
        }
        
        .header-actions {
            display: none;
        }

        .header-search {
            min-width: 150px;
            max-width: 200px;
        }
        
        .search-form {
            height: 36px;
        }
        
        .search-input-wrapper {
            height: 36px;
        }

        .search-input::placeholder {
            content: "Search";
        }

        .header-right {
            gap: 0.5rem;
        }

        .header-btn {
            padding: 0.625rem;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
        }
    }

    /* Loader */
    .modern-loader {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: var(--header-height);
        background: linear-gradient(135deg, var(--wajenzi-blue-primary) 0%, var(--wajenzi-green) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        z-index: 1050;
    }

    .loader-content {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .loader-spinner {
        width: 20px;
        height: 20px;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-top: 2px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    .loader-text {
        font-size: 0.875rem;
        font-weight: 500;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Scrollbar for notifications */
    .notifications-list::-webkit-scrollbar {
        width: 4px;
    }

    .notifications-list::-webkit-scrollbar-track {
        background: transparent;
    }

    .notifications-list::-webkit-scrollbar-thumb {
        background: var(--wajenzi-gray-300);
        border-radius: 2px;
    }

    .notifications-list::-webkit-scrollbar-thumb:hover {
        background: var(--wajenzi-gray-400);
    }
</style>
