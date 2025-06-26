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
                            <p><strong>Building Type:</strong> 
                                @if($template->buildingType)
                                    @if($template->buildingType->parent_id)
                                        {{-- Child building type --}}
                                        <span class="text-muted">{{ $template->buildingType->parent->name ?? 'Unknown Parent' }}</span>
                                        <br>
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-level-up-alt fa-rotate-90"></i>
                                            {{ $template->buildingType->name }}
                                        </span>
                                    @else
                                        {{-- Parent building type --}}
                                        <span class="badge badge-primary">
                                            <i class="fas fa-building"></i>
                                            {{ $template->buildingType->name }}
                                        </span>
                                    @endif
                                @else
                                    <span class="text-muted">Not Set</span>
                                @endif
                            </p>
                            
                            {{-- Specifications --}}
                            @if($template->roof_type || $template->no_of_rooms)
                                <p><strong>Specifications:</strong><br>
                                    @if($template->roof_type)
                                        <span class="badge badge-light">{{ ucwords(str_replace('_', ' ', $template->roof_type)) }}</span>
                                    @endif
                                    @if($template->no_of_rooms)
                                        <span class="badge badge-light">{{ $template->no_of_rooms }} Room{{ $template->no_of_rooms == '1' ? '' : 's' }}</span>
                                    @endif
                                </p>
                            @endif
                            
                            {{-- Measurements --}}
                            @if($template->square_metre || $template->run_metre)
                                <p><strong>Measurements:</strong><br>
                                    @if($template->square_metre)
                                        <span class="text-info">{{ number_format($template->square_metre, 2) }} SQM</span>
                                    @endif
                                    @if($template->square_metre && $template->run_metre)
                                        <br>
                                    @endif
                                    @if($template->run_metre)
                                        <span class="text-success">{{ number_format($template->run_metre, 2) }} RM</span>
                                    @endif
                                </p>
                            @endif
                            
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
                        
                        <!-- Add Construction Stage -->
                        <div id="addStageSection" class="builder-section">
                            <h6 class="text-primary"><i class="fa fa-plus-circle"></i> Add Construction Stage</h6>
                            <p class="text-muted mb-3">Select parent stages to add to your template. You can then choose specific children for each parent.</p>
                            
                            <div class="row">
                                @foreach(($parentStages ?? []) as $index => $stage)
                                    <div class="col-md-6 mb-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input add-stage-checkbox" 
                                                   id="add_stage_{{ $stage->id }}" 
                                                   name="add_stages[]" 
                                                   value="{{ $stage->id }}"
                                                   data-stage-name="{{ $stage->name }}"
                                                   data-stage-description="{{ $stage->description }}">
                                            <label class="custom-control-label" for="add_stage_{{ $stage->id }}">
                                                <span class="stage-number badge badge-primary">{{ $index + 1 }}</span>
                                                <i class="fas fa-layer-group text-primary ml-2"></i>
                                                <strong class="text-primary">{{ $stage->name }}</strong>
                                                @if($stage->description)
                                                    <br><small class="text-muted ml-4">{{ $stage->description }}</small>
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <hr>

                        <!-- Template Structure (Dynamic) -->
                        <div id="templateStructureSection" class="builder-section">
                            <h6 class="text-success"><i class="fa fa-sitemap"></i> Template Structure</h6>
                            <p class="text-muted mb-3">Your selected stages with their children. Check specific children you want to include.</p>
                            
                            <div id="selectedStagesContainer">
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> 
                                    Select parent stages above to configure your template structure here.
                                </div>
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
    // Handle adding/removing parent stages
    $('.add-stage-checkbox').change(function() {
        updateTemplateStructure();
        updateActivitiesSection();
    });
    
    // Handle child stage selection changes in template structure
    $(document).on('change', '.template-child-checkbox', function() {
        updateActivitiesSection();
    });

    function updateTemplateStructure() {
        const selectedParentStages = $('.add-stage-checkbox:checked');
        const container = $('#selectedStagesContainer');
        
        if (selectedParentStages.length === 0) {
            container.html(`
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> 
                    Select parent stages above to configure your template structure here.
                </div>
            `);
            return;
        }
        
        let structureHtml = '';
        
        selectedParentStages.each(function(index) {
            const stageId = $(this).val();
            const stageName = $(this).data('stage-name');
            const stageDescription = $(this).data('stage-description');
            
            structureHtml += `
                <div class="stage-structure-group mb-4" data-parent-stage-id="${stageId}">
                    <div class="parent-stage-structure">
                        <div class="d-flex align-items-center mb-3">
                            <span class="stage-number badge badge-success">${index + 1}</span>
                            <i class="fas fa-layer-group text-success ml-2 mr-2"></i>
                            <div>
                                <strong class="text-success">${stageName}</strong>
                                <input type="hidden" name="selected_stages[]" value="${stageId}">
                                ${stageDescription ? `<br><small class="text-muted">${stageDescription}</small>` : ''}
                            </div>
                        </div>
                        
                        <div class="children-selection ml-4" id="children-${stageId}">
                            <div class="loading-children">
                                <i class="fa fa-spinner fa-spin"></i> Loading children stages...
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        container.html(structureHtml);
        
        // Load children for each selected parent
        selectedParentStages.each(function() {
            const stageId = $(this).val();
            loadChildrenForParent(stageId);
        });
    }
    
    function loadChildrenForParent(parentStageId) {
        // Get children from the existing constructionStages data
        const allStages = @json($constructionStages ?? []);
        console.log('All stages data:', allStages);
        console.log('Looking for children of parent ID:', parentStageId);
        
        // Convert parentStageId to number for proper comparison
        const parentId = parseInt(parentStageId);
        const children = allStages.filter(stage => {
            const stageParentId = stage.parent_id ? parseInt(stage.parent_id) : null;
            const matches = stageParentId === parentId;
            if (matches) {
                console.log('Found child:', stage.name, 'with parent_id:', stage.parent_id);
            }
            return matches;
        });
        
        console.log('Children found:', children);
        
        let childrenHtml = '';
        
        if (children.length > 0) {
            childrenHtml += `
                <div class="children-header mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-arrow-right"></i> 
                            <strong>Select specific children to include (${children.length} available):</strong>
                        </small>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllChildren(${parentStageId})">
                            <i class="fas fa-check-double"></i> Select All
                        </button>
                    </div>
                </div>
                <div class="children-list" id="children-list-${parentStageId}">
            `;
            
            children.forEach((child, index) => {
                childrenHtml += `
                    <div class="child-stage-structure mb-2">
                        <div class="custom-control custom-checkbox child-stage-checkbox">
                            <input type="checkbox" class="custom-control-input template-child-checkbox" 
                                   id="template_child_${child.id}" 
                                   name="selected_stages[]" 
                                   value="${child.id}"
                                   data-parent-id="${parentStageId}">
                            <label class="custom-control-label" for="template_child_${child.id}">
                                <span class="stage-number badge badge-secondary">${index + 1}</span>
                                <i class="fas fa-level-up-alt fa-rotate-90 text-secondary ml-2"></i>
                                <strong class="text-dark">${child.name}</strong>
                                ${child.description ? `<br><small class="text-muted ml-4">${child.description}</small>` : ''}
                            </label>
                        </div>
                    </div>
                `;
            });
            
            childrenHtml += `</div>`;
        } else {
            childrenHtml = `
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> 
                    <strong>This parent stage has no child stages.</strong>
                    <br><small>You can proceed with just the parent stage selected.</small>
                </div>
            `;
        }
        
        $(`#children-${parentStageId}`).html(childrenHtml);
    }
    
    // Function to toggle all children for a parent
    function toggleAllChildren(parentStageId) {
        const childCheckboxes = $(`#children-list-${parentStageId} .template-child-checkbox`);
        const allChecked = childCheckboxes.filter(':checked').length === childCheckboxes.length;
        
        if (allChecked) {
            // Uncheck all
            childCheckboxes.prop('checked', false);
        } else {
            // Check all
            childCheckboxes.prop('checked', true);
        }
        
        // Update the button text
        const button = $(`button[onclick="toggleAllChildren(${parentStageId})"]`);
        if (allChecked) {
            button.html('<i class="fas fa-check-double"></i> Select All');
        } else {
            button.html('<i class="fas fa-times"></i> Unselect All');
        }
        
        // Update activities section
        updateActivitiesSection();
    }

    function updateActivitiesSection() {
        // Get all selected stages (both parent stages and children)
        const selectedParentStages = $('.add-stage-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        const selectedChildStages = $('.template-child-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        const selectedStages = [...selectedParentStages, ...selectedChildStages];

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

/* Construction Stages Hierarchical Styling */
.stages-container {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
}

.stage-group {
    background-color: white;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    padding: 0.75rem;
    margin-bottom: 1rem;
}

.stage-group:last-child {
    margin-bottom: 0;
}

.parent-stage-header {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 0.5rem;
    margin-bottom: 0.5rem;
}

.parent-stage-checkbox .custom-control-label {
    font-size: 1.05em;
    padding-left: 0.5rem;
}

.child-stage-checkbox {
    background-color: #f8f9fa;
    border-radius: 4px;
    padding: 0.5rem;
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
}

.child-stage-checkbox:hover {
    background-color: #e9ecef;
    border-color: #007bff;
}

.child-stage-checkbox .custom-control-label {
    padding-left: 0.5rem;
    width: 100%;
}

.children-stages {
    border-left: 3px solid #007bff;
    padding-left: 1rem;
    margin-left: 1rem;
}

.stage-number {
    font-size: 0.75em;
    font-weight: bold;
    min-width: 2.5rem;
    text-align: center;
    display: inline-block;
}

.parent-stage-label strong {
    color: #007bff !important;
}

.child-stage-label strong {
    color: #495057 !important;
}

/* Visual states for parent-child selection */
.children-stages.parent-selected {
    border-left-color: #28a745;
    background-color: #f8fff9;
}

.children-stages.has-selected-children {
    border-left-color: #ffc107;
    background-color: #fffef8;
}

.children-stages.parent-selected.has-selected-children {
    border-left-color: #17a2b8;
    background-color: #f8fdff;
}

.children-header {
    font-style: italic;
    color: #6c757d;
}

/* Template Structure Styling */
.stage-structure-group {
    background-color: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.parent-stage-structure {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 0.5rem;
}

.children-selection {
    background-color: #f8f9fa;
    border-radius: 6px;
    padding: 1rem;
    border-left: 3px solid #28a745;
    border: 1px solid #e9ecef;
    margin-top: 0.5rem;
}

.children-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 0.5rem;
    background-color: white;
}

.children-header {
    background-color: #f8f9fa;
    padding: 0.75rem;
    border-radius: 4px;
    border: 1px solid #e9ecef;
}

.child-stage-structure {
    background-color: white;
    border-radius: 4px;
    padding: 0.5rem;
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
}

.child-stage-structure:hover {
    background-color: #f8f9fa;
    border-color: #007bff;
}

.loading-children {
    text-align: center;
    color: #6c757d;
    padding: 1rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .children-stages {
        margin-left: 0.5rem;
        padding-left: 0.5rem;
    }
    
    .stage-number {
        min-width: 2rem;
    }
}
</style>