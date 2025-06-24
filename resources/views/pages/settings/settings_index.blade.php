@extends('layouts.backend')

@section('content')
    <div class="content">
        <!-- Heading -->
        <div class="content-heading d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1">
                    <i class="fa fa-cogs text-primary mr-2"></i>
                    System Settings
                </h2>
                <p class="text-muted mb-0">Configure {{settings('SYSTEM_NAME')}} settings and modules</p>
            </div>
            <div class="text-muted">
                <small><i class="fa fa-shield-alt mr-1"></i> {{ count($settings) }} modules available</small>
            </div>
        </div>

        <!-- Search Section -->
        <div class="block">
            <div class="block-content p-3">
                <div class="search-section">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-light border-right-0">
                                <i class="fa fa-search text-muted"></i>
                            </span>
                        </div>
                        <input type="text"
                               id="settingsSearch"
                               class="form-control search-input border-left-0"
                               placeholder="Search settings modules..."
                               onkeyup="filterSettings()">
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Grid -->
        <div class="settings-grid js-appear-enabled animated fadeIn" data-toggle="appear">
            @foreach($settings as $item)
                @if(auth()->user()->can($item['name']))
                    <a class="setting-card" href="{{ route($item['route']) }}">
                        <div class="setting-card-icon">
                            <i class="{{ $item['icon'] }} setting-icon"></i>
                        </div>
                        <div class="setting-card-content">
                            <h4 class="setting-title">{{ $item['name'] }}</h4>
                            @if(isset($item['badge']) && $item['badge'] > 0)
                                <span class="badge badge-primary setting-badge">{{ $item['badge'] }}</span>
                            @endif
                            <div class="setting-arrow">
                                <i class="fa fa-chevron-right"></i>
                            </div>
                        </div>
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    <!-- Custom Styles -->
    <style>
        .search-section {
            margin-bottom: 0;
        }

        .search-input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .setting-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 24px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            min-height: 80px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .setting-card:hover {
            text-decoration: none;
            color: inherit;
            border-color: #007bff;
            box-shadow: 0 8px 25px rgba(0,123,255,0.15);
            transform: translateY(-2px);
        }

        .setting-card:hover .setting-icon {
            color: #007bff;
            transform: scale(1.1);
        }

        .setting-card:hover .setting-arrow {
            transform: translateX(5px);
            opacity: 1;
        }

        .setting-card-icon {
            flex: 0 0 60px;
            margin-right: 20px;
        }

        .setting-icon {
            font-size: 32px;
            color: #6c757d;
            transition: all 0.3s ease;
            display: block;
            text-align: center;
        }

        .setting-card-content {
            flex: 1;
            position: relative;
        }

        .setting-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
            color: #2c3e50;
            line-height: 1.4;
        }

        .setting-badge {
            position: absolute;
            top: -5px;
            right: 25px;
            font-size: 10px;
            padding: 2px 6px;
        }

        .setting-arrow {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 14px;
            opacity: 0.6;
            transition: all 0.3s ease;
        }

        /* Animation classes */
        .animate-fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .setting-card {
                padding: 20px;
                min-height: 70px;
            }
            
            .setting-card-icon {
                flex: 0 0 50px;
                margin-right: 15px;
            }
            
            .setting-icon {
                font-size: 28px;
            }
            
            .setting-title {
                font-size: 15px;
            }
        }

        /* Loading animation for initial page load */
        .settings-grid.js-appear-enabled {
            opacity: 0;
            animation: slideUp 0.6s ease-out 0.2s forwards;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Empty state */
        .no-results {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .no-results i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        /* Search input group styling */
        .input-group-text {
            background-color: #f8f9fa !important;
            border-color: #dee2e6;
        }

        .search-input {
            background-color: #fff;
        }

        .search-input::placeholder {
            color: #adb5bd;
        }
    </style>

    <!-- Enhanced Search Script -->
    <script>
        function filterSettings() {
            const searchInput = document.getElementById('settingsSearch');
            const filter = searchInput.value.toLowerCase();
            const cards = document.querySelectorAll('.setting-card');
            const settingsGrid = document.querySelector('.settings-grid');
            let visibleCount = 0;

            // Remove existing no-results message
            const existingNoResults = document.querySelector('.no-results');
            if (existingNoResults) {
                existingNoResults.remove();
            }

            cards.forEach(card => {
                const title = card.querySelector('.setting-title').textContent.toLowerCase();
                if (title.includes(filter)) {
                    card.style.display = '';
                    card.classList.add('animate-fade-in');
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                    card.classList.remove('animate-fade-in');
                }
            });

            // Show no results message if no cards are visible
            if (visibleCount === 0 && filter.length > 0) {
                const noResults = document.createElement('div');
                noResults.className = 'no-results';
                noResults.innerHTML = `
                    <i class="fa fa-search"></i>
                    <h5>No settings found</h5>
                    <p>Try adjusting your search terms</p>
                `;
                settingsGrid.appendChild(noResults);
            }
        }

        // Clear search on Escape key
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('settingsSearch');
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    this.value = '';
                    filterSettings();
                }
            });

            // Add loading animation
            setTimeout(() => {
                document.querySelector('.settings-grid').style.opacity = '1';
            }, 100);
        });
    </script>
@endsection
