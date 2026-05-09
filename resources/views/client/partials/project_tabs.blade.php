<div class="m-tabs">
    <a href="{{ route('client.project.show', $project->id) }}" class="m-tab {{ request()->routeIs('client.project.show') ? 'active' : '' }}">
        <i class="fas fa-th-large me-1"></i> Overview
    </a>
    <a href="{{ route('client.project.boq', $project->id) }}" class="m-tab {{ request()->routeIs('client.project.boq') ? 'active' : '' }}">
        <i class="fas fa-list-ol me-1"></i> BOQ
    </a>
    <a href="{{ route('client.project.schedule', $project->id) }}" class="m-tab {{ request()->routeIs('client.project.schedule') ? 'active' : '' }}">
        <i class="fas fa-calendar-alt me-1"></i> Schedule
    </a>
    <a href="{{ route('client.project.financials', $project->id) }}" class="m-tab {{ request()->routeIs('client.project.financials') ? 'active' : '' }}">
        <i class="fas fa-money-bill-wave me-1"></i> Financials
    </a>
    <a href="{{ route('client.project.documents', $project->id) }}" class="m-tab {{ request()->routeIs('client.project.documents') ? 'active' : '' }}">
        <i class="fas fa-folder-open me-1"></i> Documents
    </a>
    <a href="{{ route('client.project.gallery', $project->id) }}" class="m-tab {{ request()->routeIs('client.project.gallery') ? 'active' : '' }}">
        <i class="fas fa-images me-1"></i> Gallery
    </a>
    <a href="{{ route('client.project.reports', $project->id) }}" class="m-tab {{ request()->routeIs('client.project.reports') ? 'active' : '' }}">
        <i class="fas fa-clipboard-list me-1"></i> Reports
    </a>
    @if(\App\Models\ProjectStructuralDesign::where('project_id', $project->id)->where('status','approved')->exists())
    <a href="{{ route('client.project.structural_design', $project->id) }}" class="m-tab {{ request()->routeIs('client.project.structural_design') ? 'active' : '' }}">
        <i class="fas fa-drafting-compass me-1"></i> Structural Design
    </a>
    @endif
    @if(\App\Models\ProjectServiceDesign::where('project_id', $project->id)->where('status','approved')->exists())
    <a href="{{ route('client.project.service_design', $project->id) }}" class="m-tab {{ request()->routeIs('client.project.service_design') ? 'active' : '' }}">
        <i class="fas fa-tools me-1"></i> Service Design
    </a>
    @endif
</div>
