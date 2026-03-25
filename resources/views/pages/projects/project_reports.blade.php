@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Project Reports</div>

            <div class="row">
                <div class="col-md-4">
                    <div class="block block-rounded">
                        <div class="block-content block-content-full text-center">
                            <div class="font-size-h2 font-w700 text-primary">{{ $items->count() }}</div>
                            <div class="text-muted">Total Report Items</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="block block-rounded">
                        <div class="block-content block-content-full text-center">
                            <div class="font-size-h2 font-w700 text-success">{{ $dailyReportsCount }}</div>
                            <div class="text-muted">Daily Reports</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="block block-rounded">
                        <div class="block-content block-content-full text-center">
                            <div class="font-size-h2 font-w700 text-info">{{ $siteVisitsCount }}</div>
                            <div class="text-muted">Site Visits</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Filter Reports</h3>
                </div>
                <div class="block-content">
                    <form method="post" autocomplete="off">
                        @csrf
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Start Date</label>
                                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>End Date</label>
                                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Project</label>
                                    <select name="project_id" class="form-control">
                                        <option value="">All Projects</option>
                                        @foreach ($projects as $project)
                                            <option value="{{ $project->id }}" {{ (string) request('project_id') === (string) $project->id ? 'selected' : '' }}>
                                                {{ $project->project_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group" style="margin-top: 32px;">
                                    <button type="submit" class="btn btn-primary btn-block">Show</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Report Feed</h3>
                </div>
                <div class="block-content">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Project</th>
                                <th>Date</th>
                                <th>Owner</th>
                                <th>Status</th>
                                <th>Summary</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($items as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item['kind'] }}</td>
                                    <td>{{ $item['project_name'] }}</td>
                                    <td>{{ $item['date'] ?? '-' }}</td>
                                    <td>{{ $item['owner_name'] }}</td>
                                    <td>{{ $item['status'] }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($item['summary'] ?? '-', 120) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No project reports found.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
