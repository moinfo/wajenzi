{{-- project_dashboard.blade.php --}}
@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Project Dashboard</div>

            <!-- Summary Stats -->
            <div class="row">
                <!-- Total Projects -->
                <div class="col-6 col-xl-3">
                    <a class="block block-link-shadow text-right" href="javascript:void(0)">
                        <div class="block-content block-content-full clearfix">
                            <div class="float-left mt-10 d-none d-sm-block">
                                <i class="si si-briefcase fa-3x text-body-bg-dark"></i>
                            </div>
                            <div class="font-size-h3 font-w600">{{ $totalProjects }}</div>
                            <div class="font-size-sm font-w600 text-uppercase text-muted">Total Projects</div>
                        </div>
                    </a>
                </div>
                <!-- In Progress Projects -->
                <div class="col-6 col-xl-3">
                    <a class="block block-link-shadow text-right" href="javascript:void(0)">
                        <div class="block-content block-content-full clearfix">
                            <div class="float-left mt-10 d-none d-sm-block">
                                <i class="si si-clock fa-3x text-body-bg-dark"></i>
                            </div>
                            <div class="font-size-h3 font-w600">{{ $inProgressProjects }}</div>
                            <div class="font-size-sm font-w600 text-uppercase text-muted">In Progress</div>
                        </div>
                    </a>
                </div>
                <!-- Total Clients -->
                <div class="col-6 col-xl-3">
                    <a class="block block-link-shadow text-right" href="javascript:void(0)">
                        <div class="block-content block-content-full clearfix">
                            <div class="float-left mt-10 d-none d-sm-block">
                                <i class="si si-users fa-3x text-body-bg-dark"></i>
                            </div>
                            <div class="font-size-h3 font-w600">{{ $totalClients }}</div>
                            <div class="font-size-sm font-w600 text-uppercase text-muted">Total Clients</div>
                        </div>
                    </a>
                </div>
                <!-- This Month Expenses -->
                <div class="col-6 col-xl-3">
                    <a class="block block-link-shadow text-right" href="javascript:void(0)">
                        <div class="block-content block-content-full clearfix">
                            <div class="float-left mt-10 d-none d-sm-block">
                                <i class="si si-wallet fa-3x text-body-bg-dark"></i>
                            </div>
                            <div class="font-size-h3 font-w600">{{ number_format($thisMonthExpenses, 2) }}</div>
                            <div class="font-size-sm font-w600 text-uppercase text-muted">This Month Expenses</div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Latest Projects -->
                <div class="col-xl-6">
                    <div class="block">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">Latest Projects</h3>
                        </div>
                        <div class="block-content">
                            <table class="table table-striped table-hover table-borderless table-vcenter">
                                <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Client</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($latestProjects as $project)
                                    <tr>
                                        <td>{{ $project->project_name }}</td>
                                        <td>{{ $project->client->first_name }} {{ $project->client->last_name }}</td>
                                        <td>
                                            @if($project->status == 'pending')
                                                <div class="badge badge-warning">{{ $project->status}}</div>
                                            @elseif($project->status == 'in_progress')
                                                <div class="badge badge-info">{{ $project->status}}</div>
                                            @elseif($project->status == 'completed')
                                                <div class="badge badge-success">{{ $project->status}}</div>
                                            @else
                                                <div class="badge badge-secondary">{{ $project->status}}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar" role="progressbar" style="width: {{ $project->progress }}%;" aria-valuenow="{{ $project->progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="col-xl-6">
                    <div class="block">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">Recent Activities</h3>
                        </div>
                        <div class="block-content">
                            <ul class="list list-activity">
                                @foreach($recentActivities as $activity)
                                    <li>
                                        <i class="si si-{{ $activity->getIcon() }} text-{{ $activity->getColor() }}"></i>
                                        <div class="font-w600">{{ $activity->description }}</div>
                                        <div>
                                            <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Upcoming Site Visits -->
                <div class="col-xl-6">
                    <div class="block">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">Upcoming Site Visits</h3>
                        </div>
                        <div class="block-content">
                            <table class="table table-striped table-hover table-borderless table-vcenter">
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Project</th>
                                    <th>Inspector</th>
                                    <th>Status</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($upcomingVisits as $visit)
                                    <tr>
                                        <td>{{ $visit->visit_date }}</td>
                                        <td>{{ $visit->project->project_name }}</td>
                                        <td>{{ $visit->inspector->name }}</td>
                                        <td>
                                            <div class="badge badge-warning">{{ $visit->status }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Material Inventory Alert -->
                <div class="col-xl-6">
                    <div class="block">
                        <div class="block-header block-header-default">
                            <h3 class="block-title">Low Inventory Alert</h3>
                        </div>
                        <div class="block-content">
                            <table class="table table-striped table-hover table-borderless table-vcenter">
                                <thead>
                                <tr>
                                    <th>Material</th>
                                    <th>Project</th>
                                    <th class="text-center">Current Stock</th>
                                    <th class="text-center">Min Required</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($lowInventory as $inventory)
                                    <tr>
                                        <td>{{ $inventory->material->name }}</td>
                                        <td>{{ $inventory->project->project_name }}</td>
                                        <td class="text-center">{{ number_format($inventory->quantity, 2) }} {{ $inventory->material->unit }}</td>
                                        <td class="text-center">{{ number_format($inventory->material->minimum_quantity, 2) }} {{ $inventory->material->unit }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
