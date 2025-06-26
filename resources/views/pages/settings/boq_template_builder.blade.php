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
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <h6 class="mb-0"><i class="fa fa-sitemap"></i> Template Structure</h6>
                            </div>
                            <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                <div id="templateStructure">
                                    {{--                                    @if(config('app.debug'))--}}
                                    {{--                                        <div class="alert alert-info">--}}
                                    {{--                                            <strong>Debug - Template Variable Check:</strong><br>--}}
                                    {{--                                            Template variable exists: {{ isset($template) ? 'YES' : 'NO' }}<br>--}}
                                    {{--                                            Template is null: {{ $template === null ? 'YES' : 'NO' }}<br>--}}
                                    {{--                                            Template falsy: {{ !$template ? 'YES' : 'NO' }}<br>--}}
                                    {{--                                            Template ID: {{ $template->id ?? 'null' }}<br>--}}
                                    {{--                                            Template Name: {{ $template->name ?? 'null' }}--}}
                                    {{--                                        </div>--}}
                                    {{--                                    @endif--}}
                                    @if($template ?? null)
                                        @if($template->templateStages->count() > 0)
                                            @php
                                                // Group template stages by parent-child hierarchy
                                                $templateStagesByConstructionStage = $template->templateStages->keyBy('construction_stage_id');
                                                $groupedStages = collect();
                                                $processedStages = collect();


                                                // Get parent stages (stages without parent_id)
                                                foreach($template->templateStages as $templateStage) {
                                                    $constructionStage = $templateStage->constructionStage;
                                                    if ($constructionStage && !$constructionStage->parent_id) {
                                                        // This is a parent stage
                                                        $parentGroup = (object)[
                                                            'parent' => $templateStage,
                                                            'children' => collect()
                                                        ];

                                                        // Find children of this parent
                                                        foreach($template->templateStages as $childTemplateStage) {
                                                            $childConstructionStage = $childTemplateStage->constructionStage;
                                                            if ($childConstructionStage && $childConstructionStage->parent_id == $constructionStage->id) {
                                                                $parentGroup->children->push($childTemplateStage);
                                                                $processedStages->push($childTemplateStage->id);
                                                            }
                                                        }

                                                        $groupedStages->push($parentGroup);
                                                        $processedStages->push($templateStage->id);
                                                    }
                                                }

                                                // Add orphaned children (children without parents in template)
                                                foreach($template->templateStages as $templateStage) {
                                                    // Skip if already processed
                                                    if ($processedStages->contains($templateStage->id)) {
                                                        continue;
                                                    }

                                                    $constructionStage = $templateStage->constructionStage;
                                                    if ($constructionStage && $constructionStage->parent_id) {
                                                        // Check if parent is in template
                                                        $parentInTemplate = $templateStagesByConstructionStage->has($constructionStage->parent_id);
                                                        if (!$parentInTemplate) {
                                                            // This child has no parent in template, show as orphan
                                                            $orphanGroup = (object)[
                                                                'parent' => null,
                                                                'children' => collect([$templateStage]),
                                                                'isOrphan' => true
                                                            ];
                                                            $groupedStages->push($orphanGroup);
                                                            $processedStages->push($templateStage->id);
                                                        }
                                                    }
                                                }

                                                // Add any remaining unprocessed stages as standalone
                                                foreach($template->templateStages as $templateStage) {
                                                    if (!$processedStages->contains($templateStage->id)) {
                                                        $standaloneGroup = (object)[
                                                            'parent' => null,
                                                            'children' => collect([$templateStage])
                                                        ];
                                                        $groupedStages->push($standaloneGroup);
                                                    }
                                                }
                                            @endphp

                                            <div class="template-structure-hierarchy">
                                                <div class="accordion" id="stagesAccordion">

                                                    @foreach($groupedStages as $groupIndex => $stageGroup)
                                                        <div class="stage-hierarchy-group mb-3">
                                                            @if($stageGroup->parent)
                                                                {{-- Parent Stage Header --}}
                                                                <div class="parent-stage-card">
                                                                    <div class="card border-primary">
                                                                        <div class="card-header bg-primary text-white">
                                                                            <div class="d-flex justify-content-between align-items-center">
                                                                                <div>
                                                                                    <i class="fas fa-layer-group"></i>
                                                                                    <strong>{{ $stageGroup->parent->constructionStage->name ?? 'Parent Stage' }}</strong>
                                                                                    <span class="badge badge-light ml-2">
                                                                                    {{ $stageGroup->children->count() }} children
                                                                                </span>
                                                                                </div>
                                                                                <form method="post" style="display: inline;" onsubmit="return confirm('Remove parent stage and all its children?');">
                                                                                    @csrf
                                                                                    <input type="hidden" name="template_id" value="{{ $templateId }}">
                                                                                    <input type="hidden" name="action" value="remove_stage">
                                                                                    <input type="hidden" name="stage_id_to_remove" value="{{ $stageGroup->parent->id }}">
                                                                                    <button type="submit" class="btn btn-sm btn-outline-light">
                                                                                        <i class="fa fa-trash"></i>
                                                                                    </button>
                                                                                </form>
                                                                            </div>
                                                                            @if($stageGroup->parent->constructionStage->description)
                                                                                <small class="d-block mt-1 opacity-75">
                                                                                    {{ $stageGroup->parent->constructionStage->description }}
                                                                                </small>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endif

                                                            {{-- Children Stages --}}
                                                            @if($stageGroup->children->count() > 0)
                                                                <div class="children-stages-container {{ $stageGroup->parent ? 'ml-4 mt-2' : '' }}">
                                                                    @foreach($stageGroup->children as $childIndex => $childStage)
                                                                        <div class="child-stage-card mb-2">
                                                                            <div class="card border-success">
                                                                                <div class="card-header" id="heading{{$groupIndex}}_{{$childIndex}}">
                                                                                    <h6 class="mb-0 d-flex justify-content-between align-items-center">
                                                                                        <button class="btn btn-link text-left p-0" type="button" data-toggle="collapse" data-target="#collapse{{$groupIndex}}_{{$childIndex}}">
                                                                                            <i class="fas fa-level-up-alt fa-rotate-90 text-success"></i>
                                                                                            <strong class="text-success">{{ $childStage->constructionStage->name ?? 'Child Stage' }}</strong>
                                                                                            <span class="badge badge-success ml-2">{{ $childStage->templateActivities->count() }} activities</span>
                                                                                        </button>
                                                                                        <form method="post" style="display: inline;" onsubmit="return confirm('Remove this child stage?');">
                                                                                            @csrf
                                                                                            <input type="hidden" name="template_id" value="{{ $templateId }}">
                                                                                            <input type="hidden" name="action" value="remove_stage">
                                                                                            <input type="hidden" name="stage_id_to_remove" value="{{ $childStage->id }}">
                                                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                                                <i class="fa fa-trash"></i>
                                                                                            </button>
                                                                                        </form>
                                                                                    </h6>
                                                                                    @if($childStage->constructionStage->description)
                                                                                        <small class="text-muted">
                                                                                            {{ $childStage->constructionStage->description }}
                                                                                        </small>
                                                                                    @endif
                                                                                </div>
                                                                                <div id="collapse{{$groupIndex}}_{{$childIndex}}" class="collapse show" data-parent="#stagesAccordion">
                                                                                    <div class="card-body">

                                                                                        @if($childStage->templateActivities->count() > 0)
                                                                                            <ul class="list-group">
                                                                                                @foreach($childStage->templateActivities as $activityIndex => $activity)
                                                                                                    <li class="list-group-item">
                                                                                                        <div class="d-flex justify-content-between align-items-center">
                                                                                                            <div>
                                                                                                                <span class="activity-number badge badge-primary mr-2">{{ ($activityIndex + 1) }}</span>
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
                                                                                                                    @foreach($activity->templateSubActivities as $subActivityIndex => $subActivity)
                                                                                                                        <li class="list-group-item border-0 py-1 pl-3" style="background-color: #f8f9fa;">
                                                                                                                            <div class="d-flex justify-content-between align-items-start">
                                                                                                                                <div>
                                                                                                                                    <span class="sub-activity-number badge badge-warning mr-2">{{ ($activityIndex + 1) }}.{{ ($subActivityIndex + 1) }}</span>
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
                                                                                                                                                @foreach($subActivity->subActivity->materials as $materialIndex => $material)
                                                                                                                                                    <small class="d-block text-muted">
                                                                                                                                                        <span class="material-number badge badge-light badge-sm mr-1">{{ ($activityIndex + 1) }}.{{ ($subActivityIndex + 1) }}.{{ ($materialIndex + 1) }}</span>
                                                                                                                                                        {{ $material->boqItem->name ?? 'Unknown Material' }}
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
                                                                                            <div class="alert alert-warning">
                                                                                                <i class="fa fa-exclamation-triangle"></i>
                                                                                                <strong>No activities added yet.</strong>
                                                                                                <br>
                                                                                                <small>
                                                                                                    To add activities to this child stage:
                                                                                                    <ol class="mb-0 mt-1">
                                                                                                        <li>Select "Add Activity" from the action dropdown</li>
                                                                                                        <li>Choose this child stage from the dropdown</li>
                                                                                                        <li>Enter an activity name</li>
                                                                                                        <li>Click "Add Activity"</li>
                                                                                                    </ol>
                                                                                                </small>
                                                                                            </div>
                                                                                        @endif
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                            @endforeach
                                                            @else
                                                                <div class="alert alert-warning">
                                                                    <i class="fa fa-exclamation-triangle"></i> No stages configured yet. Use the action panel to add construction stages.
                                                                </div>
                                                            @endif
                                                        </div> {{-- End accordion --}}
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
                                                <select name="stage_id" class="form-control" required onchange="showStageChildren(this.value)">
                                                    <option value="">Choose Stage...</option>
                                                    @foreach(($parentStages ?? []) as $stage)
                                                        <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                                    @endforeach
                                                </select>

                                                <!-- Children Selection Section -->
                                                <div id="stageChildrenPreview" class="mt-3" style="display: none;">
                                                    <div class="alert alert-success">
                                                        <h6><i class="fa fa-check-circle"></i> Select Specific Children</h6>
                                                        <p class="mb-2">This parent stage has children. Select which ones to include:</p>
                                                        <div id="childrenList" class="mt-2"></div>
                                                        <div class="mt-2">
                                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleAllChildrenSelection()">
                                                                <i class="fa fa-check-double"></i> Select All
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllChildrenSelection(false)">
                                                                <i class="fa fa-times"></i> Unselect All
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-primary" onclick="return submitStageForm()">Add Stage & Children</button>
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
                                                <label>Select Child Stage:</label>
                                                <select name="parent_stage_id" class="form-control" required>
                                                    <option value="">Choose Child Stage...</option>
                                                    @if($template ?? null)
                                                        @php
                                                            // Get only child stages (stages with parent_id)
                                                            $childStages = $template->templateStages->filter(function($templateStage) {
                                                                return $templateStage->constructionStage && $templateStage->constructionStage->parent_id;
                                                            });
                                                        @endphp
                                                        @foreach($childStages as $stage)
                                                            @php
                                                                $constructionStage = $stage->constructionStage;
                                                                $parentStage = $constructionStage->parent;
                                                            @endphp
                                                            <option value="{{ $stage->id }}">
                                                                {{ $constructionStage->name ?? 'Child Stage' }}
                                                                @if($parentStage)
                                                                    <span class="text-muted">(under {{ $parentStage->name }})</span>
                                                                @endif
                                                            </option>
                                                        @endforeach
                                                        @if($childStages->count() == 0)
                                                            <option value="" disabled>No child stages available. Add children stages first.</option>
                                                        @endif
                                                    @endif
                                                </select>
                                                <small class="form-text text-muted">
                                                    <i class="fa fa-info-circle"></i> Activities can only be added to child stages, not parent stages.
                                                </small>
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

                <!-- Template Overview Table -->
                @if($template ?? null)
                    @if($template->templateStages->count() > 0)
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fa fa-table"></i> Template Structure Overview</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped table-sm">
                                                <thead class="thead-dark">
                                                    <tr>
                                                        <th style="width: 5%">#</th>
                                                        <th style="width: 20%">Stage</th>
                                                        <th style="width: 15%">Type</th>
                                                        <th style="width: 25%">Activity</th>
                                                        <th style="width: 25%">Sub-Activity</th>
                                                        <th style="width: 10%">Materials</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $overallIndex = 1;
                                                    @endphp
                                                    @foreach($template->templateStages->sortBy('sort_order') as $stage)
                                                        @php
                                                            $constructionStage = $stage->constructionStage;
                                                            $isParent = $constructionStage && !$constructionStage->parent_id;
                                                            $rowspan = max(1, $stage->templateActivities->sum(function($activity) {
                                                                return max(1, $activity->templateSubActivities->count());
                                                            }));
                                                        @endphp

                                                        @if($stage->templateActivities->count() > 0)
                                                            @php $activityIndex = 1; @endphp
                                                            @foreach($stage->templateActivities as $activity)
                                                                @if($activity->templateSubActivities->count() > 0)
                                                                    @php $subActivityIndex = 1; @endphp
                                                                    @foreach($activity->templateSubActivities as $subActivity)
                                                                        <tr>
                                                                            @if($loop->parent->first && $loop->first)
                                                                                <td rowspan="{{ $rowspan }}" class="align-middle text-center font-weight-bold">{{ $overallIndex++ }}</td>
                                                                                <td rowspan="{{ $rowspan }}" class="align-middle">
                                                                                    @if($isParent)
                                                                                        <i class="fas fa-layer-group text-primary"></i>
                                                                                        <strong class="text-primary">{{ $constructionStage->name }}</strong>
                                                                                    @else
                                                                                        <i class="fas fa-level-up-alt fa-rotate-90 text-success"></i>
                                                                                        <strong class="text-success">{{ $constructionStage->name }}</strong>
                                                                                    @endif
                                                                                </td>
                                                                                <td rowspan="{{ $rowspan }}" class="align-middle">
                                                                                    @if($isParent)
                                                                                        <span class="badge badge-primary">Parent</span>
                                                                                    @else
                                                                                        <span class="badge badge-success">Child</span>
                                                                                    @endif
                                                                                </td>
                                                                            @endif

                                                                            @if($loop->first)
                                                                                @php $activityRowspan = max(1, $activity->templateSubActivities->count()); @endphp
                                                                                <td rowspan="{{ $activityRowspan }}" class="align-middle">
                                                                                    <span class="badge badge-primary mr-1">{{ $activityIndex }}</span>
                                                                                    <i class="fa fa-tasks text-primary"></i>
                                                                                    {{ $activity->activity->name ?? 'Unknown Activity' }}
                                                                                </td>
                                                                            @endif

                                                                            <td>
                                                                                <span class="badge badge-warning mr-1">{{ $activityIndex }}.{{ $subActivityIndex }}</span>
                                                                                <i class="fa fa-puzzle-piece text-warning"></i>
                                                                                {{ $subActivity->subActivity->name ?? 'Unknown Sub-Activity' }}
                                                                                @if($subActivity->subActivity->estimated_duration_hours)
                                                                                    <small class="text-muted d-block">
                                                                                        <i class="fa fa-clock"></i> {{ $subActivity->subActivity->estimated_duration_hours }} {{ $subActivity->subActivity->duration_unit ?? 'hours' }}
                                                                                    </small>
                                                                                @endif
                                                                            </td>
                                                                            <td class="text-center">
                                                                                @if($subActivity->subActivity->materials->count() > 0)
                                                                                    <span class="badge badge-info">{{ $subActivity->subActivity->materials->count() }}</span>
                                                                                    <small class="d-block text-muted mt-1">
                                                                                        @foreach($subActivity->subActivity->materials as $materialIndex => $material)
                                                                                            <div class="mb-1">
                                                                                                <span class="badge badge-light badge-sm">{{ $activityIndex }}.{{ $subActivityIndex }}.{{ $materialIndex + 1 }}</span>
                                                                                                {{ $material->boqItem->name ?? 'Unknown' }}
                                                                                            </div>
                                                                                        @endforeach
                                                                                    </small>
                                                                                @else
                                                                                    <span class="text-muted">-</span>
                                                                                @endif
                                                                            </td>
                                                                        </tr>
                                                                        @php $subActivityIndex++; @endphp
                                                                    @endforeach
                                                                @else
                                                                    <tr>
                                                                        @if($loop->first)
                                                                            <td rowspan="{{ $rowspan }}" class="align-middle text-center font-weight-bold">{{ $overallIndex++ }}</td>
                                                                            <td rowspan="{{ $rowspan }}" class="align-middle">
                                                                                @if($isParent)
                                                                                    <i class="fas fa-layer-group text-primary"></i>
                                                                                    <strong class="text-primary">{{ $constructionStage->name }}</strong>
                                                                                @else
                                                                                    <i class="fas fa-level-up-alt fa-rotate-90 text-success"></i>
                                                                                    <strong class="text-success">{{ $constructionStage->name }}</strong>
                                                                                @endif
                                                                            </td>
                                                                            <td rowspan="{{ $rowspan }}" class="align-middle">
                                                                                @if($isParent)
                                                                                    <span class="badge badge-primary">Parent</span>
                                                                                @else
                                                                                    <span class="badge badge-success">Child</span>
                                                                                @endif
                                                                            </td>
                                                                        @endif

                                                                        <td>
                                                                            <span class="badge badge-primary mr-1">{{ $activityIndex }}</span>
                                                                            <i class="fa fa-tasks text-primary"></i>
                                                                            {{ $activity->activity->name ?? 'Unknown Activity' }}
                                                                        </td>
                                                                        <td class="text-muted">No sub-activities</td>
                                                                        <td class="text-muted text-center">-</td>
                                                                    </tr>
                                                                @endif
                                                                @php $activityIndex++; @endphp
                                                            @endforeach
                                                        @else
                                                            <tr>
                                                                <td class="text-center font-weight-bold">{{ $overallIndex++ }}</td>
                                                                <td>
                                                                    @if($isParent)
                                                                        <i class="fas fa-layer-group text-primary"></i>
                                                                        <strong class="text-primary">{{ $constructionStage->name }}</strong>
                                                                    @else
                                                                        <i class="fas fa-level-up-alt fa-rotate-90 text-success"></i>
                                                                        <strong class="text-success">{{ $constructionStage->name }}</strong>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if($isParent)
                                                                        <span class="badge badge-primary">Parent</span>
                                                                    @else
                                                                        <span class="badge badge-success">Child</span>
                                                                    @endif
                                                                </td>
                                                                <td class="text-muted">No activities</td>
                                                                <td class="text-muted">No sub-activities</td>
                                                                <td class="text-muted text-center">-</td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="mt-3">
                                            <small class="text-muted">
                                                <strong>Legend:</strong>
                                                <span class="badge badge-primary">Parent Stage</span>
                                                <span class="badge badge-success">Child Stage</span>
                                                <span class="badge badge-primary">#</span> Activity Index
                                                <span class="badge badge-warning">#.#</span> Sub-Activity Index
                                                <span class="badge badge-light">#.#.#</span> Material Index
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

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

    // Show children of selected stage
    function showStageChildren(stageId) {
        console.log('🔍 Selected stage ID:', stageId);

        const allStages = @json($constructionStages ?? []);
        console.log('📊 All stages available:', allStages);

        const preview = document.getElementById('stageChildrenPreview');
        const childrenList = document.getElementById('childrenList');

        if (!stageId) {
            preview.style.display = 'none';
            return;
        }

        // Find children of this parent
        const children = allStages.filter(stage => stage.parent_id == stageId);
        console.log('👶 Children found:', children);

        if (children.length > 0) {
            let childrenHtml = '<div class="children-checkboxes">';
            children.forEach((child, index) => {
                childrenHtml += `
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input child-stage-checkbox"
                               id="child_stage_${child.id}"
                               name="selected_children[]"
                               value="${child.id}"
                               data-child-name="${child.name}">
                        <label class="custom-control-label" for="child_stage_${child.id}">
                            <span class="badge badge-secondary mr-2">${index + 1}</span>
                            <strong>${child.name}</strong>
                            ${child.description ? `<br><small class="text-muted ml-4">${child.description}</small>` : ''}
                        </label>
                    </div>
                `;
            });
            childrenHtml += '</div>';

            childrenList.innerHTML = childrenHtml;
            preview.style.display = 'block';
        } else {
            childrenList.innerHTML = '<em class="text-muted">This stage has no child stages.</em>';
            preview.style.display = 'block';
        }
    }

    // Toggle all children selection
    function toggleAllChildrenSelection(selectAll = true) {
        const checkboxes = document.querySelectorAll('.child-stage-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll;
        });

        console.log(`${selectAll ? '✅ Selected' : '❌ Unselected'} all children`);
    }

    // Handle form submission with children
    function submitStageForm() {
        const selectedChildren = document.querySelectorAll('.child-stage-checkbox:checked');
        const parentStageId = document.querySelector('select[name="stage_id"]').value;

        if (!parentStageId) {
            alert('Please select a parent stage first.');
            return false;
        }

        console.log('📋 Submitting parent stage:', parentStageId);
        console.log('👶 Selected children:', Array.from(selectedChildren).map(cb => cb.value));

        // Add hidden inputs for selected children
        const form = document.querySelector('#addStageForm form');

        // Remove existing children inputs
        form.querySelectorAll('input[name="selected_children[]"]').forEach(input => input.remove());

        // Add new children inputs
        selectedChildren.forEach(checkbox => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'selected_children[]';
            hiddenInput.value = checkbox.value;
            form.appendChild(hiddenInput);
        });

        return true; // Allow form submission
    }

    // Handle sub-activity selection for materials assignment
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Page loaded - checking construction stages data');
        const allStages = @json($constructionStages ?? []);
        console.log('📋 Available stages:', allStages.length);

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

<style>
/* Template Structure Hierarchy Styling */
.template-structure-hierarchy {
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 8px;
}

.stage-hierarchy-group {
    margin-bottom: 1.5rem;
}

.parent-stage-card .card {
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.children-stages-container {
    position: relative;
}

.children-stages-container::before {
    content: '';
    position: absolute;
    left: -15px;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(to bottom, #007bff, #28a745);
    border-radius: 2px;
}

.child-stage-card .card {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
}

.child-stage-card .card:hover {
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transform: translateY(-1px);
}

.parent-stage-card .badge {
    font-size: 0.75em;
}

/* Hierarchical Numbering Badges */
.activity-number {
    font-weight: bold;
    font-size: 0.8em;
    min-width: 2rem;
    text-align: center;
}

.sub-activity-number {
    font-weight: bold;
    font-size: 0.75em;
    min-width: 2.5rem;
    text-align: center;
}

.material-number {
    font-weight: bold;
    font-size: 0.7em;
    min-width: 3rem;
    text-align: center;
    border: 1px solid #dee2e6;
}

/* Enhanced visual hierarchy */
.list-group-item {
    border-left: 3px solid transparent;
}

.list-group-item:hover {
    border-left-color: #007bff;
    background-color: #f8f9fa;
}

.sub-activity-number.badge-warning {
    background-color: #ffc107;
    color: #212529;
}

.material-number.badge-light {
    background-color: #f8f9fa;
    color: #495057;
    border-color: #dee2e6;
}

.children-checkboxes .custom-control {
    background-color: #f8f9fa;
    border-radius: 4px;
    padding: 0.5rem;
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
}

.children-checkboxes .custom-control:hover {
    background-color: #e9ecef;
    border-color: #007bff;
}

@media (max-width: 768px) {
    .children-stages-container {
        margin-left: 1rem !important;
    }

    .children-stages-container::before {
        left: -8px;
    }
}
</style>

@endsection
