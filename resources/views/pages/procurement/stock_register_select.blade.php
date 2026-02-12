@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">Site Stock Register
            <div class="float-right">
                <a href="{{ route('procurement_dashboard') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="si si-graph"></i> Dashboard
                </a>
            </div>
        </div>

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Select a Project</h3>
            </div>
            <div class="block-content">
                @if($projects->isEmpty())
                    <p class="text-muted text-center py-4">No projects with a BOQ found.</p>
                @else
                    <div class="row">
                        @foreach($projects as $project)
                            <div class="col-md-4 col-lg-3">
                                <a href="{{ route('stock_register', $project->id) }}" class="block block-rounded block-link-shadow text-center">
                                    <div class="block-content block-content-full">
                                        <i class="fa fa-warehouse fa-2x text-primary mb-2"></i>
                                        <div class="font-w600">{{ $project->name }}</div>
                                        <div class="font-size-sm text-muted">{{ $project->code ?? '' }}</div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
