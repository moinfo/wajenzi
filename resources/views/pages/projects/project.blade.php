{{-- project.blade.php --}}
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">
                Project Details: {{ $project->project_name }}
                <div class="float-right">
                    <a href="{{ route('projects') }}" class="btn btn-rounded btn-outline-secondary min-width-125 mb-10">
                        <i class="fa fa-arrow-left"></i> Back to Projects
                    </a>
                </div>
            </div>

            <!-- Project Details Card -->
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Project Information</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 30%;">Project Name</th>
                                        <td>{{ $project->project_name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Client</th>
                                        <td>{{ $project->client ? $project->client->first_name.' '.$project->client->last_name : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Project Type</th>
                                        <td>{{ $project->projectType->name ?? 'N/A' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 30%;">Phone Number</th>
                                        <td>{{ $project->client->phone_number ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Start Date</th>
                                        <td>{{ $project->start_date }}</td>
                                    </tr>
                                    <tr>
                                        <th>Expected End Date</th>
                                        <td>{{ $project->expected_end_date }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($project->file)
                    <div class="row mt-4">
                        <div class="col-12">
                            <a href="{{ url($project->file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-file-pdf"></i> View Document
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Approval Component -->
            <x-ringlesoft-approval-actions :model="$project" />

        </div>
    </div>
@endsection
