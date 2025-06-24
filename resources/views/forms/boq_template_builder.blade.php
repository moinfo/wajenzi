<div class="block-content" style="max-height: 80vh; overflow-y: auto;">
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info mb-3">
                <h5><i class="fa fa-info-circle"></i> BOQ Template Builder</h5>
                <p class="mb-0">Configure your BOQ template by selecting construction stages, activities, and sub-activities. This will define the structure and default items for projects using this template.</p>
            </div>
        </div>
    </div>

    <form method="post" autocomplete="off" id="templateBuilderForm">
        @csrf
        <input type="hidden" name="template_id" value="{{ $templateId ?? '' }}">
        
        <div class="row">
            <!-- Template Info Panel -->
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fa fa-info"></i> Template Information</h6>
                    </div>
                    <div class="card-body">
                        @if($template ?? null)
                            <p><strong>Name:</strong> {{ $template->name }}</p>
                            <p><strong>Building Type:</strong> {{ $template->buildingType->name ?? 'Not Set' }}</p>
                            <p><strong>Created:</strong> {{ $template->created_at->format('M d, Y') }}</p>
                            <p><strong>Status:</strong> 
                                <span class="badge badge-{{ $template->is_active ? 'success' : 'secondary' }}">
                                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </p>
                        @endif
                        
                        <div class="form-group">
                            <label for="action">Builder Action:</label>
                            <select name="action" id="builderAction" class="form-control" required>
                                <option value="">Select Action</option>
                                <option value="add_stage">Add Construction Stage</option>
                                <option value="add_activity">Add Activity to Stage</option>
                                <option value="add_sub_activity">Add Sub-Activity</option>
                                <option value="remove_stage">Remove Stage</option>
                                <option value="save_template">Save Template Configuration</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card border-info mt-3">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fa fa-chart-bar"></i> Template Stats</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="font-weight-bold text-primary">{{ $templateStats['stages'] ?? 0 }}</div>
                                <small>Stages</small>
                            </div>
                            <div class="col-4">
                                <div class="font-weight-bold text-success">{{ $templateStats['activities'] ?? 0 }}</div>
                                <small>Activities</small>
                            </div>
                            <div class="col-4">
                                <div class="font-weight-bold text-warning">{{ $templateStats['subActivities'] ?? 0 }}</div>
                                <small>Sub-Activities</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Builder Panel -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fa fa-cogs"></i> Template Structure Builder</h6>
                    </div>
                    <div class="card-body">
                        
                        <!-- Construction Stages Selection -->
                        <div id="stageSelection" class="builder-section">
                            <h6 class="text-primary"><i class="fa fa-layer-group"></i> Available Construction Stages</h6>
                            <div class="row">
                                @foreach($constructionStages ?? [] as $stage)
                                    <div class="col-md-6 mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input stage-checkbox" 
                                                   id="stage_{{ $stage->id }}" 
                                                   name="selected_stages[]" 
                                                   value="{{ $stage->id }}"
                                                   {{ in_array($stage->id, $selectedStages ?? []) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="stage_{{ $stage->id }}">
                                                <strong>{{ $stage->name }}</strong>
                                                <br><small class="text-muted">{{ $stage->description }}</small>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <hr>

                        <!-- Activities Selection (Dynamic based on selected stages) -->
                        <div id="activitySelection" class="builder-section">
                            <h6 class="text-success"><i class="fa fa-wrench"></i> Activities Configuration</h6>
                            <div id="activitiesContainer">
                                <p class="text-muted">Select construction stages above to configure activities.</p>
                            </div>
                        </div>

                        <hr>

                        <!-- Sub-Activities with Time Tracking -->
                        <div id="subActivitySelection" class="builder-section">
                            <h6 class="text-warning"><i class="fa fa-puzzle-piece"></i> Sub-Activities & Time Tracking</h6>
                            <div id="subActivitiesContainer">
                                <p class="text-muted">Select activities to configure sub-activities and time estimates.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row mt-3">
            <div class="col-12 text-right">
                <button type="button" class="btn btn-secondary" onclick="$('#ajax-loader-modal').modal('hide');">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" id="previewTemplate" class="btn btn-info">
                    <i class="fa fa-eye"></i> Preview Template
                </button>
                <button type="submit" class="btn btn-success" name="buildTemplate">
                    <i class="fa fa-save"></i> Save Template Configuration
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Template Preview Modal -->
<div class="modal fade" id="templatePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Template Structure Preview</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="templatePreviewContent">
                <!-- Preview content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Handle stage selection changes
    $('.stage-checkbox').change(function() {
        updateActivitiesSection();
    });

    function updateActivitiesSection() {
        const selectedStages = $('.stage-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedStages.length === 0) {
            $('#activitiesContainer').html('<p class="text-muted">Select construction stages above to configure activities.</p>');
            $('#subActivitiesContainer').html('<p class="text-muted">Select activities to configure sub-activities and time estimates.</p>');
            return;
        }

        // AJAX call to get activities for selected stages
        $.ajax({
            url: '{{ route("ajax_request") }}',
            method: 'POST',
            data: {
                fx: 'getActivitiesForStages',
                stage_ids: selectedStages,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                let activitiesHtml = '';
                
                if (response.activities && response.activities.length > 0) {
                    response.activities.forEach(function(activity) {
                        activitiesHtml += `
                            <div class="col-md-6 mb-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input activity-checkbox" 
                                           id="activity_${activity.id}" 
                                           name="selected_activities[]" 
                                           value="${activity.id}">
                                    <label class="custom-control-label" for="activity_${activity.id}">
                                        <strong>${activity.name}</strong>
                                        <br><small class="text-muted">${activity.construction_stage.name}</small>
                                    </label>
                                </div>
                            </div>
                        `;
                    });
                    
                    $('#activitiesContainer').html('<div class="row">' + activitiesHtml + '</div>');
                    
                    // Attach change handlers to new activity checkboxes
                    $('.activity-checkbox').change(function() {
                        updateSubActivitiesSection();
                    });
                } else {
                    $('#activitiesContainer').html('<p class="text-muted">No activities found for selected stages.</p>');
                }
            },
            error: function() {
                $('#activitiesContainer').html('<p class="text-danger">Error loading activities. Please try again.</p>');
            }
        });
    }

    function updateSubActivitiesSection() {
        const selectedActivities = $('.activity-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedActivities.length === 0) {
            $('#subActivitiesContainer').html('<p class="text-muted">Select activities to configure sub-activities and time estimates.</p>');
            return;
        }

        // AJAX call to get sub-activities for selected activities
        $.ajax({
            url: '{{ route("ajax_request") }}',
            method: 'POST',
            data: {
                fx: 'getSubActivitiesForActivities',
                activity_ids: selectedActivities,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                let subActivitiesHtml = '';
                
                if (response.subActivities && response.subActivities.length > 0) {
                    response.subActivities.forEach(function(subActivity) {
                        const duration = subActivity.estimated_duration_hours ? 
                            `${subActivity.estimated_duration_hours} ${subActivity.duration_unit}` : 'Not set';
                        const workers = subActivity.labor_requirement ? 
                            `${subActivity.labor_requirement} workers` : 'Not specified';
                        
                        subActivitiesHtml += `
                            <div class="col-md-12 mb-3">
                                <div class="card border-light">
                                    <div class="card-body p-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" 
                                                   id="subactivity_${subActivity.id}" 
                                                   name="selected_sub_activities[]" 
                                                   value="${subActivity.id}">
                                            <label class="custom-control-label" for="subactivity_${subActivity.id}">
                                                <strong>${subActivity.name}</strong>
                                            </label>
                                        </div>
                                        <div class="row mt-1">
                                            <div class="col-sm-4">
                                                <small><i class="fa fa-clock text-primary"></i> Duration: ${duration}</small>
                                            </div>
                                            <div class="col-sm-4">
                                                <small><i class="fa fa-users text-success"></i> ${workers}</small>
                                            </div>
                                            <div class="col-sm-4">
                                                <small><i class="fa fa-star text-warning"></i> ${subActivity.skill_level}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    $('#subActivitiesContainer').html('<div class="row">' + subActivitiesHtml + '</div>');
                } else {
                    $('#subActivitiesContainer').html('<p class="text-muted">No sub-activities found for selected activities.</p>');
                }
            },
            error: function() {
                $('#subActivitiesContainer').html('<p class="text-danger">Error loading sub-activities. Please try again.</p>');
            }
        });
    }

    // Preview Template
    $('#previewTemplate').click(function() {
        const formData = $('#templateBuilderForm').serialize();
        
        $.ajax({
            url: '{{ route("ajax_request") }}',
            method: 'POST',
            data: formData + '&fx=previewBoqTemplate&_token={{ csrf_token() }}',
            success: function(response) {
                $('#templatePreviewContent').html(response.html || response);
                $('#templatePreviewModal').modal('show');
            },
            error: function() {
                alert('Error generating preview. Please try again.');
            }
        });
    });

    // Form submission
    $('#templateBuilderForm').submit(function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: '{{ route("ajax_request") }}',
            method: 'POST',
            data: formData + '&fx=saveBoqTemplateConfiguration&_token={{ csrf_token() }}',
            success: function(response) {
                if (response.success) {
                    alert('Template configuration saved successfully!');
                    $('#ajax-loader-modal').modal('hide');
                    // Refresh the templates page
                    location.reload();
                } else {
                    alert('Error saving template: ' + (response.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('Error saving template configuration. Please try again.');
            }
        });
    });
});
</script>

<style>
.builder-section {
    margin-bottom: 1.5rem;
}

.custom-control-label {
    cursor: pointer;
}

.card.border-light:hover {
    border-color: #007bff !important;
    box-shadow: 0 2px 4px rgba(0,123,255,0.1);
}

.builder-section h6 {
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #eee;
}

#templatePreviewModal .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}
</style>