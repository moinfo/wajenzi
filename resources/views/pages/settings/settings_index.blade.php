@extends('layouts.backend')

@section('content')
    <div class="content">
        <!-- Heading -->
        <h2 class="content-heading">
            {{settings('SYSTEM_NAME')}} Related Settings
            <small>| All</small>
        </h2>

        <!-- Search Section -->
        <div class="search-section">
            <input type="text"
                   id="settingsSearch"
                   class="search-input"
                   placeholder="Search settings..."
                   onkeyup="filterSettings()">
        </div>

        <!-- Settings Grid -->
        <div class="settings-grid js-appear-enabled animated fadeIn" data-toggle="appear">
            @foreach($settings as $item)
                @if(auth()->user()->can($item['name']))
                    <a class="setting-card" href="{{ route($item['route']) }}">
                        <i class="{{ $item['icon'] }} setting-icon"></i>
                        <h3 class="setting-title">{{ $item['name'] }}</h3>
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    <!-- Search Script -->
    <script>
        function filterSettings() {
            const searchInput = document.getElementById('settingsSearch');
            const filter = searchInput.value.toLowerCase();
            const cards = document.querySelectorAll('.setting-card');

            cards.forEach(card => {
                const title = card.querySelector('.setting-title').textContent.toLowerCase();
                if (title.includes(filter)) {
                    card.style.display = '';
                    card.classList.add('animate-fade-in');
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
@endsection
