<div class="block-content">
    @if($object ?? null)
        <div class="row">
            <!-- Template Information -->
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fa fa-info-circle"></i> Template Information</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td class="font-weight-bold" style="width: 40%;">Name:</td>
                                <td>{{ $object->name }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Building Type:</td>
                                <td>
                                    @if($object->buildingType)
                                        <span class="badge badge-primary">{{ $object->buildingType->name }}</span>
                                    @else
                                        <span class="text-muted">Not specified</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Status:</td>
                                <td>
                                    @if($object->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Created By:</td>
                                <td>{{ $object->creator->name ?? 'Unknown' }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Created:</td>
                                <td>{{ $object->created_at->format('M d, Y \a\t H:i A') }}</td>
                            </tr>
                            <tr>
                                <td class="font-weight-bold">Last Updated:</td>
                                <td>{{ $object->updated_at->format('M d, Y \a\t H:i A') }}</td>
                            </tr>
                        </table>
                        
                        @if($object->description)
                            <hr>
                            <div>
                                <strong>Description:</strong>
                                <p class="text-muted mb-0">{{ $object->description }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Template Statistics -->
            <div class="col-md-6">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fa fa-chart-bar"></i> Template Statistics</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-1 text-primary">{{ $templateStats['stages'] ?? 0 }}</h3>
                                    <small class="text-muted">Construction Stages</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-1 text-success">{{ $templateStats['activities'] ?? 0 }}</h3>
                                    <small class="text-muted">Activities</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-1 text-warning">{{ $templateStats['subActivities'] ?? 0 }}</h3>
                                    <small class="text-muted">Sub-Activities</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="p-3 bg-light rounded">
                                    <h3 class="mb-1 text-info">{{ $templateStats['materials'] ?? 0 }}</h3>
                                    <small class="text-muted">Materials</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($object->templateStages->count() > 0)
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fa fa-sitemap"></i> Template Structure Overview</h6>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <div class="accordion" id="stagesAccordion">
                                @foreach($object->templateStages->sortBy('sort_order') as $index => $stage)
                                    <div class="card mb-2">
                                        <div class="card-header" id="heading{{$index}}">
                                            <h6 class="mb-0">
                                                <button class="btn btn-link text-left" type="button" data-toggle="collapse" data-target="#collapse{{$index}}">
                                                    <i class="fa fa-building text-primary"></i> 
                                                    {{ $stage->constructionStage->name ?? 'Unknown Stage' }}
                                                    <span class="badge badge-primary ml-2">{{ $stage->templateActivities->count() }} activities</span>
                                                </button>
                                            </h6>
                                        </div>
                                        <div id="collapse{{$index}}" class="collapse" data-parent="#stagesAccordion">
                                            <div class="card-body">
                                                @if($stage->templateActivities->count() > 0)
                                                    <ul class="list-group list-group-flush">
                                                        @foreach($stage->templateActivities->sortBy('sort_order') as $activity)
                                                            <li class="list-group-item">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <div>
                                                                        <i class="fa fa-tasks text-success"></i>
                                                                        <strong>{{ $activity->activity->name ?? 'Unknown Activity' }}</strong>
                                                                        @if($activity->templateSubActivities->count() > 0)
                                                                            <span class="badge badge-success ml-2">{{ $activity->templateSubActivities->count() }} sub-activities</span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                
                                                                @if($activity->templateSubActivities->count() > 0)
                                                                    <div class="mt-2 ml-3">
                                                                        <ul class="list-group list-group-flush">
                                                                            @foreach($activity->templateSubActivities->sortBy('sort_order') as $subActivity)
                                                                                <li class="list-group-item border-0 py-1 pl-3" style="background-color: #f8f9fa;">
                                                                                    <i class="fa fa-puzzle-piece text-warning"></i> 
                                                                                    {{ $subActivity->subActivity->name ?? 'Unknown Sub-Activity' }}
                                                                                    
                                                                                    @if($subActivity->subActivity->estimated_duration_hours)
                                                                                        <small class="text-muted ml-2">
                                                                                            ({{ $subActivity->subActivity->estimated_duration_hours }} {{ $subActivity->subActivity->duration_unit ?? 'hours' }})
                                                                                        </small>
                                                                                    @endif
                                                                                    
                                                                                    @if($subActivity->subActivity->materials->count() > 0)
                                                                                        <div class="mt-1">
                                                                                            <small class="text-info">
                                                                                                <i class="fa fa-cubes"></i> {{ $subActivity->subActivity->materials->count() }} material(s)
                                                                                            </small>
                                                                                        </div>
                                                                                    @endif
                                                                                </li>
                                                                            @endforeach
                                                                        </ul>
                                                                    </div>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <p class="text-muted mb-0">No activities configured for this stage.</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="{{ route('hr_settings_boq_template_builder', ['templateId' => $object->id]) }}" class="btn btn-primary">
                    <i class="fa fa-cogs"></i> Open Template Builder
                </a>
                <a href="{{ route('hr_settings_boq_template_report', ['templateId' => $object->id]) }}" target="_blank" class="btn btn-success">
                    <i class="fa fa-table"></i> View Full Report
                </a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fa fa-times"></i> Close
                </button>
            </div>
        </div>
    @else
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i> Template not found or access denied.
        </div>
    @endif
</div>