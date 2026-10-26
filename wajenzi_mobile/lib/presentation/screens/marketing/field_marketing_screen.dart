import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../../core/utils/permissions.dart';
import '../../providers/settings_provider.dart';

/// Field Marketing — daily field sessions and per-business visits.
///
/// Mirrors the portal feature at `field_marketing.index`. Officers see only
/// their own sessions; managers can filter by officer.
class FieldMarketingScreen extends ConsumerStatefulWidget {
  const FieldMarketingScreen({super.key});

  @override
  ConsumerState<FieldMarketingScreen> createState() =>
      _FieldMarketingScreenState();
}

class _FieldMarketingScreenState extends ConsumerState<FieldMarketingScreen> {
  String _month = _formatMonth(DateTime.now());
  int _rev = 0;
  List<Map<String, dynamic>> _officers = const [];

  static String _formatMonth(DateTime dt) =>
      '${dt.year.toString().padLeft(4, '0')}-${dt.month.toString().padLeft(2, '0')}';

  String _tr(bool sw, String en, String swText) => sw ? swText : en;

  @override
  void initState() {
    super.initState();
    _loadOfficers();
  }

  Future<void> _loadOfficers() async {
    try {
      final res = await ref
          .read(apiClientProvider)
          .get('/field-marketing/reference-data');
      final data = res.data is Map ? res.data['data'] : null;
      if (data is Map && mounted) {
        setState(() {
          _officers = (data['officers'] as List? ?? const [])
              .whereType<Map>()
              .map((e) => Map<String, dynamic>.from(e))
              .toList();
        });
      }
    } catch (_) {}
  }

  void _reload() => setState(() => _rev++);

  Future<void> _pickMonth(bool sw) async {
    final parts = _month.split('-');
    final initial = DateTime(int.parse(parts[0]), int.parse(parts[1]), 1);
    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      helpText: _tr(sw, 'Pick any day in the month', 'Chagua siku ndani ya mwezi'),
    );
    if (picked != null) setState(() => _month = _formatMonth(picked));
  }

  Future<void> _openSession() async {
    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => const FractionallySizedBox(
        heightFactor: 0.85,
        child: _SessionFormSheet(),
      ),
    );
    if (saved == true && mounted) _reload();
  }

  Future<void> _setTarget(bool sw, {Map<String, dynamic>? officer}) async {
    final saved = await showDialog<bool>(
      context: context,
      builder: (_) => _TargetDialog(
        officers: _officers,
        month: _month,
        initial: officer,
      ),
    );
    if (saved == true && mounted) _reload();
  }

  Future<void> _service(bool sw, {Map<String, dynamic>? service}) async {
    final ctrl = TextEditingController(text: service?['name']?.toString() ?? '');
    final name = await showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(service == null
            ? _tr(sw, 'Add service', 'Ongeza huduma')
            : _tr(sw, 'Edit service', 'Badilisha huduma')),
        content: TextField(
          controller: ctrl,
          autofocus: true,
          textCapitalization: TextCapitalization.characters,
          decoration: _dec(_tr(sw, 'Service name', 'Jina la huduma')),
          onSubmitted: (v) => Navigator.pop(ctx, v),
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: Text(_tr(sw, 'Cancel', 'Ghairi'))),
          FilledButton(
              onPressed: () => Navigator.pop(ctx, ctrl.text),
              child: Text(_tr(sw, 'Save', 'Hifadhi'))),
        ],
      ),
    );
    ctrl.dispose();
    if (name == null || name.trim().isEmpty) return;
    try {
      final api = ref.read(apiClientProvider);
      if (service == null) {
        await api.post('/field-marketing/services', data: {'name': name.trim()});
      } else {
        await api.put('/field-marketing/services/${service['id']}',
            data: {'name': name.trim()});
      }
      if (mounted) _reload();
    } catch (e) {
      _snack(_errMsg(e));
    }
  }

  Future<void> _deleteService(bool sw, Map<String, dynamic> service) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(_tr(sw, 'Delete service', 'Futa huduma')),
        content: Text('${service['name']}'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: Text(_tr(sw, 'Cancel', 'Ghairi'))),
          FilledButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: Text(_tr(sw, 'Delete', 'Futa'))),
        ],
      ),
    );
    if (ok != true) return;
    try {
      await ref
          .read(apiClientProvider)
          .delete('/field-marketing/services/${service['id']}');
      if (mounted) _reload();
    } catch (e) {
      _snack(_errMsg(e));
    }
  }

  void _snack(String m) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(m)));
  }

  @override
  Widget build(BuildContext context) {
    final sw = ref.watch(isSwahiliProvider);
    final scaffoldKey = ref.watch(rootScaffoldKeyProvider);

    final canAdd = hasPermission(ref, 'Add Field Marketing Session');
    final canTarget = hasPermission(ref, 'Set Field Marketing Target');
    final canStats = hasPermission(ref, 'View Field Marketing Stats');
    final canServices = hasPermission(ref, 'Manage Field Marketing Services');

    final key = '$_month-$_rev';
    final tabs = <String>[];
    final views = <Widget>[];

    tabs.add(_tr(sw, 'Sessions', 'Vikao'));
    views.add(_SessionsTab(key: ValueKey('s$key'), month: _month, sw: sw, onChanged: _reload));

    if (canTarget) {
      tabs.add(_tr(sw, 'Targets', 'Malengo'));
      views.add(_TargetsTab(
        key: ValueKey('t$key'),
        month: _month,
        sw: sw,
        onEdit: (o) => _setTarget(sw, officer: o),
      ));
    }
    if (canStats) {
      tabs.add(_tr(sw, 'Stats', 'Takwimu'));
      views.add(_StatsTab(key: ValueKey('x$key'), month: _month, sw: sw));
    }
    tabs.add(_tr(sw, 'All Visits', 'Ziara Zote'));
    views.add(_VisitsTab(key: ValueKey('v$key'), month: _month, sw: sw, officers: _officers));
    if (canServices) {
      tabs.add(_tr(sw, 'Services', 'Huduma'));
      views.add(_ServicesTab(
        key: ValueKey('c$key'),
        sw: sw,
        onEdit: (s) => _service(sw, service: s),
        onDelete: (s) => _deleteService(sw, s),
      ));
    }

    return DefaultTabController(
      key: ValueKey(tabs.length),
      length: tabs.length,
      child: Scaffold(
        appBar: AppBar(
          leading: IconButton(
            icon: const Icon(Icons.menu_rounded),
            onPressed: () => scaffoldKey.currentState?.openDrawer(),
          ),
          title: Text(_tr(sw, 'Field Marketing', 'Masoko ya Uwandani'),
              style: AppType.display(18)),
          actions: [
            IconButton(
              tooltip: _tr(sw, 'Refresh', 'Onyesha tena'),
              onPressed: _reload,
              icon: const Icon(Icons.refresh_rounded),
            ),
          ],
          bottom: TabBar(
            isScrollable: true,
            tabAlignment: TabAlignment.start,
            tabs: [for (final t in tabs) Tab(text: t)],
          ),
        ),
        body: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 10, 16, 4),
              child: Wrap(
                spacing: 8,
                runSpacing: 8,
                crossAxisAlignment: WrapCrossAlignment.center,
                children: [
                  OutlinedButton.icon(
                    onPressed: () => _pickMonth(sw),
                    icon: const Icon(Icons.calendar_month_rounded, size: 18),
                    label: Text(_month),
                  ),
                  if (canAdd)
                    FilledButton.icon(
                      onPressed: _openSession,
                      icon: const Icon(Icons.add, size: 18),
                      label: Text(_tr(sw, 'New Session', 'Kikao Kipya')),
                    ),
                  if (canTarget)
                    FilledButton.tonalIcon(
                      onPressed: () => _setTarget(sw),
                      icon: const Icon(Icons.flag_outlined, size: 18),
                      label: Text(_tr(sw, 'Set Target', 'Weka Lengo')),
                    ),
                  if (canServices)
                    FilledButton.tonalIcon(
                      onPressed: () => _service(sw),
                      icon: const Icon(Icons.add_circle_outline, size: 18),
                      label: Text(_tr(sw, 'Add Service', 'Ongeza Huduma')),
                    ),
                ],
              ),
            ),
            Expanded(child: TabBarView(children: views)),
          ],
        ),
      ),
    );
  }
}

String _errMsg(Object e) {
  try {
    final data = (e as dynamic).response?.data;
    if (data is Map && data['message'] is String) return data['message'];
  } catch (_) {}
  return e.toString();
}

/// Loads one endpoint and hands its `data` to [builder]; pull to refresh.
class _FmRemote extends ConsumerStatefulWidget {
  final String path;
  final Map<String, dynamic> query;
  final Widget Function(BuildContext context, dynamic data) builder;

  const _FmRemote({
    super.key,
    required this.path,
    required this.query,
    required this.builder,
  });

  @override
  ConsumerState<_FmRemote> createState() => _FmRemoteState();
}

class _FmRemoteState extends ConsumerState<_FmRemote> {
  late Future<dynamic> _future = _load();

  Future<dynamic> _load() async {
    final res = await ref
        .read(apiClientProvider)
        .get(widget.path, queryParameters: widget.query);
    return res.data is Map ? res.data['data'] : null;
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: () async => setState(() => _future = _load()),
      child: FutureBuilder<dynamic>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snap.hasError) {
            return _ErrorView(
              message: _errMsg(snap.error!),
              onRetry: () => setState(() => _future = _load()),
            );
          }
          return widget.builder(context, snap.data);
        },
      ),
    );
  }
}

List<Map<String, dynamic>> _maps(dynamic v) => (v as List? ?? const [])
    .whereType<Map>()
    .map((e) => Map<String, dynamic>.from(e))
    .toList();

int _int(dynamic v) => v is num ? v.toInt() : int.tryParse('$v') ?? 0;

// ───────────────────────────── Sessions ─────────────────────────────

class _SessionsTab extends ConsumerStatefulWidget {
  final String month;
  final bool sw;
  final VoidCallback onChanged;

  const _SessionsTab({
    super.key,
    required this.month,
    required this.sw,
    required this.onChanged,
  });

  @override
  ConsumerState<_SessionsTab> createState() => _SessionsTabState();
}

class _SessionsTabState extends ConsumerState<_SessionsTab> {
  final _searchCtrl = TextEditingController();
  String _search = '';

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final sw = widget.sw;
    final isDark = ref.watch(isDarkModeProvider);
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
          child: TextField(
            controller: _searchCtrl,
            decoration: InputDecoration(
              hintText: sw
                  ? 'Tafuta kikao kwa eneo au nambari…'
                  : 'Search sessions by area or number…',
              prefixIcon: const Icon(Icons.search_rounded),
              suffixIcon: _search.isEmpty
                  ? null
                  : IconButton(
                      icon: const Icon(Icons.clear_rounded),
                      onPressed: () {
                        _searchCtrl.clear();
                        setState(() => _search = '');
                      },
                    ),
              filled: true,
              fillColor: isDark
                  ? Colors.white.withValues(alpha: 0.06)
                  : Colors.black.withValues(alpha: 0.04),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(14),
                borderSide: BorderSide.none,
              ),
            ),
            onSubmitted: (v) => setState(() => _search = v.trim()),
          ),
        ),
        Expanded(
          child: _FmRemote(
            key: ValueKey(_search),
            path: '/field-marketing',
            query: {
              'month': widget.month,
              if (_search.isNotEmpty) 'search': _search,
            },
            builder: (context, data) {
              final sessions = _maps(data is Map ? data['sessions'] : null);
              if (sessions.isEmpty) {
                return ListView(children: [
                  const SizedBox(height: 40),
                  _EmptyState(
                    icon: Icons.travel_explore_outlined,
                    title: sw
                        ? 'Hakuna vikao mwezi huu.'
                        : 'No sessions this month.',
                  ),
                ]);
              }
              return ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  for (final s in sessions)
                    _SessionCard(
                      session: s,
                      isSwahili: sw,
                      onChanged: widget.onChanged,
                    ),
                ],
              );
            },
          ),
        ),
      ],
    );
  }
}

// ───────────────────────────── Targets ─────────────────────────────

class _TargetsTab extends StatelessWidget {
  final String month;
  final bool sw;
  final ValueChanged<Map<String, dynamic>> onEdit;

  const _TargetsTab({
    super.key,
    required this.month,
    required this.sw,
    required this.onEdit,
  });

  @override
  Widget build(BuildContext context) {
    return _FmRemote(
      path: '/field-marketing/targets',
      query: {'month': month},
      builder: (context, data) {
        final officers = _maps(data is Map ? data['officers'] : null);
        if (officers.isEmpty) {
          return ListView(children: [
            const SizedBox(height: 40),
            _EmptyState(
              icon: Icons.flag_outlined,
              title: sw ? 'Hakuna maafisa.' : 'No officers found.',
            ),
          ]);
        }
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            for (final o in officers)
              Card(
                margin: const EdgeInsets.only(bottom: 12),
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text('${o['officer_name'] ?? '-'}',
                                style: AppType.display(15,
                                    weight: FontWeight.w700)),
                          ),
                          IconButton(
                            tooltip: sw ? 'Weka lengo' : 'Set target',
                            icon: const Icon(Icons.edit_outlined, size: 20),
                            onPressed: () => onEdit(o),
                          ),
                        ],
                      ),
                      Text(
                        sw
                            ? 'Walioshinda ${_int(o['converted'])} / lengo ${_int(o['target_conversions'])}'
                            : 'Won ${_int(o['converted'])} / target ${_int(o['target_conversions'])}',
                        style: const TextStyle(fontWeight: FontWeight.w600),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        sw
                            ? 'Ziara ${_int(o['visits'])} / lengo ${_int(o['target_visits'])}'
                            : 'Visits ${_int(o['visits'])} / target ${_int(o['target_visits'])}',
                        style: const TextStyle(color: AppColors.textSecondary),
                      ),
                      const SizedBox(height: 10),
                      ClipRRect(
                        borderRadius: BorderRadius.circular(6),
                        child: LinearProgressIndicator(
                          value: _int(o['progress']) / 100,
                          minHeight: 8,
                          color: AppColors.brandGreen,
                          backgroundColor:
                              AppColors.brandGreen.withValues(alpha: 0.15),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
          ],
        );
      },
    );
  }
}

class _TargetDialog extends ConsumerStatefulWidget {
  final List<Map<String, dynamic>> officers;
  final String month;
  final Map<String, dynamic>? initial;

  const _TargetDialog({
    required this.officers,
    required this.month,
    this.initial,
  });

  @override
  ConsumerState<_TargetDialog> createState() => _TargetDialogState();
}

class _TargetDialogState extends ConsumerState<_TargetDialog> {
  int? _officerId;
  late final TextEditingController _visits;
  late final TextEditingController _conv;
  late String _month = widget.month;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _officerId = widget.initial?['officer_id'] as int?;
    _visits = TextEditingController(
        text: widget.initial == null ? '' : '${_int(widget.initial!['target_visits'])}');
    _conv = TextEditingController(
        text: widget.initial == null ? '' : '${_int(widget.initial!['target_conversions'])}');
  }

  @override
  void dispose() {
    _visits.dispose();
    _conv.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final sw = ref.read(isSwahiliProvider);
    if (_officerId == null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(sw ? 'Chagua afisa' : 'Select an officer')));
      return;
    }
    setState(() => _saving = true);
    try {
      await ref.read(apiClientProvider).post('/field-marketing/targets', data: {
        'officer_id': _officerId,
        'month': _month,
        'target_visits': int.tryParse(_visits.text.trim()) ?? 0,
        'target_conversions': int.tryParse(_conv.text.trim()) ?? 0,
      });
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _saving = false);
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(_errMsg(e))));
    }
  }

  @override
  Widget build(BuildContext context) {
    final sw = ref.watch(isSwahiliProvider);
    return AlertDialog(
      title: Text(sw ? 'Weka Lengo la Mwezi' : 'Set Monthly Target'),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            DropdownButtonFormField<int>(
              isExpanded: true,
              initialValue: _officerId,
              decoration: _dec(sw ? 'Afisa' : 'Officer'),
              items: [
                for (final o in widget.officers)
                  DropdownMenuItem<int>(
                    value: o['id'] as int,
                    child: Text('${o['name']}', overflow: TextOverflow.ellipsis),
                  ),
              ],
              onChanged: (v) => setState(() => _officerId = v),
            ),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: () async {
                final p = _month.split('-');
                final picked = await showDatePicker(
                  context: context,
                  initialDate: DateTime(int.parse(p[0]), int.parse(p[1]), 1),
                  firstDate: DateTime(2020),
                  lastDate: DateTime.now().add(const Duration(days: 365)),
                );
                if (picked != null) {
                  setState(() => _month =
                      '${picked.year.toString().padLeft(4, '0')}-${picked.month.toString().padLeft(2, '0')}');
                }
              },
              icon: const Icon(Icons.calendar_month_rounded, size: 18),
              label: Text(_month),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _visits,
              keyboardType: TextInputType.number,
              decoration: _dec(sw ? 'Lengo la ziara' : 'Target visits'),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _conv,
              keyboardType: TextInputType.number,
              decoration:
                  _dec(sw ? 'Lengo la walioshinda' : 'Target conversions'),
            ),
          ],
        ),
      ),
      actions: [
        TextButton(
            onPressed: _saving ? null : () => Navigator.pop(context),
            child: Text(sw ? 'Ghairi' : 'Cancel')),
        FilledButton(
            onPressed: _saving ? null : _save,
            child: Text(sw ? 'Hifadhi' : 'Save')),
      ],
    );
  }
}

// ───────────────────────────── Stats ─────────────────────────────

class _StatsTab extends StatelessWidget {
  final String month;
  final bool sw;

  const _StatsTab({super.key, required this.month, required this.sw});

  @override
  Widget build(BuildContext context) {
    return _FmRemote(
      path: '/field-marketing/stats',
      query: {'month': month},
      builder: (context, data) {
        final stats = data is Map ? Map<String, dynamic>.from(data) : const <String, dynamic>{};
        final byOfficer = _maps(stats['by_officer']);

        Widget tile(String label, dynamic value, Color color, IconData icon) =>
            Container(
              width: 150,
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(icon, color: color, size: 20),
                  const SizedBox(height: 8),
                  Text('${_int(value)}',
                      style: AppType.display(24,
                          weight: FontWeight.w800, color: color)),
                  Text(label, style: const TextStyle(fontSize: 12)),
                ],
              ),
            );

        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: [
                tile(sw ? 'Jumla ya Ziara' : 'Total Visits', stats['total'],
                    AppColors.brandBlue, Icons.place_outlined),
                tile(sw ? 'Walioshinda' : 'Converted', stats['converted'],
                    AppColors.brandGreen, Icons.task_alt_rounded),
                tile(sw ? 'Wanavutiwa' : 'Interested', stats['interested'],
                    AppColors.brandBlue, Icons.thumb_up_alt_outlined),
                tile(sw ? 'Hawavutiwi' : 'Not Interested',
                    stats['not_interested'], AppColors.error,
                    Icons.thumb_down_alt_outlined),
                tile(sw ? 'Kuendeleza' : 'Follow Up', stats['follow_up'],
                    AppColors.brandYellow, Icons.event_repeat_rounded),
              ],
            ),
            const SizedBox(height: 20),
            Text(sw ? 'Kwa Afisa' : 'By Officer', style: AppType.display(16)),
            const SizedBox(height: 8),
            if (byOfficer.isEmpty)
              _EmptyState(
                icon: Icons.bar_chart_rounded,
                title: sw ? 'Hakuna data mwezi huu.' : 'No data this month.',
              )
            else
              for (final o in byOfficer)
                Card(
                  margin: const EdgeInsets.only(bottom: 10),
                  child: Padding(
                    padding: const EdgeInsets.all(14),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('${o['officer_name'] ?? '-'}',
                            style: AppType.display(14,
                                weight: FontWeight.w700)),
                        const SizedBox(height: 4),
                        Text(
                          sw
                              ? 'Ziara ${_int(o['visits'])} · Walioshinda ${_int(o['converted'])} · ${_int(o['rate'])}%'
                              : 'Visits ${_int(o['visits'])} · Clients won ${_int(o['converted'])} · ${_int(o['rate'])}%',
                          style:
                              const TextStyle(color: AppColors.textSecondary),
                        ),
                        const SizedBox(height: 8),
                        ClipRRect(
                          borderRadius: BorderRadius.circular(6),
                          child: LinearProgressIndicator(
                            value: _int(o['rate']) / 100,
                            minHeight: 8,
                            color: AppColors.brandGreen,
                            backgroundColor:
                                AppColors.brandGreen.withValues(alpha: 0.15),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
          ],
        );
      },
    );
  }
}

// ───────────────────────────── All Visits ─────────────────────────────

class _VisitsTab extends ConsumerStatefulWidget {
  final String month;
  final bool sw;
  final List<Map<String, dynamic>> officers;

  const _VisitsTab({
    super.key,
    required this.month,
    required this.sw,
    required this.officers,
  });

  @override
  ConsumerState<_VisitsTab> createState() => _VisitsTabState();
}

class _VisitsTabState extends ConsumerState<_VisitsTab> {
  DateTime? _from;
  DateTime? _to;
  int? _officerId;
  String? _status;

  static String _fmt(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  Future<void> _pick(bool from) async {
    final parts = widget.month.split('-');
    final base = DateTime(int.parse(parts[0]), int.parse(parts[1]), 1);
    final picked = await showDatePicker(
      context: context,
      initialDate: (from ? _from : _to) ?? base,
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (picked != null) setState(() => from ? _from = picked : _to = picked);
  }

  @override
  Widget build(BuildContext context) {
    final sw = widget.sw;
    final statuses = <String, String>{
      'interested': sw ? 'Wanavutiwa' : 'Interested',
      'not_interested': sw ? 'Hawavutiwi' : 'Not Interested',
      'follow_up': sw ? 'Kuendeleza' : 'Follow Up',
      'converted': sw ? 'Walioshinda' : 'Converted',
    };

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
          child: Column(
            children: [
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () => _pick(true),
                      icon: const Icon(Icons.event, size: 16),
                      label: Text(_from == null ? (sw ? 'Kuanzia' : 'From') : _fmt(_from!),
                          overflow: TextOverflow.ellipsis),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () => _pick(false),
                      icon: const Icon(Icons.event, size: 16),
                      label: Text(_to == null ? (sw ? 'Hadi' : 'To') : _fmt(_to!),
                          overflow: TextOverflow.ellipsis),
                    ),
                  ),
                  if (_from != null || _to != null)
                    IconButton(
                      tooltip: sw ? 'Futa tarehe' : 'Clear dates',
                      icon: const Icon(Icons.clear_rounded),
                      onPressed: () => setState(() {
                        _from = null;
                        _to = null;
                      }),
                    ),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  if (widget.officers.length > 1) ...[
                    Expanded(
                      child: DropdownButtonFormField<int?>(
                        isExpanded: true,
                        initialValue: _officerId,
                        decoration: _dec(sw ? 'Afisa' : 'Officer'),
                        items: [
                          DropdownMenuItem<int?>(
                              value: null,
                              child: Text(sw ? 'Wote' : 'All')),
                          for (final o in widget.officers)
                            DropdownMenuItem<int?>(
                              value: o['id'] as int,
                              child: Text('${o['name']}',
                                  overflow: TextOverflow.ellipsis),
                            ),
                        ],
                        onChanged: (v) => setState(() => _officerId = v),
                      ),
                    ),
                    const SizedBox(width: 8),
                  ],
                  Expanded(
                    child: DropdownButtonFormField<String?>(
                      isExpanded: true,
                      initialValue: _status,
                      decoration: _dec(sw ? 'Hali' : 'Status'),
                      items: [
                        DropdownMenuItem<String?>(
                            value: null, child: Text(sw ? 'Zote' : 'All')),
                        for (final e in statuses.entries)
                          DropdownMenuItem<String?>(
                              value: e.key,
                              child: Text(e.value,
                                  overflow: TextOverflow.ellipsis)),
                      ],
                      onChanged: (v) => setState(() => _status = v),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
        Expanded(
          child: _FmRemote(
            key: ValueKey('${_from}_${_to}_${_officerId}_$_status'),
            path: '/field-marketing/visits',
            query: {
              'month': widget.month,
              if (_from != null) 'from_date': _fmt(_from!),
              if (_to != null) 'to_date': _fmt(_to!),
              if (_officerId != null) 'officer_id': _officerId,
              if (_status != null) 'status': _status,
            },
            builder: (context, data) {
              final visits = _maps(data);
              if (visits.isEmpty) {
                return ListView(children: [
                  const SizedBox(height: 40),
                  _EmptyState(
                    icon: Icons.place_outlined,
                    title: sw ? 'Hakuna ziara.' : 'No visits found.',
                  ),
                ]);
              }
              return ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: visits.length,
                itemBuilder: (context, i) => _VisitCard(visit: visits[i], sw: sw),
              );
            },
          ),
        ),
      ],
    );
  }
}

class _VisitCard extends StatelessWidget {
  final Map<String, dynamic> visit;
  final bool sw;

  const _VisitCard({required this.visit, required this.sw});

  @override
  Widget build(BuildContext context) {
    final services = _maps(visit['services']);
    final lines = <String>[
      if ('${visit['date'] ?? ''}'.isNotEmpty) '${visit['date']}',
      if ('${visit['officer_name'] ?? ''}'.isNotEmpty) '${visit['officer_name']}',
      if ('${visit['area'] ?? ''}'.isNotEmpty) '${visit['area']}',
    ];
    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text('${visit['business_name'] ?? '-'}',
                      style: AppType.display(15, weight: FontWeight.w700)),
                ),
                _StatusBadge(status: visit['status']?.toString()),
              ],
            ),
            if (lines.isNotEmpty) ...[
              const SizedBox(height: 4),
              Text(lines.join(' · '),
                  style: const TextStyle(color: AppColors.textSecondary)),
            ],
            if ('${visit['location'] ?? ''}'.isNotEmpty) ...[
              const SizedBox(height: 4),
              Text('${visit['location']}'),
            ],
            if ('${visit['phone'] ?? ''}'.isNotEmpty) ...[
              const SizedBox(height: 2),
              Text('${visit['phone']}'),
            ],
            if (services.isNotEmpty) ...[
              const SizedBox(height: 8),
              Wrap(
                spacing: 6,
                runSpacing: 6,
                children: [
                  for (final s in services)
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(
                        color: AppColors.brandBlue.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text('${s['name']}',
                          style: const TextStyle(
                              fontSize: 10, fontWeight: FontWeight.w600)),
                    ),
                ],
              ),
            ],
            if ('${visit['next_followup_date'] ?? ''}'.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                '${sw ? 'Kuendeleza' : 'Next follow-up'}: ${visit['next_followup_date']}',
                style: const TextStyle(fontSize: 12),
              ),
            ],
            if ('${visit['client_name'] ?? ''}'.isNotEmpty) ...[
              const SizedBox(height: 2),
              Text('${sw ? 'Mteja' : 'Client'}: ${visit['client_name']}',
                  style: const TextStyle(fontSize: 12)),
            ],
          ],
        ),
      ),
    );
  }
}

// ───────────────────────────── Services ─────────────────────────────

class _ServicesTab extends StatelessWidget {
  final bool sw;
  final ValueChanged<Map<String, dynamic>> onEdit;
  final ValueChanged<Map<String, dynamic>> onDelete;

  const _ServicesTab({
    super.key,
    required this.sw,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return _FmRemote(
      path: '/field-marketing/services',
      query: const {},
      builder: (context, data) {
        final services = _maps(data);
        if (services.isEmpty) {
          return ListView(children: [
            const SizedBox(height: 40),
            _EmptyState(
              icon: Icons.category_outlined,
              title: sw ? 'Hakuna huduma.' : 'No services yet.',
            ),
          ]);
        }
        return ListView.builder(
          padding: const EdgeInsets.all(16),
          itemCount: services.length,
          itemBuilder: (context, i) {
            final s = services[i];
            return Card(
              margin: const EdgeInsets.only(bottom: 8),
              child: ListTile(
                leading: CircleAvatar(
                  radius: 14,
                  child: Text('${i + 1}', style: const TextStyle(fontSize: 12)),
                ),
                title: Text('${s['name']}'),
                trailing: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    IconButton(
                      tooltip: sw ? 'Badilisha' : 'Edit',
                      icon: const Icon(Icons.edit_outlined, size: 20),
                      onPressed: () => onEdit(s),
                    ),
                    IconButton(
                      tooltip: sw ? 'Futa' : 'Delete',
                      icon: const Icon(Icons.delete_outline,
                          size: 20, color: AppColors.error),
                      onPressed: () => onDelete(s),
                    ),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }
}

class _SessionCard extends ConsumerWidget {
  final Map<String, dynamic> session;
  final bool isSwahili;
  final VoidCallback onChanged;

  const _SessionCard({
    required this.session,
    required this.isSwahili,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final visits = (session['visits_count'] ?? 0) as int;
    final converted = (session['converted_count'] ?? 0) as int;
    final closed = (session['status'] ?? 'open') == 'closed';

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () => _showSessionDetail(context, ref, session),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      '${session['session_number'] ?? '-'}',
                      style: AppType.display(15, weight: FontWeight.w700),
                    ),
                  ),
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: closed
                          ? AppColors.draft.withValues(alpha: 0.15)
                          : AppColors.brandGreen.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      closed
                          ? (isSwahili ? 'Imefungwa' : 'Closed')
                          : (isSwahili ? 'Wazi' : 'Open'),
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color:
                            closed ? AppColors.draft : AppColors.brandGreen,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              Text(
                '${session['officer_name'] ?? '-'} · ${session['date'] ?? '-'}',
                style: const TextStyle(color: AppColors.textSecondary),
              ),
              const SizedBox(height: 4),
              Text(
                '${session['area'] ?? (isSwahili ? 'Hakuna eneo' : 'No area')}',
                style: const TextStyle(color: AppColors.textSecondary),
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  _statChip(Icons.place_outlined,
                      '$visits ${isSwahili ? 'ziara' : 'visits'}',
                      AppColors.brandBlue),
                  const SizedBox(width: 8),
                  _statChip(Icons.handshake_outlined,
                      '$converted ${isSwahili ? 'walibadilika' : 'converted'}',
                      AppColors.brandGreen),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _statChip(IconData icon, String label, Color color) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.10),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, color: color, size: 14),
            const SizedBox(width: 4),
            Text(label,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: color,
                )),
          ],
        ),
      );
}

Future<void> _showSessionDetail(
  BuildContext context,
  WidgetRef ref,
  Map<String, dynamic> session,
) async {
  final id = session['id'] as int?;
  if (id == null) return;
  await showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    backgroundColor: Colors.transparent,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.92,
      child: _SessionDetailSheet(sessionId: id),
    ),
  );
}

class _SessionDetailSheet extends ConsumerStatefulWidget {
  final int sessionId;
  const _SessionDetailSheet({required this.sessionId});

  @override
  ConsumerState<_SessionDetailSheet> createState() =>
      _SessionDetailSheetState();
}

class _SessionDetailSheetState
    extends ConsumerState<_SessionDetailSheet> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<Map<String, dynamic>> _load() async {
    final api = ref.read(apiClientProvider);
    final res = await api.get('/field-marketing/sessions/${widget.sessionId}');
    final data = res.data is Map ? Map<String, dynamic>.from(res.data as Map) : {};
    return data['data'] is Map
        ? Map<String, dynamic>.from(data['data'] as Map)
        : <String, dynamic>{};
  }

  String _tr(bool sw, String en, String swText) => sw ? swText : en;

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDark = ref.watch(isDarkModeProvider);
    return Container(
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1A1A1A) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: FutureBuilder<Map<String, dynamic>>(
          future: _future,
          builder: (context, snap) {
            if (snap.connectionState == ConnectionState.waiting) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snap.hasError) {
              return _ErrorView(
                message: snap.error.toString(),
                onRetry: () => setState(() => _future = _load()),
              );
            }
            final data = snap.data ?? const {};
            final visits = (data['visits'] as List? ?? const [])
                .whereType<Map>()
                .map((e) => Map<String, dynamic>.from(e))
                .toList();

            return ListView(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
              children: [
                Center(
                  child: Container(
                    width: 50,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.grey.withValues(alpha: 0.3),
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  '${data['session_number'] ?? ''}',
                  style: AppType.display(20),
                ),
                Text(
                  '${data['officer_name'] ?? '-'} · ${data['date'] ?? '-'} · ${data['area'] ?? '-'}',
                  style: const TextStyle(color: AppColors.textSecondary),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        icon: const Icon(Icons.add),
                        label:
                            Text(_tr(isSwahili, 'Add Visit', 'Ongeza Ziara')),
                        onPressed: () async {
                          final saved = await showModalBottomSheet<bool>(
                            context: context,
                            isScrollControlled: true,
                            backgroundColor: Colors.transparent,
                            builder: (_) => FractionallySizedBox(
                              heightFactor: 0.92,
                              child: _VisitFormSheet(
                                sessionId: widget.sessionId,
                              ),
                            ),
                          );
                          if (saved == true) {
                            setState(() => _future = _load());
                          }
                        },
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Text(
                  _tr(isSwahili, 'Visits (${visits.length})',
                      'Ziara (${visits.length})'),
                  style: AppType.display(16),
                ),
                const SizedBox(height: 8),
                if (visits.isEmpty)
                  _EmptyState(
                    icon: Icons.storefront_outlined,
                    title: _tr(isSwahili, 'No visits yet.',
                        'Hakuna ziara bado.'),
                  )
                else
                  ...visits.map(
                    (v) => Container(
                      margin: const EdgeInsets.only(bottom: 8),
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: isDark
                            ? Colors.white.withValues(alpha: 0.05)
                            : Colors.black.withValues(alpha: 0.03),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  '${v['business_name'] ?? '-'}',
                                  style: const TextStyle(
                                      fontWeight: FontWeight.w700),
                                ),
                              ),
                              _StatusBadge(status: v['status']?.toString()),
                            ],
                          ),
                          if ((v['location'] ?? '').toString().isNotEmpty)
                            Padding(
                              padding: const EdgeInsets.only(top: 2),
                              child: Text(
                                '${v['location']}',
                                style: const TextStyle(
                                    color: AppColors.textSecondary),
                              ),
                            ),
                          if ((v['phone'] ?? '').toString().isNotEmpty)
                            Padding(
                              padding: const EdgeInsets.only(top: 2),
                              child: Text(
                                '${v['phone']}',
                                style: const TextStyle(
                                    color: AppColors.textSecondary,
                                    fontFamily: 'monospace'),
                              ),
                            ),
                          if ((v['notes'] ?? '').toString().isNotEmpty)
                            Padding(
                              padding: const EdgeInsets.only(top: 6),
                              child: Text('${v['notes']}'),
                            ),
                        ],
                      ),
                    ),
                  ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  final String? status;
  const _StatusBadge({required this.status});

  @override
  Widget build(BuildContext context) {
    final color = switch (status) {
      'converted' => AppColors.brandGreen,
      'interested' => AppColors.brandBlue,
      'follow_up' => AppColors.brandYellow,
      'not_interested' => AppColors.error,
      _ => AppColors.draft,
    };
    final label = switch (status) {
      'converted' => 'Converted',
      'interested' => 'Interested',
      'follow_up' => 'Follow Up',
      'not_interested' => 'Not Interested',
      _ => '-',
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w700,
          color: color,
        ),
      ),
    );
  }
}

class _SessionFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? session;
  const _SessionFormSheet({this.session});

  @override
  ConsumerState<_SessionFormSheet> createState() => _SessionFormSheetState();
}

class _SessionFormSheetState extends ConsumerState<_SessionFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final _areaCtrl =
      TextEditingController(text: widget.session?['area']?.toString() ?? '');
  late final _notesCtrl =
      TextEditingController(text: widget.session?['notes']?.toString() ?? '');
  late DateTime _date = _parseDate(widget.session?['date']?.toString());
  int? _officerId;
  List<Map<String, dynamic>> _officers = const [];
  bool _isFieldOfficer = false;
  bool _loading = false;
  bool _refsLoading = true;

  @override
  void initState() {
    super.initState();
    _loadRefs();
  }

  Future<void> _loadRefs() async {
    try {
      final res = await ref
          .read(apiClientProvider)
          .get('/field-marketing/reference-data');
      final data =
          (res.data is Map ? res.data['data'] : null) as Map<String, dynamic>?;
      setState(() {
        _officers = ((data?['officers'] as List?) ?? const [])
            .whereType<Map>()
            .map((e) => Map<String, dynamic>.from(e))
            .toList();
        _isFieldOfficer = data?['is_field_officer'] == true;
        _officerId = widget.session?['officer_id'] as int?;
      });
    } catch (_) {} finally {
      if (mounted) setState(() => _refsLoading = false);
    }
  }

  @override
  void dispose() {
    _areaCtrl.dispose();
    _notesCtrl.dispose();
    super.dispose();
  }

  static DateTime _parseDate(String? s) {
    if (s == null || s.isEmpty) return DateTime.now();
    return DateTime.tryParse(s) ?? DateTime.now();
  }

  String _formatDate(DateTime dt) =>
      '${dt.year.toString().padLeft(4, '0')}-${dt.month.toString().padLeft(2, '0')}-${dt.day.toString().padLeft(2, '0')}';

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _loading = true);
    try {
      final api = ref.read(apiClientProvider);
      final payload = {
        if (!_isFieldOfficer && _officerId != null) 'officer_id': _officerId,
        'area': _areaCtrl.text.trim(),
        'date': _formatDate(_date),
        'notes': _notesCtrl.text.trim(),
      };
      if (widget.session != null) {
        await api.put('/field-marketing/sessions/${widget.session!['id']}',
            data: payload);
      } else {
        await api.post('/field-marketing/sessions', data: payload);
      }
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Save failed: $e')),
      );
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDark = ref.watch(isDarkModeProvider);

    return Container(
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1A1A1A) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: _refsLoading
            ? const Center(child: CircularProgressIndicator())
            : Form(
                key: _formKey,
                child: ListView(
                  padding: EdgeInsets.fromLTRB(20, 16, 20,
                      MediaQuery.of(context).viewInsets.bottom + 28),
                  children: [
                    Text(
                      isSwahili ? 'Kikao Kipya' : 'New Session',
                      textAlign: TextAlign.center,
                      style: AppType.display(20),
                    ),
                    const SizedBox(height: 16),
                    if (!_isFieldOfficer)
                      DropdownButtonFormField<int>(
      isExpanded: true,
                        value: _officerId,
                        decoration: _dec(isSwahili ? 'Afisa' : 'Officer'),
                        items: _officers
                            .map((o) => DropdownMenuItem<int>(
                                  value: o['id'] as int,
                                  child: Text('${o['name'] ?? '-'}'),
                                ))
                            .toList(),
                        onChanged: (v) => setState(() => _officerId = v),
                      ),
                    if (!_isFieldOfficer) const SizedBox(height: 12),
                    TextFormField(
                      controller: _areaCtrl,
                      decoration: _dec(isSwahili ? 'Eneo' : 'Area'),
                    ),
                    const SizedBox(height: 12),
                    InkWell(
                      onTap: () async {
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: _date,
                          firstDate: DateTime(2020),
                          lastDate:
                              DateTime.now().add(const Duration(days: 365)),
                        );
                        if (picked != null) setState(() => _date = picked);
                      },
                      child: InputDecorator(
                        decoration: _dec(isSwahili ? 'Tarehe' : 'Date'),
                        child: Text(_formatDate(_date)),
                      ),
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _notesCtrl,
                      maxLines: 4,
                      decoration: _dec(isSwahili ? 'Maelezo' : 'Notes'),
                    ),
                    const SizedBox(height: 20),
                    ElevatedButton(
                      onPressed: _loading ? null : _save,
                      child: _loading
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(
                                strokeWidth: 2, color: Colors.white,
                              ),
                            )
                          : Text(isSwahili ? 'Hifadhi Kikao' : 'Save Session'),
                    ),
                  ],
                ),
              ),
      ),
    );
  }
}

class _VisitFormSheet extends ConsumerStatefulWidget {
  final int sessionId;
  const _VisitFormSheet({required this.sessionId});

  @override
  ConsumerState<_VisitFormSheet> createState() => _VisitFormSheetState();
}

class _VisitFormSheetState extends ConsumerState<_VisitFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _nameCtrl = TextEditingController();
  final _locationCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _notesCtrl = TextEditingController();
  String _status = 'follow_up';
  DateTime? _followupDate;
  bool _saving = false;

  @override
  void dispose() {
    _nameCtrl.dispose();
    _locationCtrl.dispose();
    _phoneCtrl.dispose();
    _notesCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      await ref.read(apiClientProvider).post(
        '/field-marketing/sessions/${widget.sessionId}/visits',
        data: {
          'business_name': _nameCtrl.text.trim(),
          'location': _locationCtrl.text.trim(),
          'phone': _phoneCtrl.text.trim(),
          'status': _status,
          'notes': _notesCtrl.text.trim(),
          if (_followupDate != null)
            'next_followup_date':
                '${_followupDate!.year.toString().padLeft(4, '0')}-${_followupDate!.month.toString().padLeft(2, '0')}-${_followupDate!.day.toString().padLeft(2, '0')}',
        },
      );
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Save failed: $e')),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDark = ref.watch(isDarkModeProvider);
    return Container(
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1A1A1A) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Form(
          key: _formKey,
          child: ListView(
            padding: EdgeInsets.fromLTRB(20, 16, 20,
                MediaQuery.of(context).viewInsets.bottom + 28),
            children: [
              Text(
                isSwahili ? 'Ziara Mpya' : 'New Visit',
                textAlign: TextAlign.center,
                style: AppType.display(20),
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _nameCtrl,
                decoration:
                    _dec(isSwahili ? 'Jina la Biashara' : 'Business Name'),
                validator: (v) =>
                    (v ?? '').trim().isEmpty ? 'Required' : null,
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _locationCtrl,
                decoration: _dec(isSwahili ? 'Eneo' : 'Location'),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _phoneCtrl,
                keyboardType: TextInputType.phone,
                decoration: _dec(isSwahili ? 'Simu' : 'Phone'),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
      isExpanded: true,
                value: _status,
                decoration: _dec(isSwahili ? 'Hali' : 'Status'),
                items: const [
                  DropdownMenuItem(
                      value: 'interested', child: Text('Interested')),
                  DropdownMenuItem(
                      value: 'not_interested',
                      child: Text('Not Interested')),
                  DropdownMenuItem(
                      value: 'follow_up', child: Text('Follow Up')),
                  DropdownMenuItem(
                      value: 'converted', child: Text('Converted')),
                ],
                onChanged: (v) =>
                    setState(() => _status = v ?? _status),
              ),
              const SizedBox(height: 12),
              InkWell(
                onTap: () async {
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: _followupDate ?? DateTime.now(),
                    firstDate: DateTime.now(),
                    lastDate: DateTime.now().add(const Duration(days: 365)),
                  );
                  if (picked != null) {
                    setState(() => _followupDate = picked);
                  }
                },
                child: InputDecorator(
                  decoration: _dec(isSwahili
                      ? 'Tarehe ya Kufuatilia'
                      : 'Follow-up Date'),
                  child: Text(_followupDate == null
                      ? (isSwahili ? 'Hiari' : 'Optional')
                      : '${_followupDate!.year}-${_followupDate!.month.toString().padLeft(2, '0')}-${_followupDate!.day.toString().padLeft(2, '0')}'),
                ),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _notesCtrl,
                maxLines: 4,
                decoration: _dec(isSwahili ? 'Maelezo' : 'Notes'),
              ),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: _saving ? null : _save,
                child: _saving
                    ? const SizedBox(
                        width: 18, height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white,
                        ),
                      )
                    : Text(isSwahili ? 'Hifadhi Ziara' : 'Save Visit'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  final IconData icon;
  final String title;
  const _EmptyState({required this.icon, required this.title});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 36),
      child: Column(
        children: [
          Icon(icon, size: 48, color: Colors.black26),
          const SizedBox(height: 12),
          Text(title, style: const TextStyle(color: AppColors.textSecondary)),
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final String message;
  final VoidCallback onRetry;
  const _ErrorView({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, size: 54, color: AppColors.error),
            const SizedBox(height: 10),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 10),
            OutlinedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('Retry'),
            ),
          ],
        ),
      ),
    );
  }
}

InputDecoration _dec(String label) => InputDecoration(
      labelText: label,
      filled: true,
      fillColor: Colors.grey.withValues(alpha: 0.08),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide.none,
      ),
    );
