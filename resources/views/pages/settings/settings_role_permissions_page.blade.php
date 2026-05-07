@extends('layouts.backend')
@section('content')

<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            Manage Permissions: {{ $selectedRole->name }}
            <div class="float-right">
                <a href="{{ route('hr_settings_roles') }}" class="btn btn-rounded min-width-125 mb-10 btn-secondary">
                    <i class="fa fa-arrow-left">&nbsp;</i>Back to Roles
                </a>
            </div>
        </div>

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Permissions for Role: <strong>{{ $selectedRole->name }}</strong></h3>
            </div>
            @include('components.headed_paper_settings')
            <br/>
            <div class="block-content">

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form method="post" autocomplete="off" action="{{ route('hr_settings_role_permissions') }}">
                    @csrf
                    <input type="hidden" name="role_id" value="{{ $selectedRole->id }}">
                    <input type="hidden" name="update_permissions" value="1">

                    <!-- Search & Filter -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Search Permissions</label>
                                <input type="text" class="form-control" id="permission-search" placeholder="Search permissions...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Filter by Type</label>
                                <select class="form-control" id="type-filter">
                                    <option value="">All Types</option>
                                    @foreach($permissionsByType as $type => $typePermissions)
                                        <option value="{{ $type }}">{{ $type }} ({{ $typePermissions->count() }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Two Column Layout -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0 text-success">
                                        <i class="fas fa-check-circle"></i> Assigned Permissions
                                        <span class="badge bg-success ms-2" id="assigned-count">0</span>
                                    </h5>
                                    <small class="text-muted">Permissions currently assigned to this role</small>
                                </div>
                                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                    <div id="assigned-permissions" class="permission-list"></div>
                                </div>
                                <div class="card-footer">
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="remove-all">
                                        <i class="fas fa-times"></i> Remove All
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0 text-primary">
                                        <i class="fas fa-plus-circle"></i> Available Permissions
                                        <span class="badge bg-primary ms-2" id="available-count">0</span>
                                    </h5>
                                    <small class="text-muted">Permissions not yet assigned to this role</small>
                                </div>
                                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                    <div id="available-permissions" class="permission-list"></div>
                                </div>
                                <div class="card-footer">
                                    <button type="button" class="btn btn-sm btn-outline-success" id="add-all-filtered">
                                        <i class="fas fa-plus"></i> Add All Visible
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden checkboxes for form submission -->
                    <div id="hidden-permissions" style="display: none;">
                        @foreach($permissions as $permission)
                            <input type="checkbox"
                                   name="permission_id[]"
                                   value="{{ $permission->id }}"
                                   id="hidden-permission-{{ $permission->id }}"
                                   {{ in_array($permission->id, $rolePermissionIds) ? 'checked' : '' }}>
                        @endforeach
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-alt-primary" name="submit" value="UpdatePermissions">
                            <i class="fa fa-save"></i> Update Permissions
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

@endsection

@section('css_after')
<style>
/* ── Permission leaf items ────────────────────── */
.permission-item {
    padding: 5px 10px;
    margin: 2px 0;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    background: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.12s;
}
.permission-item:hover { background: #f1f5f9; }
.permission-item .permission-name { font-size: 12.5px; font-weight: 500; color: #334155; }
.permission-item .btn { padding: 1px 6px; font-size: 11px; }

/* ── Shared collapsible header base ─────────────── */
.perm-hdr {
    display: flex;
    align-items: center;
    border-radius: 5px;
    cursor: pointer;
    user-select: none;
    font-weight: 700;
    letter-spacing: 0.5px;
}
.perm-hdr .toggle-arrow { margin-left: auto; font-size: 10px; transition: transform 0.2s; }
.perm-hdr.collapsed .toggle-arrow { transform: rotate(-90deg); }
.perm-body { overflow: hidden; }

/* ── L1: Main menu ─────────────────────────────── */
.l1-header { padding: 8px 12px; font-size: 12px; text-transform: uppercase; margin-bottom: 3px;
             background: #1e40af; color: #fff; border-left: 4px solid #93c5fd; }
.l1-body   { padding: 0 0 4px 10px; }
.l1-badge  { background: rgba(255,255,255,0.3); color:#fff; font-size:10px; padding:1px 6px;
             border-radius:9px; margin-left:6px; font-weight:700; }

/* ── L2: Sub-menu ──────────────────────────────── */
.l2-header { padding: 6px 11px; font-size: 12px; margin: 3px 0 2px;
             background: #dbeafe; color: #1d4ed8; border-left: 3px solid #3b82f6; }
.l2-body   { padding: 0 0 3px 10px; }
.l2-badge  { background: #3b82f6; color:#fff; font-size:9px; padding:1px 5px;
             border-radius:8px; margin-left:5px; font-weight:700; }

/* ── L3: CRUD actions row ──────────────────────── */
.l3-header { padding: 5px 10px; font-size: 11.5px; margin: 2px 0;
             background: #fef3c7; color: #92400e; border-left: 3px solid #f59e0b; }
.l3-body   { padding: 0 0 2px 10px; }
.l3-badge  { background: #f59e0b; color:#fff; font-size:9px; padding:1px 5px;
             border-radius:8px; margin-left:5px; font-weight:700; }

/* ── L4: Settings row ──────────────────────────── */
.l4-header { padding: 5px 10px; font-size: 11.5px; margin: 2px 0;
             background: #f1f5f9; color: #334155; border-left: 3px solid #64748b; }
.l4-body   { padding: 0 0 2px 10px; }
.l4-badge  { background: #64748b; color:#fff; font-size:9px; padding:1px 5px;
             border-radius:8px; margin-left:5px; font-weight:700; }

/* ── L5: Reports row ───────────────────────────── */
.l5-header { padding: 5px 10px; font-size: 11.5px; margin: 2px 0;
             background: #d1fae5; color: #065f46; border-left: 3px solid #10b981; }
.l5-body   { padding: 0 0 2px 10px; }
.l5-badge  { background: #10b981; color:#fff; font-size:9px; padding:1px 5px;
             border-radius:8px; margin-left:5px; font-weight:700; }

/* ── Orphan section (unmatched) ─────────────────── */
.orphan-header { padding: 6px 11px; font-size: 11px; margin: 3px 0 2px;
                 background: #fce7f3; color: #9d174d; border-left: 3px solid #ec4899; }
.orphan-badge  { background: #ec4899; color:#fff; font-size:9px; padding:1px 5px;
                 border-radius:8px; margin-left:5px; font-weight:700; }

.permission-list { min-height: 200px; }
.empty-state { text-align: center; color: #94a3b8; font-style: italic; padding: 40px 20px; }
</style>
@endsection

@section('js_after')
<script>
// Extended set of action verbs used as permission name prefixes
const ACTION_WORDS = new Set([
    'Add','Edit','Delete','Approve','Verify','Sign','Process','Reduce','Reject',
    'Confirm','Create','Update','Remove','Assign','Record','Generate','Build',
    'Use','Export','Import','Upload','Download','Submit','Share','Log','Set',
    'Manage','View','Change','Date','Customize'
]);

const MENU_TREE = {!! json_encode($menuTree) !!};

// L1 menus that serve as global catch-all sections for unmatched items
const SETTINGS_L1 = 'Settings';
const REPORTS_L1  = 'Reports';

let allPermissions = [], assignedPermissions = [], availablePermissions = [];

$(document).ready(function() {
    allPermissions = [
        @foreach($permissions as $permission)
        { id: {{ $permission->id }}, name: {!! json_encode($permission->name) !!},
          type: {!! json_encode($permission->permission_type) !!},
          assigned: {{ in_array($permission->id, $rolePermissionIds) ? 'true' : 'false' }} },
        @endforeach
    ];
    initializePermissions();
    renderPermissions();

    $('#permission-search').on('input', function() { renderPermissions(); });
    $('#type-filter').on('change', function() { renderPermissions(); });

    $('#remove-all').click(function() {
        allPermissions.forEach(p => { p.assigned = false; $('#hidden-permission-'+p.id).prop('checked', false); });
        initializePermissions(); renderPermissions();
    });
    $('#add-all-filtered').click(function() {
        const q = $('#permission-search').val().toLowerCase(), tf = $('#type-filter').val();
        availablePermissions.forEach(p => {
            if (p.name.toLowerCase().includes(q) && (!tf || p.type === tf)) {
                p.assigned = true; $('#hidden-permission-'+p.id).prop('checked', true);
            }
        });
        initializePermissions(); renderPermissions();
    });
    $(document).on('click', '.perm-hdr', function(e) {
        e.stopPropagation();
        $(this).toggleClass('collapsed');
        $(this).next('.perm-body').slideToggle(140);
    });
});

function initializePermissions() {
    assignedPermissions  = allPermissions.filter(p => p.assigned);
    availablePermissions = allPermissions.filter(p => !p.assigned);
}

/* ── String helpers ─────────────────────────────── */
const norm = s => s.toLowerCase().trim();

function variants(s) {
    // Returns all normalised variants to try in the lookup
    const n = norm(s);
    const vs = new Set([n]);
    if (n.endsWith('ies')) vs.add(n.slice(0,-3)+'y');
    else if (n.endsWith('es') && n.length > 3) vs.add(n.slice(0,-2));
    if (n.endsWith('s') && n.length > 2) vs.add(n.slice(0,-1));
    if (!n.endsWith('s')) { vs.add(n+'s'); vs.add(n+'es'); }
    if (n.endsWith('y') && n.length > 2) vs.add(n.slice(0,-1)+'ies');
    return [...vs];
}

function actionBase(name) {
    const first = name.split(' ')[0];
    return ACTION_WORDS.has(first) ? name.slice(first.length + 1) : null;
}
const isAction = p => p.type === 'CRUD' || (p.type === 'MENU' && actionBase(p.name) !== null);

/* ── Build lookup: normKey → {l1, l2} ───────────── */
function buildLookup() {
    const lk = {};
    const set = (key, val) => { if (!lk[key]) lk[key] = val; };
    MENU_TREE.forEach(m => {
        variants(m.name).forEach(v => set(v, {l1: m.name, l2: null}));
        m.children.forEach(c => {
            variants(c).forEach(v => set(v, {l1: m.name, l2: c}));
        });
    });
    return lk;
}

/* ── Resolve a permission to its {l1, l2} slot ───── */
function resolve(p, lk) {
    let base;
    if (isAction(p)) {
        base = actionBase(p.name) || p.name;
    } else if (p.type === 'REPORT') {
        base = p.name.replace(/\s+Reports?$/i,'').replace(/\s+Summary\s+Report$/i,'').trim();
    } else {
        base = p.name;
    }

    for (const v of variants(base)) {
        if (lk[v]) return lk[v];
    }
    return null;
}

/* ── Build tree ─────────────────────────────────── */
function buildTree(permissions) {
    const lk = buildLookup();
    const tree = {};
    MENU_TREE.forEach(m => {
        tree[m.name] = {self: null, direct: {actions:[], settings:[], reports:[]}, children: {}};
        m.children.forEach(c => {
            tree[m.name].children[c] = {self: null, actions: [], settings: [], reports: []};
        });
    });

    const other = {menu: [], actions: [], settings: [], reports: []};

    permissions.forEach(p => {
        const match = resolve(p, lk);

        if (match) {
            const {l1, l2} = match;
            if (!tree[l1]) tree[l1] = {self: null, direct: {actions:[],settings:[],reports:[]}, children: {}};

            if (!l2) {
                // Goes directly on L1
                if (!isAction(p) && p.type === 'MENU') { tree[l1].self = p; }
                else if (isAction(p))      { tree[l1].direct.actions.push(p); }
                else if (p.type === 'SETTING') { tree[l1].direct.settings.push(p); }
                else if (p.type === 'REPORT')  { tree[l1].direct.reports.push(p); }
            } else {
                if (!tree[l1].children[l2]) tree[l1].children[l2] = {self:null,actions:[],settings:[],reports:[]};
                const node = tree[l1].children[l2];
                if (!isAction(p) && p.type === 'MENU' && norm(p.name) === norm(l2)) {
                    node.self = p;
                } else if (isAction(p))        { node.actions.push(p); }
                else if (p.type === 'SETTING') { node.settings.push(p); }
                else if (p.type === 'REPORT')  { node.reports.push(p); }
                else                           { node.actions.push(p); } // deeper nav
            }
        } else {
            // ── Smart fallbacks ─────────────────────────
            if (p.type === 'REPORT' && tree[REPORTS_L1]) {
                tree[REPORTS_L1].direct.reports.push(p);
            } else if (p.type === 'SETTING' && tree[SETTINGS_L1]) {
                tree[SETTINGS_L1].direct.settings.push(p);
            } else if (isAction(p) && tree[SETTINGS_L1]) {
                // Action with no sub-menu match → Settings catch-all
                tree[SETTINGS_L1].direct.actions.push(p);
            } else {
                const bucket = p.type === 'SETTING' ? 'settings'
                             : p.type === 'REPORT'  ? 'reports'
                             : isAction(p)          ? 'actions' : 'menu';
                other[bucket].push(p);
            }
        }
    });

    return {tree, other};
}

/* ── Render helpers ─────────────────────────────── */
function permItem(p, btnClass, icon) {
    return `<div class="permission-item" data-id="${p.id}">
        <span class="permission-name">${p.name}</span>
        <button type="button" class="btn ${btnClass} btn-sm" data-id="${p.id}"><i class="fas ${icon}"></i></button>
    </div>`;
}

function collapsible(hdrCls, badgeCls, bodyCls, label, items, btnClass, icon) {
    if (!items.length) return '';
    const sorted = items.slice().sort((a,b) => a.name.localeCompare(b.name));
    const body   = sorted.map(p => permItem(p, btnClass, icon)).join('');
    return `<div>
        <div class="perm-hdr ${hdrCls}">${label}<span class="${badgeCls}">${items.length}</span><span class="toggle-arrow"><i class="fa fa-chevron-down"></i></span></div>
        <div class="perm-body ${bodyCls}">${body}</div>
    </div>`;
}

function renderL2(ch, cName, btnClass, icon) {
    const total = (ch.self?1:0) + ch.actions.length + ch.settings.length + ch.reports.length;
    if (!total) return '';

    let body = ch.self ? permItem(ch.self, btnClass, icon) : '';
    body += collapsible('perm-hdr l3-header','l3-badge','l3-body','Actions',  ch.actions,  btnClass, icon);
    body += collapsible('perm-hdr l4-header','l4-badge','l4-body','Settings', ch.settings, btnClass, icon);
    body += collapsible('perm-hdr l5-header','l5-badge','l5-body','Reports',  ch.reports,  btnClass, icon);

    return `<div>
        <div class="perm-hdr l2-header">${cName}<span class="l2-badge">${total}</span><span class="toggle-arrow"><i class="fa fa-chevron-down"></i></span></div>
        <div class="perm-body l2-body">${body}</div>
    </div>`;
}

function renderGroupedList(permissions, action) {
    if (!permissions.length) return '<div class="empty-state">No permissions match your criteria</div>';

    const btnClass = action === 'remove' ? 'btn-outline-danger remove-permission' : 'btn-outline-success add-permission';
    const icon     = action === 'remove' ? 'fa-times' : 'fa-plus';
    const {tree, other} = buildTree(permissions);
    let html = '';

    MENU_TREE.forEach(m => {
        const node = tree[m.name];
        if (!node) return;

        // Count everything under this L1
        let l1Count = (node.self?1:0)
            + node.direct.actions.length + node.direct.settings.length + node.direct.reports.length;
        Object.values(node.children).forEach(ch => {
            l1Count += (ch.self?1:0) + ch.actions.length + ch.settings.length + ch.reports.length;
        });
        if (!l1Count) return;

        let l1Body = node.self ? permItem(node.self, btnClass, icon) : '';

        // L2 children
        m.children.forEach(cName => {
            l1Body += renderL2(node.children[cName] || {self:null,actions:[],settings:[],reports:[]}, cName, btnClass, icon);
        });

        // Direct actions/settings/reports on L1 (e.g. Settings, Reports catch-all nodes)
        l1Body += collapsible('perm-hdr l3-header','l3-badge','l3-body','Actions',  node.direct.actions,  btnClass, icon);
        l1Body += collapsible('perm-hdr l4-header','l4-badge','l4-body','Settings', node.direct.settings, btnClass, icon);
        l1Body += collapsible('perm-hdr l5-header','l5-badge','l5-body','Reports',  node.direct.reports,  btnClass, icon);

        html += `<div>
            <div class="perm-hdr l1-header">${m.name}<span class="l1-badge">${l1Count}</span><span class="toggle-arrow"><i class="fa fa-chevron-down"></i></span></div>
            <div class="perm-body l1-body">${l1Body}</div>
        </div>`;
    });

    // ── Remaining orphans (true unknowns) ───────────
    const orphanCount = other.menu.length + other.actions.length + other.settings.length + other.reports.length;
    if (orphanCount) {
        const actionGroups = {};
        other.actions.forEach(p => {
            const mod = actionBase(p.name) || p.name;
            (actionGroups[mod] = actionGroups[mod]||[]).push(p);
        });

        let body = other.menu.sort((a,b)=>a.name.localeCompare(b.name)).map(p => permItem(p,btnClass,icon)).join('');
        Object.keys(actionGroups).sort().forEach(mod =>
            body += collapsible('perm-hdr l3-header','l3-badge','l3-body', mod, actionGroups[mod], btnClass, icon)
        );
        body += collapsible('perm-hdr l4-header','l4-badge','l4-body','Settings', other.settings, btnClass, icon);
        body += collapsible('perm-hdr l5-header','l5-badge','l5-body','Reports',  other.reports,  btnClass, icon);

        html += `<div>
            <div class="perm-hdr orphan-header">Other<span class="orphan-badge">${orphanCount}</span><span class="toggle-arrow"><i class="fa fa-chevron-down"></i></span></div>
            <div class="perm-body l1-body">${body}</div>
        </div>`;
    }

    return html || '<div class="empty-state">No permissions match your criteria</div>';
}

function renderPermissions() {
    const q  = $('#permission-search').val().toLowerCase();
    const tf = $('#type-filter').val();
    const fa = assignedPermissions.filter(p  => p.name.toLowerCase().includes(q) && (!tf || p.type === tf));
    const fv = availablePermissions.filter(p => p.name.toLowerCase().includes(q) && (!tf || p.type === tf));

    $('#assigned-permissions').html(renderGroupedList(fa, 'remove'));
    $('#available-permissions').html(renderGroupedList(fv, 'add'));
    $('#assigned-count').text(assignedPermissions.length);
    $('#available-count').text(availablePermissions.length);

    $('.add-permission').click(function()    { addPermission($(this).data('id')); });
    $('.remove-permission').click(function() { removePermission($(this).data('id')); });
}

function addPermission(id) {
    const p = allPermissions.find(p => p.id == id);
    if (p) { p.assigned = true; $('#hidden-permission-'+id).prop('checked', true); initializePermissions(); renderPermissions(); }
}
function removePermission(id) {
    const p = allPermissions.find(p => p.id == id);
    if (p) { p.assigned = false; $('#hidden-permission-'+id).prop('checked', false); initializePermissions(); renderPermissions(); }
}
</script>
@endsection
