@extends('layouts.backend')
@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Site Visit Cost Calculator</h1>
            <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">Calculators</li>
                    <li class="breadcrumb-item active">Site Visit</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    <div class="row g-4">

        {{-- ── Left column: inputs ────────────────────────────────── --}}
        <div class="col-lg-8">

            {{-- Currency selector --}}
            <div class="block block-rounded mb-3">
                <div class="block-content py-3">
                    <div class="row g-3">
                        <div class="col-sm-5">
                            <label class="form-label fw-semibold fs-sm mb-1">Display Currency</label>
                            <select id="currencySelect" class="form-select form-select-sm">
                                @foreach($currencies as $cur)
                                <option value="{{ $cur->rate_to_usd }}"
                                    data-code="{{ $cur->code }}"
                                    data-symbol="{{ $cur->symbol }}"
                                    {{ $cur->code === 'TZS' ? 'selected' : '' }}>
                                    {{ $cur->code }} &mdash; {{ $cur->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-7 d-flex align-items-end">
                            <div class="alert alert-info fs-sm py-2 mb-0 flex-grow-1">
                                <i class="fa fa-info-circle me-1"></i>
                                Preset costs are stored in TZS and converted to the selected currency.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Location select --}}
            <div class="block block-rounded mb-3">
                <div class="block-header block-header-default">
                    <h3 class="block-title">1. Select Destination</h3>
                </div>
                <div class="block-content py-3">
                    <div class="row g-2">
                        @foreach($locations as $loc)
                        <div class="col-sm-6 col-lg-4">
                            <div class="location-card border rounded p-3"
                                style="cursor:pointer"
                                data-id="{{ $loc->id }}"
                                data-base="{{ $loc->base_cost_tzs }}"
                                data-travel="{{ $loc->preset_travel_tzs }}"
                                data-local="{{ $loc->preset_local_tzs }}"
                                data-allowance="{{ $loc->preset_allowance_tzs }}">
                                <div class="fw-semibold fs-sm">{{ $loc->name }}</div>
                                <div class="text-muted" style="font-size:11px">
                                    Base: TZS {{ number_format($loc->base_cost_tzs, 0) }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Cost components --}}
            <div class="block block-rounded mb-3" id="costBlock" style="display:none">
                <div class="block-header block-header-default">
                    <h3 class="block-title">2. Cost Components</h3>
                </div>
                <div class="block-content py-3">
                    <p class="fs-sm text-muted mb-3">
                        Preset values are loaded from the selected location. You can override them manually.
                    </p>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold fs-sm">Travel Cost</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" id="sym-travel">TZS</span>
                                <input type="number" id="costTravel" class="form-control" min="0" step="1000">
                            </div>
                            <div class="form-text" id="preset-travel-hint"></div>
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label fw-semibold fs-sm">Local Transport</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" id="sym-local">TZS</span>
                                <input type="number" id="costLocal" class="form-control" min="0" step="1000">
                            </div>
                            <div class="form-text" id="preset-local-hint"></div>
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label fw-semibold fs-sm">Daily Allowance</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" id="sym-allowance">TZS</span>
                                <input type="number" id="costAllowance" class="form-control" min="0" step="1000">
                            </div>
                            <div class="form-text" id="preset-allowance-hint"></div>
                        </div>

                        <div class="col-sm-6">
                            <label class="form-label fw-semibold fs-sm">Number of Days</label>
                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-sm btn-alt-secondary day-adj" data-delta="-1" style="width:34px;height:34px">&#8722;</button>
                                <span id="dayVal" class="fs-4 fw-bold" style="min-width:2rem;text-align:center">1</span>
                                <button class="btn btn-sm btn-alt-secondary day-adj" data-delta="1"  style="width:34px;height:34px">+</button>
                                <span class="text-muted fs-sm">day(s)</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold fs-sm">Additional Notes</label>
                            <input type="text" id="visitNotes" class="form-control form-control-sm"
                                placeholder="e.g. client name, purpose of visit">
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button class="btn btn-sm btn-alt-warning" id="resetPreset">
                            <i class="fa fa-undo me-1"></i> Reset to Presets
                        </button>
                    </div>
                </div>
            </div>

        </div>{{-- /left col --}}

        {{-- ── Right column: result ────────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="sticky-top" style="top:4.5rem">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Cost Summary</h3>
                    </div>
                    <div class="block-content py-3" id="resultPanel">
                        <p class="text-muted fs-sm text-center py-4 mb-0">Select a location to see costs</p>
                    </div>
                </div>
                <p class="text-muted text-center mb-0" style="font-size:11px">All amounts VAT exclusive &middot; Converted at prevailing rate</p>
            </div>
        </div>

    </div>{{-- /row --}}
</div>
@endsection

@section('js_after')
<script>
(function () {
    'use strict';

    var TZS_RATE = {{ (float) $tzsRate }};
    var days     = 1;
    var selectedCard = null;

    // ── DOM helpers ────────────────────────────────────────────────────
    function gid(id) { return document.getElementById(id); }

    function ce(tag, opts) {
        var el = document.createElement(tag);
        if (!opts) return el;
        if (opts.cls)          el.className   = opts.cls;
        if (opts.text != null) el.textContent = opts.text;
        if (opts.style)        el.style.cssText = opts.style;
        if (opts.attrs) Object.keys(opts.attrs).forEach(function (k) { el.setAttribute(k, opts.attrs[k]); });
        return el;
    }

    function ap(parent, child) { parent.appendChild(child); return child; }

    function clearEl(el) {
        while (el.firstChild) el.removeChild(el.firstChild);
    }

    // ── Currency ───────────────────────────────────────────────────────
    function getCur() {
        var sel = gid('currencySelect');
        var opt = sel.options[sel.selectedIndex];
        return {
            rate:   parseFloat(opt.value) || 1,
            code:   opt.dataset.code   || 'TZS',
            symbol: opt.dataset.symbol || 'TZS'
        };
    }

    function tzsToDisplay(tzs) {
        var c   = getCur();
        var amt = (c.code === 'TZS') ? tzs : (tzs / TZS_RATE) * c.rate;
        var sym = c.code === 'TZS' ? 'TZS ' : (c.symbol + ' ');
        return sym + Math.round(amt).toLocaleString();
    }

    function updateSymbols() {
        var c = getCur();
        var sym = c.code === 'TZS' ? 'TZS' : c.code;
        ['sym-travel', 'sym-local', 'sym-allowance'].forEach(function (id) {
            gid(id).textContent = sym;
        });
    }

    // ── Get input values (in TZS internally) ──────────────────────────
    function inputTZS(inputId) {
        var val = parseFloat(gid(inputId).value) || 0;
        var c   = getCur();
        // inputs are shown in display currency, convert back to TZS for calculation
        if (c.code === 'TZS') return val;
        return (val / c.rate) * TZS_RATE;
    }

    function displayVal(tzs) {
        var c = getCur();
        if (c.code === 'TZS') return Math.round(tzs);
        return Math.round((tzs / TZS_RATE) * c.rate);
    }

    // ── Load location presets into inputs ─────────────────────────────
    function loadPresets(card) {
        var base      = parseFloat(card.dataset.base)      || 0;
        var travel    = parseFloat(card.dataset.travel)    || 0;
        var local     = parseFloat(card.dataset.local)     || 0;
        var allowance = parseFloat(card.dataset.allowance) || 0;

        gid('costTravel').value    = displayVal(travel);
        gid('costLocal').value     = displayVal(local);
        gid('costAllowance').value = displayVal(allowance);

        var c = getCur();
        var sym = c.code === 'TZS' ? 'TZS' : c.code;

        gid('preset-travel-hint').textContent    = 'Preset: ' + sym + ' ' + displayVal(travel).toLocaleString();
        gid('preset-local-hint').textContent     = 'Preset: ' + sym + ' ' + displayVal(local).toLocaleString();
        gid('preset-allowance-hint').textContent = 'Preset: ' + sym + ' ' + displayVal(allowance).toLocaleString();
    }

    // ── Result rendering ───────────────────────────────────────────────
    function renderResult() {
        var panel = gid('resultPanel');
        clearEl(panel);

        if (!selectedCard) {
            ap(panel, ce('p', { cls: 'text-muted fs-sm text-center py-4 mb-0', text: 'Select a location to see costs' }));
            return;
        }

        var travel    = inputTZS('costTravel');
        var local     = inputTZS('costLocal');
        var allowance = inputTZS('costAllowance');
        var perDay    = travel + local + allowance;
        var total     = perDay * days;
        var locName   = selectedCard.querySelector('.fw-semibold').textContent;

        var lines = [
            { label: 'Travel cost',    value: tzsToDisplay(travel)    },
            { label: 'Local transport',value: tzsToDisplay(local)     },
            { label: 'Daily allowance',value: tzsToDisplay(allowance) },
            { label: 'Per-day total',  value: tzsToDisplay(perDay)    },
            { label: 'Days',           value: days + ' day' + (days > 1 ? 's' : '') }
        ];

        // Result card
        var card = ce('div', { cls: 'p-3 bg-body-secondary rounded-3 mb-3' });
        ap(card, ce('div', {
            cls: 'fw-bold mb-2',
            style: 'font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#6c757d',
            text: 'Cost Breakdown — ' + locName
        }));

        lines.forEach(function (line) {
            var row = ce('div', { cls: 'd-flex justify-content-between py-1 border-bottom fs-sm' });
            ap(row, ce('span', { cls: 'text-muted', text: line.label }));
            ap(row, ce('span', { cls: 'fw-semibold', text: line.value }));
            ap(card, row);
        });

        var totRow  = ce('div', { cls: 'd-flex justify-content-between align-items-end mt-3 pt-2 border-top' });
        var leftDiv = ce('div');
        ap(leftDiv, ce('div', { cls: 'fw-semibold fs-sm', text: 'Total (VAT exclusive)' }));
        ap(totRow, leftDiv);
        ap(totRow, ce('div', { cls: 'fs-3 fw-bold text-primary', text: tzsToDisplay(total) }));
        ap(card, totRow);
        ap(panel, card);

        // Invoice description
        var notes   = gid('visitNotes').value.trim();
        var invText = 'Site visit to ' + locName
            + (notes ? ' (' + notes + ')' : '')
            + ', ' + days + ' day' + (days > 1 ? 's' : '')
            + '. Travel: ' + tzsToDisplay(travel)
            + ', Local transport: ' + tzsToDisplay(local)
            + ', Allowance: ' + tzsToDisplay(allowance)
            + ' per day. Total: ' + tzsToDisplay(total) + ' (VAT exclusive).';

        var box = ce('div', {
            cls: 'border rounded p-3 mb-3',
            style: 'border-left:3px solid #185fa5!important'
        });
        ap(box, ce('div', {
            cls: 'fw-bold mb-2',
            style: 'font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#6c757d',
            text: 'Invoice Description'
        }));
        var textEl = ce('p', { cls: 'fs-sm mb-2', text: invText });
        ap(box, textEl);

        var copyBtn = ce('button', { cls: 'btn btn-sm btn-alt-secondary', text: 'Copy' });
        copyBtn.addEventListener('click', function () {
            navigator.clipboard.writeText(textEl.textContent).then(function () {
                copyBtn.textContent = '✓ Copied!';
                setTimeout(function () { copyBtn.textContent = 'Copy'; }, 2000);
            });
        });
        ap(box, copyBtn);
        ap(panel, box);
    }

    // ── Location card selection ────────────────────────────────────────
    document.querySelectorAll('.location-card').forEach(function (card) {
        card.addEventListener('click', function () {
            document.querySelectorAll('.location-card').forEach(function (c) {
                c.classList.remove('border-primary', 'border-2', 'bg-primary-subtle');
            });
            card.classList.add('border-primary', 'border-2', 'bg-primary-subtle');
            selectedCard = card;
            gid('costBlock').style.display = '';
            updateSymbols();
            loadPresets(card);
            renderResult();
        });
    });

    // ── Day stepper ────────────────────────────────────────────────────
    document.querySelectorAll('.day-adj').forEach(function (btn) {
        btn.addEventListener('click', function () {
            days = Math.max(1, days + parseInt(btn.dataset.delta, 10));
            gid('dayVal').textContent = days;
            renderResult();
        });
    });

    // ── Reset to presets ───────────────────────────────────────────────
    gid('resetPreset').addEventListener('click', function () {
        if (selectedCard) { loadPresets(selectedCard); renderResult(); }
    });

    // ── Input / notes changes ─────────────────────────────────────────
    ['costTravel', 'costLocal', 'costAllowance', 'visitNotes'].forEach(function (id) {
        gid(id).addEventListener('input', renderResult);
    });

    // ── Currency change ────────────────────────────────────────────────
    gid('currencySelect').addEventListener('change', function () {
        updateSymbols();
        if (selectedCard) { loadPresets(selectedCard); renderResult(); }
    });

})();
</script>
@endsection
