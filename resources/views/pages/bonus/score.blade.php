@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">
                <i class="fa fa-star text-warning mr-2"></i>Score Task: {{ $task->task_number }}
            </h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item"><a href="{{ route('architect-bonus.index') }}">Architect Bonus</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('architect-bonus.show', $task->id) }}">{{ $task->task_number }}</a></li>
                    <li class="breadcrumb-item active">Score</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    <!-- Task Summary -->
    <div class="alert alert-info">
        <div class="row">
            <div class="col-md-3"><strong>Project:</strong> {{ $task->project_name }}</div>
            <div class="col-md-3"><strong>Architect:</strong> {{ $task->architect->name }}</div>
            <div class="col-md-3"><strong>Budget:</strong> TZS {{ number_format($task->project_budget) }}</div>
            <div class="col-md-3"><strong>Max Units:</strong> {{ $task->max_units }} (TZS {{ number_format($task->max_units * 10000) }})</div>
        </div>
        <div class="row mt-2">
            <div class="col-md-3"><strong>Start:</strong> {{ $task->start_date->format('d M Y') }}</div>
            <div class="col-md-3"><strong>Scheduled:</strong> {{ $task->scheduled_completion_date->format('d M Y') }}</div>
            <div class="col-md-3"><strong>Duration:</strong> {{ $task->scheduled_days }} working days</div>
            <div class="col-md-3">
                <strong>No-bonus if delay >:</strong>
                <span class="text-danger">{{ ceil($task->scheduled_days * 0.5) }} days</span>
            </div>
        </div>
    </div>

    <form action="{{ route('architect-bonus.score.update', $task->id) }}" method="POST">
        @csrf
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Performance Scoring</h3>
            </div>
            <div class="block-content">
                <div class="row">
                    <!-- Actual Completion -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Actual Completion Date</strong> <span class="text-danger">*</span></label>
                            <input type="date" name="actual_completion_date" id="actualDate"
                                   class="form-control @error('actual_completion_date') is-invalid @enderror"
                                   value="{{ old('actual_completion_date', $task->actual_completion_date?->format('Y-m-d')) }}"
                                   min="{{ $task->start_date->format('Y-m-d') }}" required>
                            @error('actual_completion_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted" id="spPreview">SP will be calculated</small>
                        </div>
                    </div>

                    <!-- Design Quality -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Design Quality Score (DQ)</strong> <span class="text-danger">*</span></label>
                            <input type="number" name="design_quality_score" id="dqScore"
                                   class="form-control @error('design_quality_score') is-invalid @enderror"
                                   value="{{ old('design_quality_score', $task->design_quality_score) }}"
                                   min="0.4" max="1.0" step="0.05" required>
                            @error('design_quality_score')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">Scale: 0.40 (poor) to 1.00 (excellent)</small>
                        </div>
                    </div>

                    <!-- Client Revisions -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Client Revisions</strong> <span class="text-danger">*</span></label>
                            <input type="number" name="client_revisions" id="clientRevisions"
                                   class="form-control @error('client_revisions') is-invalid @enderror"
                                   value="{{ old('client_revisions', $task->client_revisions ?? 1) }}"
                                   min="1" max="20" required>
                            @error('client_revisions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted" id="caPreview">CA = 1/revisions</small>
                        </div>
                    </div>
                </div>

                <!-- Live Preview -->
                <div class="alert alert-light border mt-3" id="previewBox">
                    <h5 class="mb-2"><i class="fa fa-calculator mr-1"></i> Live Calculation Preview</h5>
                    <div class="row text-center">
                        <div class="col-md-3">
                            <strong>SP</strong><br>
                            <span id="prevSP" class="h4">-</span>
                            <br><small class="text-muted">x {{ $weights['schedule'] ?? 0.4 }}</small>
                        </div>
                        <div class="col-md-3">
                            <strong>DQ</strong><br>
                            <span id="prevDQ" class="h4">-</span>
                            <br><small class="text-muted">x {{ $weights['quality'] ?? 0.4 }}</small>
                        </div>
                        <div class="col-md-3">
                            <strong>CA</strong><br>
                            <span id="prevCA" class="h4">-</span>
                            <br><small class="text-muted">x {{ $weights['client'] ?? 0.2 }}</small>
                        </div>
                        <div class="col-md-3">
                            <strong>Result</strong><br>
                            <span id="prevPS" class="h4 text-primary">-</span>
                            <br><span id="prevBonus" class="text-success font-weight-bold">-</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="block-content block-content-full block-content-sm bg-body-light">
                <div class="row">
                    <div class="col-6">
                        <a href="{{ route('architect-bonus.show', $task->id) }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left mr-1"></i> Cancel
                        </a>
                    </div>
                    <div class="col-6 text-right">
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-check mr-1"></i> Submit Score
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('js_after')
<script>
(function() {
    var scheduledDays = {{ $task->scheduled_days }};
    var startDate = new Date('{{ $task->start_date->format("Y-m-d") }}');
    var maxUnits = {{ $task->max_units }};
    var wSP = {{ $weights['schedule'] ?? 0.4 }};
    var wDQ = {{ $weights['quality'] ?? 0.4 }};
    var wCA = {{ $weights['client'] ?? 0.2 }};

    function countWeekdays(start, end) {
        var count = 0;
        var cur = new Date(start);
        while (cur < end) {
            var day = cur.getDay();
            if (day !== 0 && day !== 6) count++;
            cur.setDate(cur.getDate() + 1);
        }
        return Math.max(count, 1);
    }

    function updatePreview() {
        var actualDateVal = document.getElementById('actualDate').value;
        var dq = parseFloat(document.getElementById('dqScore').value) || 0;
        var revisions = parseInt(document.getElementById('clientRevisions').value) || 1;

        var ca = 1 / Math.max(1, revisions);
        document.getElementById('caPreview').textContent = 'CA = 1/' + revisions + ' = ' + ca.toFixed(2);
        document.getElementById('prevCA').textContent = ca.toFixed(3);
        document.getElementById('prevDQ').textContent = dq.toFixed(2);

        if (!actualDateVal) {
            document.getElementById('prevSP').textContent = '-';
            document.getElementById('prevPS').textContent = '-';
            document.getElementById('prevBonus').textContent = '-';
            return;
        }

        var actualDate = new Date(actualDateVal);
        var actualDays = countWeekdays(startDate, actualDate);
        var sp = Math.min(scheduledDays / actualDays, 1.1);
        var delay = actualDays - scheduledDays;

        document.getElementById('spPreview').textContent = actualDays + ' working days' +
            (delay > 0 ? ' (' + delay + ' days late)' : delay < 0 ? ' (' + Math.abs(delay) + ' days early)' : ' (on time)');
        document.getElementById('prevSP').textContent = sp.toFixed(3);

        // No bonus check
        if (delay > scheduledDays * 0.5) {
            document.getElementById('prevPS').textContent = '0';
            document.getElementById('prevPS').className = 'h4 text-danger';
            document.getElementById('prevBonus').textContent = 'NO BONUS (excessive delay)';
            document.getElementById('prevBonus').className = 'text-danger font-weight-bold';
            return;
        }

        var ps = (wSP * sp) + (wDQ * dq) + (wCA * ca);
        var finalUnits = Math.min(Math.round(maxUnits * ps), maxUnits);
        var bonus = finalUnits * 10000;

        document.getElementById('prevPS').textContent = ps.toFixed(3);
        document.getElementById('prevPS').className = 'h4 text-primary';
        document.getElementById('prevBonus').textContent = finalUnits + ' units = TZS ' + bonus.toLocaleString();
        document.getElementById('prevBonus').className = 'text-success font-weight-bold';
    }

    document.getElementById('actualDate').addEventListener('change', updatePreview);
    document.getElementById('dqScore').addEventListener('input', updatePreview);
    document.getElementById('clientRevisions').addEventListener('input', updatePreview);

    updatePreview();
})();
</script>
@endsection
