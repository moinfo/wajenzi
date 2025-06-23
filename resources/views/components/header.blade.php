<?php
use Illuminate\Support\Facades\Auth;
$notification_unread = \App\Models\Notification::getLatestUnreadNotifications(Auth::user()->id);
$count_notification_unread = \App\Models\Notification::getUnreadNotificationsCount(Auth::user()->id);
//                    dump($notification_unread);
?>
<header id="page-header" class="modern-header">
    <!-- Header Content -->
    <div class="content-header">
        <!-- Left Section -->
        <div class="header-left">
            <!-- Toggle Sidebar -->
            <button type="button" class="header-btn" data-toggle="layout" data-action="sidebar_toggle">
                <i class="fa fa-bars"></i>
            </button>
        </div>

        <!-- Right Section -->
        <div class="header-right">
            <!-- User Dropdown -->
            <div class="header-dropdown">
                <button type="button" class="header-btn user-btn" id="page-header-user-dropdown" data-toggle="dropdown">
                    <span class="user-name">{{ Auth::user()->name }}</span>
                    <i class="fa fa-chevron-down"></i>
                </button>
                <div class="dropdown-menu modern-dropdown dropdown-menu-right" aria-labelledby="page-header-user-dropdown">
                    <a class="dropdown-item" href="{{ route('user_profile') }}">
                        <i class="si si-user"></i> Profile
                    </a>
                    <a class="dropdown-item" href="{{ route('user_inbox') }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="si si-envelope-open"></i> Inbox</span>
                            <span class="badge">3</span>
                        </div>
                    </a>
                    <a class="dropdown-item" href="{{ route('user_settings') }}">
                        <i class="si si-wrench"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="si si-logout"></i> Sign Out
                    </a>
{{--                    <a class="dropdown-item" href="{{ route('logout') }}"--}}
{{--                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">--}}
{{--                        <i class="si si-logout mr-5"></i> Sign Out--}}
{{--                    </a>--}}
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </div>
            </div>

            <!-- Notifications -->
            <div class="header-dropdown">
                <button type="button" class="header-btn" id="page-header-notifications" data-toggle="dropdown">
                    <i class="fa fa-bell"></i>
                    <span class="badge">{{$count_notification_unread}}</span>
                </button>
                <div class="dropdown-menu modern-dropdown dropdown-menu-right" aria-labelledby="page-header-notifications">
                    <h6 class="dropdown-header">Notifications</h6>
                    <div class="notifications-list">
                        @foreach(Auth::user()->unreadNotifications()->take(3)->get() as $notification)
                            <a class="notification-item" href="{{url($notification->data['link'])}}">
                                <div class="notification-icon">
                                    <i class="fa fa-clock-o"></i>
                                </div>
                                <div class="notification-content">
                                    <h6>{{$notification->data['title']}}</h6>
                                    <p>{{$notification->data['body']}}</p>
                                    <span class="notification-time">{{$notification->updated_at}}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                    <a class="dropdown-item text-center view-all" href="{{ route('user_notifications') }}">
                        View All Notifications
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Overlay -->
    <div id="page-header-search" class="modern-search-overlay">
        <form action="/dashboard" method="POST">
            @csrf
            <div class="search-container">
                <div class="search-input-group">
                    <button type="button" class="search-close" data-toggle="layout" data-action="header_search_off">
                        <i class="fa fa-times"></i>
                    </button>
                    <input type="text" class="search-input" placeholder="Search..." id="page-header-search-input" name="page-header-search-input">
                    <button type="submit" class="search-submit">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Loading Overlay -->
    <div id="page-header-loader" class="modern-loader">
        <div class="loader-content">
            <i class="fa fa-sun-o fa-spin"></i>
        </div>
    </div>
</header>

<style>
    :root {
        --header-bg: #ffffff;
        --header-height: 60px;
        --primary-color: #4169E1;
        --accent-color: #32CD32;
        --text-primary: #333333;
        --text-secondary: #666666;
        --border-color: #e5e7eb;
        --hover-bg: #f8f9fa;
        --shadow-sm: 0 1px 2px 0 rgba(0,0,0,0.05);
        --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
    }

    /* Header Container */
    .modern-header {
        background: var(--header-bg);
        height: var(--header-height);
        border-bottom: 1px solid var(--border-color);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1030;
    }

    .content-header {
        height: 100%;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 1.5rem;
    }

    /* Header Sections */
    .header-left, .header-right {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    /* Buttons */
    .header-btn {
        background: transparent;
        border: none;
        padding: 0.5rem;
        border-radius: 8px;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
    }

    .header-btn:hover {
        background: var(--hover-bg);
        color: var(--primary-color);
    }

    .header-btn i {
        font-size: 1.25rem;
    }

    /* User Button */
    .user-btn {
        padding: 0.5rem 1rem;
        background: var(--hover-bg);
        border-radius: 20px;
    }

    .user-name {
        margin-right: 0.5rem;
        font-weight: 500;
    }

    /* Badges */
    .badge {
        background: var(--accent-color);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    /* Dropdowns */
    .modern-dropdown {
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        box-shadow: var(--shadow-md);
        padding: 1rem 0;
        min-width: 240px;
    }

    .dropdown-header {
        color: var(--text-secondary);
        font-size: 0.875rem;
        font-weight: 600;
        padding: 0.5rem 1rem;
        margin-bottom: 0.5rem;
    }

    .dropdown-item {
        padding: 0.625rem 1rem;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        transition: all 0.2s ease;
    }

    .dropdown-item:hover {
        background: var(--hover-bg);
        color: var(--primary-color);
    }

    .dropdown-item i {
        font-size: 1.1rem;
        color: var(--text-secondary);
    }

    /* Theme Options */
    .theme-options {
        padding: 1rem;
    }

    .theme-header {
        font-size: 0.875rem;
        color: var(--text-secondary);
        margin-bottom: 0.75rem;
    }

    .theme-colors {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 0.5rem;
    }

    .theme-btn {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border: 2px solid var(--border-color);
        cursor: pointer;
        transition: transform 0.2s ease;
    }

    .theme-btn:hover {
        transform: scale(1.1);
    }

    /* Notifications */
    .notifications-list {
        max-height: 300px;
        overflow-y: auto;
    }

    #page-header {
        margin-bottom: 30px!important;
    }

    .notification-item {
        display: flex;
        padding: 1rem;
        gap: 1rem;
        border-bottom: 1px solid var(--border-color);
        text-decoration: none;
        color: var(--text-primary);
    }

    .notification-icon {
        color: var(--accent-color);
        font-size: 1.25rem;
    }

    .notification-content h6 {
        margin: 0;
        font-size: 0.875rem;
        font-weight: 600;
    }

    .notification-content p {
        margin: 0.25rem 0;
        font-size: 0.8125rem;
        color: var(--text-secondary);
    }

    .notification-time {
        font-size: 0.75rem;
        color: var(--text-secondary);
    }

    /* Search Overlay */
    .modern-search-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.8);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1040;
    }

    .search-container {
        width: 100%;
        max-width: 600px;
        padding: 2rem;
    }

    .search-input-group {
        display: flex;
        align-items: center;
        background: white;
        border-radius: 12px;
        overflow: hidden;
    }

    .search-input {
        flex: 1;
        border: none;
        padding: 1rem;
        font-size: 1.125rem;
    }

    .search-close,
    .search-submit {
        background: transparent;
        border: none;
        padding: 1rem;
        color: var(--text-secondary);
        cursor: pointer;
    }

    /* Loader */
    .modern-loader {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: var(--header-height);
        background: var(--primary-color);
        display: none;
        align-items: center;
        justify-content: center;
        color: white;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .header-btn span {
            display: none;
        }

        .modern-dropdown {
            position: fixed;
            top: var(--header-height);
            left: 0;
            right: 0;
            margin: 0;
            border-radius: 0;
        }
    }
</style>
