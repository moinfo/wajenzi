@extends('layouts.backend')

@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-sm-fill h3 my-2">
                <i class="fa fa-cog text-muted mr-2"></i>Bonus Settings
            </h1>
            <nav class="flex-sm-00-auto ml-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb breadcrumb-alt">
                    <li class="breadcrumb-item"><a href="{{ route('architect-bonus.index') }}">Architect Bonus</a></li>
                    <li class="breadcrumb-item active">Settings</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    @endif

    <div class="row">
        <!-- Weight Configuration -->
        <div class="col-md-5">
            <form action="{{ route('architect-bonus.weights.update') }}" method="POST">
                @csrf
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Performance Weights</h3>
                    </div>
                    <div class="block-content">
                        <p class="text-muted">Weights must total 100%. These determine how each factor contributes to the performance score.</p>

                        @foreach($weights as $w)
                            <div class="form-group">
                                <label>
                                    <strong>{{ $w->description }}</strong>
                                    <small class="text-muted">({{ $w->factor }})</small>
                                </label>
                                <div class="input-group">
                                    <input type="number" name="weights[{{ $w->factor }}]" class="form-control weight-input"
                                           value="{{ $w->weight }}" min="0" max="1" step="0.05">
                                    <div class="input-group-append">
                                        <span class="input-group-text">{{ round($w->weight * 100) }}%</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="alert alert-light border">
                            <strong>Total:</strong> <span id="weightTotal">{{ $weights->sum('weight') * 100 }}%</span>
                            <span id="weightStatusIcon"></span>
                            <span id="weightStatusText"></span>
                        </div>
                    </div>
                    <div class="block-content block-content-full block-content-sm bg-body-light text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save mr-1"></i> Save Weights
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Unit Tiers -->
        <div class="col-md-7">
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Unit Tiers (1 Unit = TZS 10,000)</h3>
                </div>
                <div class="block-content block-content-full">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter">
                            <thead>
                                <tr>
                                    <th>Min Amount (TZS)</th>
                                    <th>Max Amount (TZS)</th>
                                    <th class="text-center">Max Units</th>
                                    <th class="text-right">Max Bonus (TZS)</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tiers as $tier)
                                <tr>
                                    <form action="{{ route('architect-bonus.tier.update', $tier->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <td>
                                            <input type="number" name="min_amount" class="form-control form-control-sm"
                                                   value="{{ (int)$tier->min_amount }}" min="0">
                                        </td>
                                        <td>
                                            <input type="number" name="max_amount" class="form-control form-control-sm"
                                                   value="{{ (int)$tier->max_amount }}" min="0">
                                        </td>
                                        <td class="text-center">
                                            <input type="number" name="max_units" class="form-control form-control-sm text-center"
                                                   value="{{ $tier->max_units }}" min="1" style="width:80px;margin:0 auto;">
                                        </td>
                                        <td class="text-right">{{ number_format($tier->max_units * 10000) }}</td>
                                        <td class="text-center">
                                            <button type="submit" class="btn btn-sm btn-alt-primary" title="Save">
                                                <i class="fa fa-save"></i>
                                            </button>
                                        </td>
                                    </form>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js_after')
<script>
document.querySelectorAll('.weight-input').forEach(function(input) {
    input.addEventListener('input', function() {
        var total = 0;
        document.querySelectorAll('.weight-input').forEach(function(i) {
            total += parseFloat(i.value) || 0;
        });
        var pct = Math.round(total * 100);
        document.getElementById('weightTotal').textContent = pct + '%';
        var icon = document.getElementById('weightStatusIcon');
        var text = document.getElementById('weightStatusText');
        if (Math.abs(total - 1.0) < 0.01) {
            icon.className = 'fa fa-check-circle text-success ml-1';
            text.textContent = 'Valid';
            text.className = 'text-success';
        } else {
            icon.className = 'fa fa-exclamation-circle text-danger ml-1';
            text.textContent = 'Must equal 100%';
            text.className = 'text-danger';
        }
    });
});
</script>
@endsection
