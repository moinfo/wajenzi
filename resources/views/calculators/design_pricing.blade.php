@extends('layouts.backend')
@section('content')
<div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Design Pricing Calculator</h1>
            <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">Calculators</li>
                    <li class="breadcrumb-item active">Design Pricing</li>
                </ol>
            </nav>
        </div>
    </div>
</div>

<div class="content">
    <div class="row g-4">

        {{-- ── Left column: inputs ────────────────────────────────── --}}
        <div class="col-lg-8">

            {{-- Currency & location --}}
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
                                    {{ $cur->code === 'USD' ? 'selected' : '' }}>
                                    {{ $cur->code }} &mdash; {{ $cur->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-7">
                            <label class="form-label fw-semibold fs-sm mb-1">Project Location</label>
                            <input type="text" id="locationInput" class="form-control form-control-sm mb-1"
                                placeholder="e.g. Kigamboni, Dar es Salaam" autocomplete="off">
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($locations as $loc)
                                <button type="button" class="btn btn-xs btn-alt-secondary loc-chip"
                                    data-target="locationInput"
                                    style="font-size:11px;padding:2px 8px">{{ $loc }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tab nav --}}
            <ul class="nav nav-tabs mb-0" id="mainTabs">
                <li class="nav-item">
                    <button class="nav-link active" data-tab="standard">Standard Building</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-tab="special">Special Structures</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-tab="airbnb">AirBnB / Multi-unit</button>
                </li>
            </ul>

            {{-- ── STANDARD TAB ──────────────────────────────────────── --}}
            <div id="tab-standard" class="tab-pane border border-top-0 rounded-bottom p-4 mb-3">

                <div class="mb-4">
                    <div class="text-muted text-uppercase fw-bold mb-2" style="font-size:11px;letter-spacing:.06em">Building Type</div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-alt-primary rise-btn" data-rise="low">Low-rise (single storey)</button>
                        <button class="btn btn-sm btn-alt-secondary rise-btn" data-rise="high">High-rise (multi storey)</button>
                    </div>
                </div>

                <div id="floorsCard" class="mb-4" style="display:none">
                    <div class="text-muted text-uppercase fw-bold mb-2" style="font-size:11px;letter-spacing:.06em">Storeys Above Ground</div>
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-sm btn-alt-secondary floor-adj" data-delta="-1" style="width:34px;height:34px">&#8722;</button>
                        <span id="floorVal" class="fs-4 fw-bold" style="min-width:2rem;text-align:center">1</span>
                        <button class="btn btn-sm btn-alt-secondary floor-adj" data-delta="1"  style="width:34px;height:34px">+</button>
                        <span id="floorLabel" class="text-muted fs-sm">= G+1</span>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="text-muted text-uppercase fw-bold mb-2" style="font-size:11px;letter-spacing:.06em">Select Package</div>
                    <div id="pkgGrid" class="row g-2"></div>
                </div>

                <div>
                    <div class="text-muted text-uppercase fw-bold mb-2" style="font-size:11px;letter-spacing:.06em">Add-on Services</div>
                    <div id="addonList" class="row g-2"></div>
                </div>
            </div>

            {{-- ── SPECIAL STRUCTURES TAB ────────────────────────────── --}}
            <div id="tab-special" class="tab-pane border border-top-0 rounded-bottom p-4 mb-3" style="display:none">

                <div class="mb-4">
                    <label class="form-label fw-semibold fs-sm text-muted text-uppercase">Structure Type</label>
                    <select id="specialSelect" class="form-select">
                        <option value="">&#8212; Select structure type &#8212;</option>
                        @foreach($specialStructures as $s)
                        <option value="{{ $s->id }}" data-rate="{{ $s->rate_tzs_per_sqm }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="specialFields" style="display:none">
                    <div class="mb-4">
                        <label class="form-label fw-semibold fs-sm text-muted text-uppercase">Dimensions (metres)</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" id="dimL" class="form-control" style="max-width:120px" min="1" value="10" placeholder="Length">
                            <span class="text-muted">&times;</span>
                            <input type="number" id="dimW" class="form-control" style="max-width:120px" min="1" value="8" placeholder="Width">
                            <span class="text-muted">m&sup2;</span>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold fs-sm text-muted text-uppercase">Project Location</label>
                        <input type="text" id="specialLoc" class="form-control mb-1"
                            placeholder="e.g. Mikocheni, Dar es Salaam" autocomplete="off">
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($locations as $loc)
                            <button type="button" class="btn btn-xs btn-alt-secondary loc-chip"
                                data-target="specialLoc"
                                style="font-size:11px;padding:2px 8px">{{ $loc }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div id="specialResult"></div>
            </div>

            {{-- ── AIRBNB TAB ────────────────────────────────────────── --}}
            <div id="tab-airbnb" class="tab-pane border border-top-0 rounded-bottom p-4 mb-3" style="display:none">

                <div class="mb-4">
                    <div class="text-muted text-uppercase fw-bold mb-2" style="font-size:11px;letter-spacing:.06em">Number of Units</div>
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-sm btn-alt-secondary" id="unitMinus" style="width:34px;height:34px">&#8722;</button>
                        <span id="unitVal" class="fs-4 fw-bold" style="min-width:2rem;text-align:center">2</span>
                        <button class="btn btn-sm btn-alt-secondary" id="unitPlus"  style="width:34px;height:34px">+</button>
                        <span class="text-muted fs-sm">units</span>
                    </div>
                    <div id="unitNote" class="alert alert-warning fs-sm mt-2 py-2 mb-0" style="display:none">
                        More than 2 units — this case must be escalated to the CEO/MD for further calculation and pricing guidelines before invoicing.
                    </div>
                </div>

                <div class="mb-4 p-3 bg-body-secondary rounded-3">
                    <div class="text-muted text-uppercase fw-bold mb-2" style="font-size:11px;letter-spacing:.06em">Package &mdash; PLATINUM Low-Rise (fixed)</div>
                    <div class="fs-sm text-muted lh-lg">
                        &#10003; Architectural design &nbsp;&middot;&nbsp; &#10003; BOQ preparation<br>
                        &#10003; Fence design &nbsp;&middot;&nbsp; &#10003; Servant&rsquo;s quarter design
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold fs-sm text-muted text-uppercase">Project Location</label>
                    <input type="text" id="airbnbLoc" class="form-control mb-1"
                        placeholder="e.g. Masaki, Dar es Salaam" autocomplete="off">
                    <div class="d-flex flex-wrap gap-1">
                        @foreach($locations as $loc)
                        <button type="button" class="btn btn-xs btn-alt-secondary loc-chip"
                            data-target="airbnbLoc"
                            style="font-size:11px;padding:2px 8px">{{ $loc }}</button>
                        @endforeach
                    </div>
                </div>

                <div id="airbnbResult"></div>
            </div>

        </div>{{-- /left col --}}

        {{-- ── Right column: sticky result ────────────────────────── --}}
        <div class="col-lg-4">
            <div class="sticky-top" style="top:4.5rem">
                <div class="block block-rounded">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Pricing Summary</h3>
                    </div>
                    <div class="block-content py-3" id="sideResult">
                        <p class="text-muted fs-sm text-center py-4 mb-0">Select a package to see pricing</p>
                    </div>
                    <div class="block-content py-3 border-top" id="billingActions" style="display:none">
                        <p class="text-muted fs-xs mb-2">Send calculation to billing:</p>
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-sm btn-alt-info"    onclick="openBillingModal('quote')">
                                <i class="fa fa-file-alt me-1"></i> Quote
                            </button>
                            <button class="btn btn-sm btn-alt-warning" onclick="openBillingModal('proforma')">
                                <i class="fa fa-file-invoice me-1"></i> Proforma
                            </button>
                            <button class="btn btn-sm btn-alt-success" onclick="openBillingModal('invoice')">
                                <i class="fa fa-file-invoice-dollar me-1"></i> Invoice
                            </button>
                        </div>
                    </div>
                </div>
                <p class="text-muted text-center mb-0" style="font-size:11px">All prices VAT exclusive &middot; Converted at prevailing rate</p>
            </div>
        </div>

    </div>{{-- /row --}}
</div>

{{-- ── Send to Billing modal ──────────────────────────────────── --}}
<div class="modal fade" id="billingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('calculators.to-billing') }}" id="billingForm">
                @csrf
                <input type="hidden" name="doc_type"          id="billingDocType">
                <input type="hidden" name="currency_code"     id="billingCurrency">
                <input type="hidden" name="exchange_rate"     id="billingRate">
                <input type="hidden" name="service_description" id="billingDescription">
                <div id="billingItemsContainer"></div>

                <div class="modal-header">
                    <h5 class="modal-title" id="billingModalTitle">Create Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Client <span class="text-danger">*</span></label>
                        <select name="client_id" class="form-select" required>
                            <option value="">&#8212; Select client &#8212;</option>
                            @foreach($clients as $client)
                            <option value="{{ $client->id }}">
                                {{ $client->first_name }} {{ $client->last_name }}
                                @if($client->company_name) &mdash; {{ $client->company_name }} @endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" id="billingNotes"
                            placeholder="Invoice description will be pre-filled from the calculator"></textarea>
                    </div>
                    <div class="border rounded p-3 bg-body-secondary">
                        <div class="fw-semibold fs-sm mb-2">Line Items Preview</div>
                        <div id="billingItemsPreview"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="billingSubmitBtn">
                        <i class="fa fa-paper-plane me-1"></i> Create Document
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js_after')
<script>
(function () {
    'use strict';

    // ── Server data (Blade-injected, server-controlled) ──
    var LOW_PKGS  = @json($lowPackages);
    var HIGH_PKGS = @json($highPackages);
    var ADDONS    = @json($addons);
    var SPECIALS  = @json($specialStructures);
    var TZS_RATE  = {{ (float) $tzsRate }};

    // ── State ──────────────────────────────────────────────────────────
    var S = { rise: 'low', floors: 1, pkgId: null, addonIds: [] };
    var airbnbUnits = 2;

    // ── DOM helpers ────────────────────────────────────────────────────
    function gid(id) { return document.getElementById(id); }

    function ce(tag, opts) {
        var el = document.createElement(tag);
        if (!opts) return el;
        if (opts.cls)            el.className   = opts.cls;
        if (opts.text != null)   el.textContent = opts.text;
        if (opts.id)             el.id          = opts.id;
        if (opts.style)          el.style.cssText = opts.style;
        if (opts.attrs) Object.keys(opts.attrs).forEach(function (k) { el.setAttribute(k, opts.attrs[k]); });
        if (opts.on)   Object.keys(opts.on).forEach(function (ev) { el.addEventListener(ev, opts.on[ev]); });
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
            code:   opt.dataset.code   || 'USD',
            symbol: opt.dataset.symbol || '$'
        };
    }

    function fmtUSD(usd) {
        var c = getCur();
        return c.symbol + ' ' + Math.round(usd * c.rate).toLocaleString();
    }

    function tzsSub(usd) {
        var c = getCur();
        if (c.code === 'TZS') return null;
        return '≈ TZS ' + Math.round(usd * TZS_RATE).toLocaleString();
    }

    function tzsToDisplayAmt(tzs) {
        var c = getCur();
        if (c.code === 'TZS') return 'TZS ' + Math.round(tzs).toLocaleString();
        return fmtUSD(tzs / TZS_RATE);
    }

    // ── Shared UI builders ─────────────────────────────────────────────
    function buildResultCard(lines, totalValue, subLine) {
        var card = ce('div', { cls: 'p-3 bg-body-secondary rounded-3 mb-3' });
        ap(card, ce('div', {
            cls: 'text-muted fw-bold mb-2',
            style: 'font-size:11px;letter-spacing:.06em;text-transform:uppercase',
            text: 'Pricing Breakdown'
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
        if (subLine) ap(leftDiv, ce('div', { style: 'font-size:11px;color:#6c757d', text: subLine }));
        ap(totRow, leftDiv);
        ap(totRow, ce('div', { cls: 'fs-3 fw-bold text-primary', text: totalValue }));
        ap(card, totRow);
        return card;
    }

    function buildInvoiceBox(text) {
        var box = ce('div', {
            cls: 'border rounded p-3 mb-3',
            style: 'border-left:3px solid #185fa5!important'
        });
        ap(box, ce('div', {
            cls: 'fw-bold mb-2',
            style: 'font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#6c757d',
            text: 'Invoice Description'
        }));
        var textEl = ce('p', { cls: 'fs-sm mb-2', text: text });
        ap(box, textEl);

        var copyBtn = ce('button', { cls: 'btn btn-sm btn-alt-secondary', text: 'Copy' });
        copyBtn.addEventListener('click', function () {
            navigator.clipboard.writeText(textEl.textContent).then(function () {
                copyBtn.textContent = '✓ Copied!';
                setTimeout(function () { copyBtn.textContent = 'Copy'; }, 2000);
            });
        });
        ap(box, copyBtn);
        return box;
    }

    // ── Standard: packages ─────────────────────────────────────────────
    function getPkgs() { return S.rise === 'low' ? LOW_PKGS : HIGH_PKGS; }

    function renderPkgs() {
        var grid = gid('pkgGrid');
        clearEl(grid);
        getPkgs().forEach(function (pkg) {
            var isSelected = pkg.id === S.pkgId;
            var col  = ce('div', { cls: 'col-sm-4' });
            var card = ce('div', {
                cls: 'h-100 border rounded p-3' + (isSelected ? ' border-primary border-2 bg-primary-subtle' : ''),
                style: 'cursor:pointer'
            });
            card.addEventListener('click', function () { selectPkg(pkg.id); });

            ap(card, ce('div', {
                cls: 'fw-bold text-uppercase mb-1',
                style: 'font-size:11px;letter-spacing:.05em;color:#6c757d',
                text: pkg.name
            }));
            ap(card, ce('div', { cls: 'fs-4 fw-bold text-primary mb-2', text: fmtUSD(pkg.price_usd) }));

            (pkg.included_services || []).forEach(function (svc) {
                ap(card, ce('div', { cls: 'fs-xs text-muted', text: '✓ ' + svc }));
            });

            ap(col, card);
            ap(grid, col);
        });
    }

    function selectPkg(id) {
        S.pkgId    = id;
        S.addonIds = [];
        renderPkgs();
        renderAddons();
        renderStdResult();
    }

    // ── Standard: add-ons ──────────────────────────────────────────────
    function getIncluded() {
        var pkg = getPkgs().filter(function (p) { return p.id === S.pkgId; })[0];
        return (pkg && pkg.included_services) ? pkg.included_services.map(function (s) { return s.toLowerCase(); }) : [];
    }

    function renderAddons() {
        var list     = gid('addonList');
        clearEl(list);
        var included = getIncluded();

        ADDONS.forEach(function (addon) {
            var key         = addon.name.toLowerCase();
            var isIncluded  = included.some(function (s) { return s.includes(key) || key.includes(s.replace(/ /g, '')); });
            var price       = S.rise === 'low' ? addon.price_low_usd : addon.price_high_usd;
            var isChecked   = isIncluded || S.addonIds.indexOf(addon.id) !== -1;

            var col = ce('div', { cls: 'col-sm-6' });
            var lbl = ce('label', {
                cls: 'd-flex align-items-center gap-2 p-2 border rounded mb-0' + (isIncluded ? ' opacity-50' : ''),
                style: 'cursor:' + (isIncluded ? 'default' : 'pointer')
            });

            var cb        = document.createElement('input');
            cb.type       = 'checkbox';
            cb.className  = 'form-check-input flex-shrink-0';
            cb.checked    = isChecked;
            cb.disabled   = isIncluded;
            if (!isIncluded) {
                (function (addonId) {
                    cb.addEventListener('change', function () {
                        S.addonIds = cb.checked
                            ? S.addonIds.concat([addonId])
                            : S.addonIds.filter(function (x) { return x !== addonId; });
                        renderAddons();
                        renderStdResult();
                    });
                })(addon.id);
            }

            ap(lbl, cb);
            ap(lbl, ce('span', { cls: 'flex-grow-1 fs-sm', text: addon.name }));
            ap(lbl, ce('span', {
                style: 'font-size:11px;color:#6c757d;white-space:nowrap',
                text: isIncluded ? 'Included' : '+' + fmtUSD(price)
            }));
            ap(col, lbl);
            ap(list, col);
        });
    }

    // ── Standard: result ───────────────────────────────────────────────
    function calcStd() {
        var pkgs    = getPkgs();
        var pkg     = pkgs.filter(function (p) { return p.id === S.pkgId; })[0];
        var cheapPkg = pkgs[0];
        if (!pkg) return null;

        var extraF    = (S.rise === 'high' && S.floors > 1) ? S.floors - 1 : 0;
        var extraCost = extraF * (cheapPkg.price_usd / 2);
        var addonCost = S.addonIds.reduce(function (sum, id) {
            var a = ADDONS.filter(function (x) { return x.id === id; })[0];
            return a ? sum + (S.rise === 'low' ? a.price_low_usd : a.price_high_usd) : sum;
        }, 0);
        var total = pkg.price_usd + extraCost + addonCost;
        return { pkg: pkg, cheapPkg: cheapPkg, extraF: extraF, extraCost: extraCost, addonCost: addonCost, total: total };
    }

    function renderStdResult() {
        var panel = gid('sideResult');
        clearEl(panel);

        var calc = calcStd();
        if (!calc) {
            ap(panel, ce('p', { cls: 'text-muted fs-sm text-center py-4 mb-0', text: 'Select a package to see pricing' }));
            gid('billingActions').style.display = 'none';
            return;
        }
        gid('billingActions').style.display = '';

        var lines = [{ label: calc.pkg.name + ' (' + S.rise + '-rise)', value: fmtUSD(calc.pkg.price_usd) }];
        if (calc.extraF > 0) {
            lines.push({ label: 'Extra ' + calc.extraF + ' floor(s)', value: '+' + fmtUSD(calc.extraCost) });
        }
        S.addonIds.forEach(function (id) {
            var a = ADDONS.filter(function (x) { return x.id === id; })[0];
            if (a) lines.push({ label: a.name, value: '+' + fmtUSD(S.rise === 'low' ? a.price_low_usd : a.price_high_usd) });
        });

        ap(panel, buildResultCard(lines, fmtUSD(calc.total), tzsSub(calc.total)));

        var loc       = gid('locationInput').value.trim() || '[Location]';
        var allSvcs   = (calc.pkg.included_services || []).slice();
        S.addonIds.forEach(function (id) {
            var a = ADDONS.filter(function (x) { return x.id === id; })[0];
            if (a) allSvcs.push(a.name);
        });
        var riseLabel = S.rise === 'high' ? 'G+' + S.floors : 'single storey';
        var typeLabel = S.rise === 'high' ? 'high-rise' : 'low-rise';
        var invText   = 'Design of a ' + typeLabel + ' building (' + riseLabel + ') at ' + loc
            + ', comprising: ' + allSvcs.join(', ')
            + '. Total: ' + fmtUSD(calc.total) + ' (VAT exclusive), converted to TZS at prevailing rate.';
        ap(panel, buildInvoiceBox(invText));
    }

    // ── Special structures ─────────────────────────────────────────────
    function renderSpecialResult() {
        var container = gid('specialResult');
        clearEl(container);
        var sel = gid('specialSelect');
        var opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.value) return;

        var rate  = parseFloat(opt.dataset.rate) || 0;
        var name  = opt.textContent;
        var l     = parseFloat(gid('dimL').value) || 0;
        var w     = parseFloat(gid('dimW').value) || 0;
        var sqm   = l * w;
        var tzs   = sqm * rate;
        var loc   = gid('specialLoc').value.trim() || '[Location]';

        var lines = [
            { label: 'Area',               value: sqm.toLocaleString() + ' m²' },
            { label: 'Rate (' + name + ')', value: 'TZS ' + rate.toLocaleString() + '/m²' }
        ];

        var totalFmt = tzsToDisplayAmt(tzs);
        var sub      = getCur().code === 'TZS' ? null : '≈ TZS ' + Math.round(tzs).toLocaleString();
        ap(container, buildResultCard(lines, totalFmt, sub));

        var invText = name + ' design at ' + loc
            + ', total area ' + sqm.toLocaleString() + ' m² at TZS ' + rate.toLocaleString() + '/m²'
            + '. Total: TZS ' + Math.round(tzs).toLocaleString() + ' (VAT exclusive).';
        ap(container, buildInvoiceBox(invText));
    }

    // ── AirBnB ─────────────────────────────────────────────────────────
    function renderAirbnb() {
        gid('unitVal').textContent   = airbnbUnits;
        gid('unitNote').style.display = airbnbUnits > 2 ? '' : 'none';

        var container = gid('airbnbResult');
        clearEl(container);

        if (airbnbUnits > 2) {
            ap(container, ce('div', {
                cls: 'alert alert-warning text-center fs-sm',
                text: airbnbUnits + ' units requires CEO/MD approval before pricing can be issued.'
            }));
            return;
        }

        var platPkg  = LOW_PKGS.filter(function (p) { return p.name.toLowerCase().indexOf('platinum') !== -1; })[0] || { price_usd: 580 };
        var cheapLow = LOW_PKGS[0] || { price_usd: 320 };
        var platinum = platPkg.price_usd;
        var extra    = (airbnbUnits - 1) * (cheapLow.price_usd / 2);
        var total    = platinum + extra;

        var lines = [{ label: 'PLATINUM Low-Rise base (1 unit)', value: fmtUSD(platinum) }];
        if (airbnbUnits > 1) {
            lines.push({ label: (airbnbUnits - 1) + ' extra unit(s) × ' + fmtUSD(cheapLow.price_usd / 2), value: '+' + fmtUSD(extra) });
        }

        ap(container, buildResultCard(lines, fmtUSD(total), tzsSub(total)));

        var loc     = gid('airbnbLoc').value.trim() || '[Location]';
        var invText = 'Design of AirBnB (' + airbnbUnits + ' unit' + (airbnbUnits > 1 ? 's' : '') + ') at ' + loc
            + ', including: Architectural design, BOQ preparation, Fence design, Servant\'s quarter design.'
            + ' Total: ' + fmtUSD(total) + ' (VAT exclusive), converted to TZS at prevailing rate.';
        ap(container, buildInvoiceBox(invText));
    }

    // ── Tab navigation ─────────────────────────────────────────────────
    document.querySelectorAll('#mainTabs .nav-link').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('#mainTabs .nav-link').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var tab = btn.dataset.tab;
            ['standard', 'special', 'airbnb'].forEach(function (t) {
                gid('tab-' + t).style.display = (t === tab) ? '' : 'none';
            });
            // Sync side panel for standard tab only; others show inline results
            if (tab === 'standard') renderStdResult();
            else {
                var panel = gid('sideResult');
                clearEl(panel);
                ap(panel, ce('p', { cls: 'text-muted fs-sm text-center py-4 mb-0', text: 'Results shown below the inputs' }));
            }
        });
    });

    // ── Rise type ──────────────────────────────────────────────────────
    document.querySelectorAll('.rise-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            S.rise     = btn.dataset.rise;
            S.addonIds = [];
            var pkgs   = getPkgs();
            S.pkgId    = pkgs.length > 0 ? pkgs[0].id : null;

            gid('floorsCard').style.display = (S.rise === 'high') ? '' : 'none';

            document.querySelectorAll('.rise-btn').forEach(function (b) {
                b.classList.toggle('btn-alt-primary',   b.dataset.rise === S.rise);
                b.classList.toggle('btn-alt-secondary', b.dataset.rise !== S.rise);
            });
            renderPkgs();
            renderAddons();
            renderStdResult();
        });
    });

    // ── Floor stepper ──────────────────────────────────────────────────
    document.querySelectorAll('.floor-adj').forEach(function (btn) {
        btn.addEventListener('click', function () {
            S.floors = Math.max(1, S.floors + parseInt(btn.dataset.delta, 10));
            gid('floorVal').textContent   = S.floors;
            gid('floorLabel').textContent = '= G+' + S.floors;
            renderStdResult();
        });
    });

    // ── Special structures events ──────────────────────────────────────
    gid('specialSelect').addEventListener('change', function () {
        gid('specialFields').style.display = this.value ? '' : 'none';
        if (this.value) renderSpecialResult();
        else clearEl(gid('specialResult'));
    });
    ['dimL', 'dimW', 'specialLoc'].forEach(function (id) {
        gid(id).addEventListener('input', renderSpecialResult);
    });

    // ── AirBnB events ──────────────────────────────────────────────────
    gid('unitPlus').addEventListener('click',  function () { airbnbUnits = Math.min(20, airbnbUnits + 1); renderAirbnb(); });
    gid('unitMinus').addEventListener('click', function () { airbnbUnits = Math.max(1,  airbnbUnits - 1); renderAirbnb(); });
    gid('airbnbLoc').addEventListener('input', renderAirbnb);

    // ── Currency / location change ─────────────────────────────────────
    function recalcAll() {
        var activeBtn = document.querySelector('#mainTabs .nav-link.active');
        var tab = activeBtn ? activeBtn.dataset.tab : 'standard';
        if (tab === 'standard') { renderPkgs(); renderAddons(); renderStdResult(); }
        else if (tab === 'special' && gid('specialSelect').value) renderSpecialResult();
        else if (tab === 'airbnb') renderAirbnb();
    }
    gid('currencySelect').addEventListener('change', recalcAll);
    gid('locationInput').addEventListener('input',   recalcAll);

    // ── Location chips ─────────────────────────────────────────────────
    document.querySelectorAll('.loc-chip').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var inp = gid(btn.dataset.target);
            inp.value = btn.textContent.trim();
            inp.dispatchEvent(new Event('input'));
            // highlight active chip
            document.querySelectorAll('.loc-chip[data-target="' + btn.dataset.target + '"]').forEach(function (b) {
                b.classList.toggle('btn-primary', b === btn);
                b.classList.toggle('btn-alt-secondary', b !== btn);
            });
        });
    });

    // ── Init ───────────────────────────────────────────────────────────
    var initPkgs = getPkgs();
    if (initPkgs.length > 0) S.pkgId = initPkgs[0].id;
    renderPkgs();
    renderAddons();
    renderStdResult();

    // ── Billing modal ──────────────────────────────────────────────────
    // Collect current calculation as line items for billing
    function getActiveTab() {
        var btn = document.querySelector('#mainTabs .nav-link.active');
        return btn ? btn.dataset.tab : 'standard';
    }

    function buildBillingItems() {
        var tab   = getActiveTab();
        var items = [];

        if (tab === 'standard') {
            var calc = calcStd();
            if (!calc) return null;
            var pkg = calc.pkg;
            items.push({ item_name: pkg.name + ' Package (' + S.rise + '-rise)', quantity: 1, unit_price: pkg.price_usd,
                description: (pkg.included_services || []).join(', ') });
            if (calc.extraF > 0) {
                items.push({ item_name: 'Additional Floors (' + calc.extraF + ' floor' + (calc.extraF > 1 ? 's' : '') + ')',
                    quantity: calc.extraF, unit_price: calc.cheapPkg.price_usd / 2, description: 'Extra storeys above G+1' });
            }
            S.addonIds.forEach(function (id) {
                var a = ADDONS.filter(function (x) { return x.id === id; })[0];
                if (a) items.push({ item_name: a.name, quantity: 1, unit_price: S.rise === 'low' ? a.price_low_usd : a.price_high_usd });
            });

        } else if (tab === 'special') {
            var sel = gid('specialSelect');
            var opt = sel.options[sel.selectedIndex];
            if (!opt || !opt.value) return null;
            var rate = parseFloat(opt.dataset.rate) || 0;
            var l = parseFloat(gid('dimL').value) || 0;
            var w = parseFloat(gid('dimW').value) || 0;
            var sqm = l * w;
            var tzs = sqm * rate;
            var usd = tzs / TZS_RATE;
            items.push({ item_name: opt.textContent.trim() + ' Design', quantity: sqm,
                unit_price: parseFloat((usd / sqm).toFixed(4)), description: sqm + ' m² at TZS ' + rate.toLocaleString() + '/m²' });

        } else if (tab === 'airbnb') {
            if (airbnbUnits > 2) return null;
            var platPkg  = LOW_PKGS.filter(function (p) { return p.name.toLowerCase().indexOf('platinum') !== -1; })[0] || { price_usd: 580 };
            var cheapLow = LOW_PKGS[0] || { price_usd: 320 };
            items.push({ item_name: 'AirBnB Design — PLATINUM Low-Rise (1 unit)', quantity: 1, unit_price: platPkg.price_usd,
                description: 'Architectural design, BOQ preparation, Fence design, Servant\'s quarter design' });
            if (airbnbUnits > 1) {
                items.push({ item_name: 'Additional Units', quantity: airbnbUnits - 1, unit_price: cheapLow.price_usd / 2 });
            }
        }

        return items.length > 0 ? items : null;
    }

    window.openBillingModal = function (docType) {
        var items = buildBillingItems();
        if (!items) {
            alert('Please complete your calculation first.');
            return;
        }

        // Set hidden fields
        gid('billingDocType').value    = docType;
        gid('billingCurrency').value   = getCur().code;
        gid('billingRate').value       = getCur().rate;

        // Invoice description from result panel
        var invEl = gid('sideResult') ? gid('sideResult').querySelector('p.fs-sm') : null;
        gid('billingDescription').value = invEl ? invEl.textContent : '';
        gid('billingNotes').value       = invEl ? invEl.textContent : '';

        // Populate hidden item inputs
        var container = gid('billingItemsContainer');
        clearEl(container);
        items.forEach(function (item, idx) {
            var prefix = 'items[' + idx + ']';
            Object.keys(item).forEach(function (key) {
                var inp   = document.createElement('input');
                inp.type  = 'hidden';
                inp.name  = prefix + '[' + key + ']';
                inp.value = item[key] != null ? item[key] : '';
                container.appendChild(inp);
            });
        });

        // Populate preview table
        var preview = gid('billingItemsPreview');
        clearEl(preview);
        var c = getCur();
        items.forEach(function (item) {
            var row = ce('div', { cls: 'd-flex justify-content-between fs-sm py-1 border-bottom' });
            var left = ce('span', { cls: 'text-muted', text: item.item_name + (item.quantity !== 1 ? ' × ' + item.quantity : '') });
            var right = ce('span', { cls: 'fw-semibold', text: c.symbol + ' ' + Math.round(item.quantity * item.unit_price * c.rate).toLocaleString() });
            row.appendChild(left);
            row.appendChild(right);
            preview.appendChild(row);
        });
        var totalUSD = items.reduce(function (s, i) { return s + i.quantity * i.unit_price; }, 0);
        var totRow = ce('div', { cls: 'd-flex justify-content-between fw-bold mt-2 pt-1 border-top' });
        totRow.appendChild(ce('span', { text: 'Total' }));
        totRow.appendChild(ce('span', { cls: 'text-primary', text: c.symbol + ' ' + Math.round(totalUSD * c.rate).toLocaleString() }));
        preview.appendChild(totRow);

        // Update modal title and button
        var labels = { quote: 'Quotation', proforma: 'Proforma Invoice', invoice: 'Invoice' };
        gid('billingModalTitle').textContent = 'Create ' + (labels[docType] || docType);
        gid('billingSubmitBtn').textContent  = 'Create ' + (labels[docType] || docType);

        var modal = new bootstrap.Modal(document.getElementById('billingModal'));
        modal.show();
    };

})();
</script>
@endsection
