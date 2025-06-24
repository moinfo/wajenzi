<div class="template-preview">
    <h6 class="text-primary mb-3"><i class="fa fa-eye"></i> BOQ Template Structure Preview</h6>
    
    @if($stages->count() > 0)
        <div class="accordion" id="templateAccordion">
            @foreach($stages as $stageIndex => $stage)
                <div class="card mb-2">
                    <div class="card-header bg-primary text-white" id="heading{{ $stage->id }}">
                        <h6 class="mb-0">
                            <button class="btn btn-link text-white text-decoration-none" type="button" data-toggle="collapse" data-target="#collapse{{ $stage->id }}">
                                <i class="fa fa-layer-group"></i> {{ $stage->name }}
                                <span class="badge badge-light text-primary ml-2">{{ $stage->activities->count() }} Activities</span>
                            </button>
                        </h6>
                    </div>

                    <div id="collapse{{ $stage->id }}" class="collapse {{ $stageIndex === 0 ? 'show' : '' }}" data-parent="#templateAccordion">
                        <div class="card-body">
                            @if($stage->description)
                                <p class="text-muted mb-3">{{ $stage->description }}</p>
                            @endif
                            
                            @if($stage->activities->count() > 0)
                                @foreach($stage->activities as $activity)
                                    <div class="border-left border-success pl-3 mb-3">
                                        <h6 class="text-success mb-2">
                                            <i class="fa fa-wrench"></i> {{ $activity->name }}
                                            @if($activity->subActivities->count() > 0)
                                                <span class="badge badge-success ml-2">{{ $activity->subActivities->count() }} Sub-Activities</span>
                                            @endif
                                        </h6>
                                        
                                        @if($activity->description)
                                            <p class="text-muted small mb-2">{{ $activity->description }}</p>
                                        @endif
                                        
                                        @if($activity->subActivities->count() > 0)
                                            <div class="row">
                                                @foreach($activity->subActivities as $subActivity)
                                                    <div class="col-md-6 mb-2">
                                                        <div class="card border-warning">
                                                            <div class="card-body p-2">
                                                                <h6 class="card-title text-warning mb-1">
                                                                    <i class="fa fa-puzzle-piece"></i> {{ $subActivity->name }}
                                                                </h6>
                                                                
                                                                @if($subActivity->description)
                                                                    <p class="card-text small text-muted mb-2">{{ $subActivity->description }}</p>
                                                                @endif
                                                                
                                                                <div class="row small">
                                                                    @if($subActivity->estimated_duration_hours)
                                                                        <div class="col-6">
                                                                            <i class="fa fa-clock text-primary"></i> 
                                                                            {{ $subActivity->duration_display }}
                                                                        </div>
                                                                    @endif
                                                                    
                                                                    @if($subActivity->labor_requirement)
                                                                        <div class="col-6">
                                                                            <i class="fa fa-users text-success"></i> 
                                                                            {{ $subActivity->labor_requirement }} workers
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                
                                                                <div class="row small mt-1">
                                                                    <div class="col-6">
                                                                        <i class="fa fa-star text-warning"></i> 
                                                                        {{ ucfirst(str_replace('_', ' ', $subActivity->skill_level)) }}
                                                                    </div>
                                                                    
                                                                    <div class="col-6">
                                                                        @if($subActivity->can_run_parallel)
                                                                            <i class="fa fa-check text-success"></i> Parallel
                                                                        @else
                                                                            <i class="fa fa-arrow-right text-muted"></i> Sequential
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                
                                                                @if($subActivity->weather_dependent)
                                                                    <div class="mt-1">
                                                                        <small class="text-warning">
                                                                            <i class="fa fa-cloud"></i> Weather Dependent
                                                                        </small>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-muted small">No sub-activities selected for this activity.</p>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted">No activities selected for this stage.</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Summary Statistics -->
        <div class="mt-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="card-title"><i class="fa fa-chart-bar"></i> Template Summary</h6>
                    <div class="row text-center">
                        <div class="col-3">
                            <div class="h4 text-primary">{{ $stages->count() }}</div>
                            <small>Construction Stages</small>
                        </div>
                        <div class="col-3">
                            <div class="h4 text-success">{{ $stages->sum(function($stage) { return $stage->activities->count(); }) }}</div>
                            <small>Activities</small>
                        </div>
                        <div class="col-3">
                            <div class="h4 text-warning">{{ $stages->sum(function($stage) { return $stage->activities->sum(function($activity) { return $activity->subActivities->count(); }); }) }}</div>
                            <small>Sub-Activities</small>
                        </div>
                        <div class="col-3">
                            @php
                                $totalHours = $stages->sum(function($stage) {
                                    return $stage->activities->sum(function($activity) {
                                        return $activity->subActivities->sum('estimated_duration_hours');
                                    });
                                });
                            @endphp
                            <div class="h4 text-info">{{ number_format($totalHours, 1) }}</div>
                            <small>Total Hours</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    @else
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i> 
            No construction stages selected. Please select at least one construction stage to preview the template structure.
        </div>
    @endif
</div>

<style>
.template-preview .card-header button {
    width: 100%;
    text-align: left;
}

.template-preview .border-left {
    border-left-width: 3px !important;
}

.template-preview .card.border-warning:hover {
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
}
</style>