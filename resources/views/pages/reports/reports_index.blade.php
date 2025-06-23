@extends('layouts.backend')

@section('content')
    <div class="content">
        <!-- Gradient Header -->
        <div class="content-heading" style="background: linear-gradient(90deg, #4461E9 0%, #32CD32 100%); border-radius: 2rem; padding: 1.25rem 2rem; color: white; margin-bottom: 2rem;">
            {{settings('SYSTEM_NAME')}} Related Reports
        </div>

        <!-- Search Input -->
        <div style="margin: 2rem 0 3rem 0;">
            <input type="text"
                   id="reportSearch"
                   placeholder="Search reports..."
                   style="width: 100%; padding: 0.875rem 1.25rem; border-radius: 0.75rem; border: 1px solid #e5e7eb; font-size: 0.95rem;"
                   oninput="filterReports()">
        </div>

        <!-- Reports Grid -->
        <div class="row js-appear-enabled animated fadeIn" data-toggle="appear" style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 2rem; padding: 0 0.5rem;">
            @foreach($reports as $item)
                @if(auth()->user()->can($item['name']))
                    <a class="block block-link-shadow text-center report-item" href="{{ route($item['route']) }}">
                        <div class="block-content" style="display: flex; flex-direction: column; align-items: center; gap: 1.5rem;">
                            <!-- Updated Icon Style -->
                            <div style="width: 48px; height: 48px;">
                                <svg style="width: 100%; height: 100%; color: #4461E9;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"></path>
                                </svg>
                            </div>
                            <p class="font-w600 report-name" style="color: #444; font-size: 0.875rem; margin: 0; text-align: center; line-height: 1.4;">
                                {{ $item['name'] }}
                            </p>
                        </div>
                    </a>
                @endif
            @endforeach
        </div>
    </div>

    <style>
        #reportSearch::placeholder {
            color: #9CA3AF;
        }

        #reportSearch:focus {
            outline: none;
            border-color: #4461E9;
            box-shadow: 0 0 0 3px rgba(68, 97, 233, 0.1);
        }

        /* Responsive Grid */
        @media (max-width: 1400px) {
            .row[data-toggle="appear"] {
                grid-template-columns: repeat(4, 1fr) !important;
            }
        }

        @media (max-width: 1024px) {
            .row[data-toggle="appear"] {
                grid-template-columns: repeat(3, 1fr) !important;
            }
        }

        @media (max-width: 768px) {
            .row[data-toggle="appear"] {
                grid-template-columns: repeat(2, 1fr) !important;
            }

            .content {
                padding: 1rem;
            }

            .content-heading {
                padding: 1rem 1.5rem !important;
            }
        }
    </style>

    <script>
        function filterReports() {
            const searchInput = document.getElementById('reportSearch');
            const filter = searchInput.value.toLowerCase();
            const reports = document.getElementsByClassName('report-item');

            Array.from(reports).forEach(report => {
                const name = report.querySelector('.report-name').textContent.toLowerCase();
                if (name.includes(filter)) {
                    report.style.display = '';
                } else {
                    report.style.display = 'none';
                }
            });
        }

        // Initialize the search functionality when the document is ready
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('reportSearch');
            searchInput.addEventListener('input', filterReports);
        });
    </script>
@endsection
