{{-- project_boq_templates.blade.php - BOQ Templates List --}}
@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">BOQ Templates
                <small class="text-muted">({{ $templates->count() }} templates)</small>
                <div class="float-right">
                    <a href="{{ route('project_boqs') }}" class="btn btn-rounded min-width-125 mb-10 btn-alt-secondary">
                        <i class="si si-arrow-left">&nbsp;</i>Back to BOQs
                    </a>
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Saved Templates</h3>
                    </div>
                    <div class="block-content block-content-full">
                        @if($templates->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-vcenter table-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 50px;">#</th>
                                            <th>Template Name</th>
                                            <th>Source Project</th>
                                            <th class="text-center">Sections</th>
                                            <th class="text-center">Items</th>
                                            <th class="text-right">Total (TZS)</th>
                                            <th>Created By</th>
                                            <th>Created</th>
                                            <th class="text-center" style="width: 80px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($templates as $template)
                                            <tr id="tpl-tr-{{ $template->id }}">
                                                <td class="text-center">{{ $loop->index + 1 }}</td>
                                                <td>
                                                    <strong>{{ $template->name }}</strong>
                                                    @if($template->description)
                                                        <br><small class="text-muted">{{ $template->description }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($template->sourceBoq && $template->sourceBoq->project)
                                                        {{ $template->sourceBoq->project->project_name }}
                                                        <small class="text-muted">v{{ $template->sourceBoq->version }}</small>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ $template->sections()->count() }}</td>
                                                <td class="text-center">{{ $template->items()->count() }}</td>
                                                <td class="text-right">{{ number_format($template->total_amount, 2) }}</td>
                                                <td>{{ $template->creator->name ?? '—' }}</td>
                                                <td>{{ $template->created_at->format('d/m/Y') }}</td>
                                                <td class="text-center">
                                                    <div class="btn-group btn-group-xs">
                                                        <a href="{{ route('project_boq_template.show', $template->id) }}"
                                                            class="btn btn-xs btn-success" title="View Template">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <form method="POST" action="{{ route('project_boq_template.delete', $template->id) }}"
                                                            onsubmit="return confirm('Delete this template? This cannot be undone.');"
                                                            style="display: inline;">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-xs btn-danger" title="Delete Template">
                                                                <i class="fa fa-times"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-30">
                                <i class="si si-layers fa-3x text-muted mb-10"></i>
                                <p class="text-muted">No templates saved yet.</p>
                                <p class="text-muted" style="font-size: 12px;">
                                    Open any BOQ and click <strong>More > Save as Template</strong> to create one.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
