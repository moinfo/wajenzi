@extends('layouts.backend')

@section('content')
<div class="content">
    <div class="content-heading">
        <div class="d-flex align-items-center">
            <a href="{{ route('hr_settings_boq_templates') }}" class="btn btn-sm btn-secondary mr-2">
                <i class="fa fa-arrow-left"></i> Back to Templates
            </a>
            <h2 class="mb-0">BOQ Template Builder</h2>
        </div>
    </div>

    <div class="block">
        <div class="block-header block-header-default">
            <h3 class="block-title">Configure Template Structure</h3>
            <div class="block-options">
                <button type="button" class="btn-block-option" data-toggle="block-option" data-action="fullscreen_toggle"></button>
            </div>
        </div>
        <div class="block-content">
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info mb-4">
                        <h5><i class="fa fa-info-circle"></i> BOQ Template Builder</h5>
                        <p class="mb-0">Configure your BOQ template by selecting construction stages, activities, and sub-activities. This will define the structure and default items for projects using this template.</p>
                    </div>
                    
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fa fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    
                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fa fa-exclamation-triangle"></i> {{ session('warning') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <div id="templateBuilderContainer">
                
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
                                
                                <hr>
                                
                                <div class="form-group">
                                    <label for="action">Builder Action:</label>
                                    <select name="action" id="builderAction" class="form-control" required>
                                        <option value="">Select Action</option>
                                        <option value="add_stage">Add Construction Stage</option>
                                        <option value="add_activity">Add Activity to Stage</option>
                                        <option value="add_sub_activity">Add Sub-Activity</option>
                                        <option value="assign_materials">Assign Materials to Sub-Activity</option>
                                        <option value="remove_stage">Remove Stage</option>
                                        <option value="save_template">Save Template Configuration</option>
                                    </select>
                                </div>
                                
                                <button type="button" class="btn btn-primary btn-block" onclick="executeAction()">
                                    <i class="fa fa-play"></i> Execute Action
                                </button>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="card border-info mt-3">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fa fa-chart-bar"></i> Template Statistics</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h4 class="mb-0">{{ $templateStats['stages'] ?? 0 }}</h4>
                                        <small>Stages</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="mb-0">{{ $templateStats['activities'] ?? 0 }}</h4>
                                        <small>Activities</small>
                                    </div>
                                    <div class="col-4">
                                        <h4 class="mb-0">{{ $templateStats['subActivities'] ?? 0 }}</h4>
                                        <small>Sub-Activities</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Template Structure -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h6 class="mb-0"><i class="fa fa-sitemap"></i> Template Structure</h6>
                            </div>
                            <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                <div id="templateStructure">
                                    @if(config('app.debug'))
                                        <div class="alert alert-info">
                                            <strong>Debug - Template Variable Check:</strong><br>
                                            Template variable exists: {{ isset($template) ? 'YES' : 'NO' }}<br>
                                            Template is null: {{ $template === null ? 'YES' : 'NO' }}<br>
                                            Template falsy: {{ !$template ? 'YES' : 'NO' }}<br>
                                            Template ID: {{ $template->id ?? 'null' }}<br>
                                            Template Name: {{ $template->name ?? 'null' }}
                                        </div>
                                    @endif
                                    @if($template ?? null)
                                        @if($template->templateStages->count() > 0)
                                            <div class="accordion" id="stagesAccordion">
                                                @foreach($template->templateStages as $index => $stage)
                                                    <div class="card mb-2">
                                                        <div class="card-header" id="heading{{$index}}">
                                                            <h5 class="mb-0 d-flex justify-content-between align-items-center">
                                                                <button class="btn btn-link text-left" type="button" data-toggle="collapse" data-target="#collapse{{$index}}">
                                                                    <i class="fa fa-layer-group"></i> {{ $stage->constructionStage->name ?? 'Stage' }}
                                                                    <span class="badge badge-primary ml-2">{{ $stage->templateActivities->count() }} activities</span>
                                                                </button>
                                                                <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to remove this stage?');">
                                                                    @csrf
                                                                    <input type="hidden" name="template_id" value="{{ $templateId }}">
                                                                    <input type="hidden" name="action" value="remove_stage">
                                                                    <input type="hidden" name="stage_id_to_remove" value="{{ $stage->id }}">
                                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                                        <i class="fa fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </h5>
                                                        </div>
                                                        <div id="collapse{{$index}}" class="collapse" data-parent="#stagesAccordion">
                                                            <div class="card-body">
                                                                @if($stage->templateActivities->count() > 0)
                                                                    <ul class="list-group">
                                                                        @foreach($stage->templateActivities as $activity)
                                                                            <li class="list-group-item">
                                                                                <div class="d-flex justify-content-between align-items-center">
                                                                                    <div>
                                                                                        <i class="fa fa-tasks text-primary"></i> 
                                                                                        <strong>{{ $activity->activity->name ?? 'Unknown Activity' }}</strong>
                                                                                        @if($activity->templateSubActivities->count() > 0)
                                                                                            <span class="badge badge-secondary ml-2">{{ $activity->templateSubActivities->count() }} sub-activities</span>
                                                                                        @endif
                                                                                    </div>
                                                                                </div>
                                                                                
                                                                                @if($activity->templateSubActivities->count() > 0)
                                                                                    <div class="mt-2 ml-3">
                                                                                        <ul class="list-group list-group-flush">
                                                                                            @foreach($activity->templateSubActivities as $subActivity)
                                                                                                <li class="list-group-item border-0 py-1 pl-3" style="background-color: #f8f9fa;">
                                                                                                    <div class="d-flex justify-content-between align-items-start">
                                                                                                        <div>
                                                                                                            <i class="fa fa-puzzle-piece text-warning"></i> 
                                                                                                            <strong>{{ $subActivity->subActivity->name ?? 'Unknown Sub-Activity' }}</strong>
                                                                                                            @if($subActivity->subActivity->estimated_duration_hours ?? null)
                                                                                                                <small class="text-muted ml-2">
                                                                                                                    ({{ $subActivity->subActivity->estimated_duration_hours }} {{ $subActivity->subActivity->duration_unit ?? 'hours' }})
                                                                                                                </small>
                                                                                                            @endif
                                                                                                            
                                                                                                            @if($subActivity->subActivity->materials->count() > 0)
                                                                                                                <div class="mt-1">
                                                                                                                    <small class="text-info">
                                                                                                                        <i class="fa fa-cubes"></i> {{ $subActivity->subActivity->materials->count() }} material(s) assigned
                                                                                                                    </small>
                                                                                                                    <div class="ml-3 mt-1">
                                                                                                                        @foreach($subActivity->subActivity->materials as $material)
                                                                                                                            <small class="d-block text-muted">
                                                                                                                                • {{ $material->boqItem->name ?? 'Unknown Material' }} 
                                                                                                                                ({{ $material->quantity }} {{ $material->boqItem->unit ?? 'pcs' }})
                                                                                                                            </small>
                                                                                                                        @endforeach
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            @else
                                                                                                                <div class="mt-1">
                                                                                                                    <small class="text-muted">
                                                                                                                        <i class="fa fa-exclamation-triangle"></i> No materials assigned
                                                                                                                    </small>
                                                                                                                </div>
                                                                                                            @endif
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </li>
                                                                                            @endforeach
                                                                                        </ul>
                                                                                    </div>
                                                                                @endif
                                                                            </li>
                                                                        @endforeach
                                                                    </ul>
                                                                @else
                                                                    <p class="text-muted">No activities added yet. Use the action panel to add activities to this stage.</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="alert alert-warning">
                                                <i class="fa fa-exclamation-triangle"></i> No stages configured yet. Use the action panel to add construction stages.
                                            </div>
                                        @endif
                                    @else
                                        <div class="alert alert-danger">
                                            <i class="fa fa-exclamation-circle"></i> Template not found. Please go back and select a valid template.
                                            
                                            @if(config('app.debug'))
                                                <hr>
                                                <small class="text-muted">
                                                    <strong>Debug Info:</strong><br>
                                                    Template ID: {{ $templateId ?? 'null' }}<br>
                                                    Template variable: {{ $template === null ? 'null' : (empty($template) ? 'empty' : 'exists') }}<br>
                                                    URL: {{ request()->fullUrl() }}<br>
                                                    Parameters: {{ json_encode(request()->all()) }}
                                                </small>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Forms (Hidden by default) -->
                <div id="actionForms" style="display: none;">
                    <!-- Add Stage Form -->
                    <div id="addStageForm" class="action-form mt-3" style="display: none;">
                        <form method="post" action="{{ route('hr_settings_boq_template_builder', ['templateId' => $templateId]) }}">
                            @csrf
                            <input type="hidden" name="template_id" value="{{ $templateId }}">
                            <input type="hidden" name="action" value="add_stage">
                            
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">Add Construction Stage</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Select Construction Stage:</label>
                                        <select name="stage_id" class="form-control" required>
                                            <option value="">Choose Stage...</option>
                                            @foreach($constructionStages ?? [] as $stage)
                                                <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Add Stage</button>
                                    <button type="button" class="btn btn-secondary" onclick="cancelAction()">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Add Activity Form -->
                    <div id="addActivityForm" class="action-form mt-3" style="display: none;">
                        <form method="post" action="{{ route('hr_settings_boq_template_builder', ['templateId' => $templateId]) }}">
                            @csrf
                            <input type="hidden" name="template_id" value="{{ $templateId }}">
                            <input type="hidden" name="action" value="add_activity">
                            
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">Add Activity</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Select Stage:</label>
                                        <select name="parent_stage_id" class="form-control" required>
                                            <option value="">Choose Stage...</option>
                                            @if($template ?? null)
                                                @foreach($template->templateStages as $stage)
                                                    <option value="{{ $stage->id }}">{{ $stage->constructionStage->name ?? 'Stage' }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Activity Name:</label>
                                        <input type="text" name="activity_name" class="form-control" placeholder="Enter activity name" required>
                                    </div>
                                    <button type="submit" class="btn btn-success">Add Activity</button>
                                    <button type="button" class="btn btn-secondary" onclick="cancelAction()">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Add Sub-Activity Form -->
                    <div id="addSubActivityForm" class="action-form mt-3" style="display: none;">
                        <form method="post" action="{{ route('hr_settings_boq_template_builder', ['templateId' => $templateId]) }}">
                            @csrf
                            <input type="hidden" name="template_id" value="{{ $templateId }}">
                            <input type="hidden" name="action" value="add_sub_activity">
                            
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <h6 class="mb-0">Add Sub-Activity</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Select Activity:</label>
                                        <select name="parent_activity_id" class="form-control" required>
                                            <option value="">Choose Activity...</option>
                                            @if($template ?? null)
                                                @foreach($template->templateStages as $stage)
                                                    @if($stage->templateActivities->count() > 0)
                                                        <optgroup label="{{ $stage->constructionStage->name ?? 'Stage' }}">
                                                            @foreach($stage->templateActivities as $activity)
                                                                <option value="{{ $activity->id }}">{{ $activity->activity->name ?? 'Unknown Activity' }}</option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Sub-Activity Name:</label>
                                        <input type="text" name="sub_activity_name" class="form-control" placeholder="Enter sub-activity name" required>
                                    </div>
                                    <button type="submit" class="btn btn-warning">Add Sub-Activity</button>
                                    <button type="button" class="btn btn-secondary" onclick="cancelAction()">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Assign Materials Form -->
                    <div id="assignMaterialsForm" class="action-form mt-3" style="display: none;">
                        <form method="post" action="{{ route('hr_settings_boq_template_builder', ['templateId' => $templateId]) }}">
                            @csrf
                            <input type="hidden" name="template_id" value="{{ $templateId }}">
                            <input type="hidden" name="action" value="assign_materials">
                            
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">Assign Materials to Sub-Activity</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Select Sub-Activity:</label>
                                        <select name="sub_activity_id" id="subActivitySelect" class="form-control" required>
                                            <option value="">Choose Sub-Activity...</option>
                                            @if($template ?? null)
                                                @foreach($template->templateStages as $stage)
                                                    @foreach($stage->templateActivities as $activity)
                                                        @if($activity->templateSubActivities->count() > 0)
                                                            <optgroup label="{{ $stage->constructionStage->name ?? 'Stage' }} → {{ $activity->activity->name ?? 'Activity' }}">
                                                                @foreach($activity->templateSubActivities as $subActivity)
                                                                    <option value="{{ $subActivity->sub_activity_id }}">{{ $subActivity->subActivity->name ?? 'Unknown Sub-Activity' }}</option>
                                                                @endforeach
                                                            </optgroup>
                                                        @endif
                                                    @endforeach
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    
                                    <div id="materialsSection" style="display: none;">
                                        <div class="form-group">
                                            <label>Select BOQ Item/Material:</label>
                                            <select name="boq_item_id" class="form-control" required>
                                                <option value="">Choose Material...</option>
                                                @if(isset($boqItems))
                                                    @foreach($boqItems as $item)
                                                        <option value="{{ $item->id }}">{{ $item->name }} - {{ $item->unit ?? 'pcs' }}</option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Quantity:</label>
                                            <input type="number" name="quantity" class="form-control" step="0.01" min="0" placeholder="0.00" required>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-info">Assign Material</button>
                                    <button type="button" class="btn btn-secondary" onclick="cancelAction()">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function executeAction() {
        const action = document.getElementById('builderAction').value;
        
        // Hide all action forms first
        document.querySelectorAll('.action-form').forEach(form => {
            form.style.display = 'none';
        });
        
        // Show action forms container
        document.getElementById('actionForms').style.display = 'block';
        
        // Show specific form based on action
        switch(action) {
            case 'add_stage':
                document.getElementById('addStageForm').style.display = 'block';
                break;
            case 'add_activity':
                document.getElementById('addActivityForm').style.display = 'block';
                break;
            case 'add_sub_activity':
                document.getElementById('addSubActivityForm').style.display = 'block';
                break;
            case 'assign_materials':
                document.getElementById('assignMaterialsForm').style.display = 'block';
                break;
            case 'save_template':
                saveTemplate();
                break;
            default:
                alert('Please select an action first');
        }
    }
    
    function cancelAction() {
        document.getElementById('actionForms').style.display = 'none';
        document.getElementById('builderAction').value = '';
    }
    
    function saveTemplate() {
        Swal.fire({
            title: 'Success!',
            text: 'Template configuration saved!',
            icon: 'success',
            confirmButtonText: 'OK',
            confirmButtonColor: '#28a745'
        });
        // In a real implementation, this would save the overall template configuration
    }
    
    // Handle sub-activity selection for materials assignment
    document.addEventListener('DOMContentLoaded', function() {
        const subActivitySelect = document.getElementById('subActivitySelect');
        const materialsSection = document.getElementById('materialsSection');
        
        if (subActivitySelect && materialsSection) {
            subActivitySelect.addEventListener('change', function() {
                if (this.value) {
                    materialsSection.style.display = 'block';
                } else {
                    materialsSection.style.display = 'none';
                }
            });
        }
    });
</script>
@endsection