import 'package:dio/dio.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../../core/services/external_launcher_service.dart';
import '../../providers/settings_provider.dart';

/// Content Creator — tasks board for the content team.
///
/// Mirrors the portal feature at `content_creator.index`. Backed by
/// [ContentCreatorApiController]; the board groups tasks by `status`
/// (todo, in_progress, in_review, published). Supports search, status
/// filter chips, pull-to-refresh, and create/edit via bottom sheet.
class ContentCreatorScreen extends ConsumerStatefulWidget {
  const ContentCreatorScreen({super.key});

  @override
  ConsumerState<ContentCreatorScreen> createState() =>
      _ContentCreatorScreenState();
}

class _ContentCreatorScreenState extends ConsumerState<ContentCreatorScreen> {
  String _search = '';
  String? _statusFilter;
  int _refreshTick = 0;
  String _month =
      '${DateTime.now().year.toString().padLeft(4, '0')}-${DateTime.now().month.toString().padLeft(2, '0')}';

  String _tr(bool sw, String en, String swText) => sw ? swText : en;

  void _bump() => setState(() => _refreshTick++);

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final scaffoldKey = ref.watch(rootScaffoldKeyProvider);

    return DefaultTabController(
      length: 4,
      child: Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => scaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(
          _tr(isSwahili, 'Content Creator', 'Mtengenezaji wa Maudhui'),
          style: AppType.display(18),
        ),
        actions: [
          TextButton.icon(
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
          IconButton(
            tooltip: _tr(isSwahili, 'Set target', 'Weka lengo'),
            onPressed: () => _openTargetSheet(context),
            icon: const Icon(Icons.flag_rounded),
          ),
          IconButton(
            tooltip: _tr(isSwahili, 'Refresh', 'Onyesha tena'),
            onPressed: _bump,
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        icon: const Icon(Icons.add_task_rounded),
        label: Text(_tr(isSwahili, 'New Task', 'Kazi Mpya')),
        onPressed: () async {
          final saved = await _openTaskSheet(context);
          if (saved == true && mounted) _bump();
        },
      ),
      body: Column(
        children: [
          const SizedBox(height: 8),
          _CcStatsRow(
            key: ValueKey('st$_month$_refreshTick'),
            month: _month,
            sw: isSwahili,
          ),
          TabBar(
            isScrollable: true,
            tabAlignment: TabAlignment.start,
            tabs: [
              Tab(text: _tr(isSwahili, 'Content Calendar', 'Kalenda ya Maudhui')),
              Tab(text: _tr(isSwahili, 'Crew Workability', 'Mzigo wa Timu')),
              Tab(text: _tr(isSwahili, 'Task Board', 'Bodi ya Kazi')),
              Tab(text: _tr(isSwahili, 'Targets & Matrix', 'Malengo na Majukumu')),
            ],
          ),
          Expanded(
            child: TabBarView(
              children: [
                _CalendarTab(key: ValueKey('c$_refreshTick'), sw: isSwahili),
                _WorkabilityTab(
                  key: ValueKey('w$_month$_refreshTick'),
                  month: _month,
                  sw: isSwahili,
                ),
                _BoardView(
                  search: _search,
                  statusFilter: _statusFilter,
                  refreshTick: _refreshTick,
                  onSearchChanged: (s) => setState(() => _search = s),
                  onStatusChanged: (s) => setState(() => _statusFilter = s),
                  onTaskChanged: _bump,
                ),
                _TargetsTab(
                  key: ValueKey('t$_month$_refreshTick'),
                  month: _month,
                  sw: isSwahili,
                  onSetTarget: () async {
                    await _openTargetSheet(context);
                    if (mounted) _bump();
                  },
                ),
              ],
            ),
          ),
        ],
      ),
      ),
    );
  }

  Future<bool?> _openTaskSheet(BuildContext context,
      {Map<String, dynamic>? task}) {
    return showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.92,
        child: _TaskFormSheet(task: task),
      ),
    );
  }

  Future<void> _openTargetSheet(BuildContext context) async {
    final messenger = ScaffoldMessenger.of(context);
    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => const _TargetFormSheet(),
    );
    if (saved == true && mounted) {
      messenger.showSnackBar(
        SnackBar(
          content: Text(_tr(
              ref.read(isSwahiliProvider), 'Target saved.', 'Lengo limehifadhiwa.')),
        ),
      );
    }
  }
}

// ───────────────────────────────────── Providers ────────────────────────────

class _BoardArgs {
  final String search;
  final String? status;
  final int tick;
  const _BoardArgs(this.search, this.status, this.tick);

  @override
  bool operator ==(Object other) =>
      other is _BoardArgs &&
      other.search == search &&
      other.status == status &&
      other.tick == tick;

  @override
  int get hashCode => Object.hash(search, status, tick);
}

/// Loads `/content-creator` index payload (board + stats + crew).
final _ccIndexProvider =
    FutureProvider.family<Map<String, dynamic>, _BoardArgs>((ref, args) async {
  final res = await ref.read(apiClientProvider).get(
    '/content-creator',
    queryParameters: {
      if (args.search.trim().isNotEmpty) 'search': args.search.trim(),
      if (args.status != null) 'status': args.status,
    },
  );
  final data = res.data is Map ? Map<String, dynamic>.from(res.data as Map) : {};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : <String, dynamic>{};
});

/// Loads `/content-creator/reference-data` (statuses, platforms, priorities,
/// task_types, assignees, creators). Cached for the screen lifetime.
final _ccReferenceProvider = FutureProvider<Map<String, dynamic>>((ref) async {
  final res =
      await ref.read(apiClientProvider).get('/content-creator/reference-data');
  final data = res.data is Map ? Map<String, dynamic>.from(res.data as Map) : {};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : <String, dynamic>{};
});

// ───────────────────────────────────── Board view ───────────────────────────

class _BoardView extends ConsumerStatefulWidget {
  final String search;
  final String? statusFilter;
  final int refreshTick;
  final ValueChanged<String> onSearchChanged;
  final ValueChanged<String?> onStatusChanged;
  final VoidCallback onTaskChanged;

  const _BoardView({
    required this.search,
    required this.statusFilter,
    required this.refreshTick,
    required this.onSearchChanged,
    required this.onStatusChanged,
    required this.onTaskChanged,
  });

  @override
  ConsumerState<_BoardView> createState() => _BoardViewState();
}

class _BoardViewState extends ConsumerState<_BoardView> {
  final _searchCtrl = TextEditingController();

  static const _statusPalette = <String, Color>{
    'todo': AppColors.draft,
    'in_progress': AppColors.brandBlue,
    'in_review': AppColors.brandYellow,
    'published': AppColors.brandGreen,
    // Common fallback synonyms portals sometimes return.
    'draft': AppColors.draft,
    'approved': AppColors.brandGreen,
  };

  @override
  void initState() {
    super.initState();
    _searchCtrl.text = widget.search;
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  String _tr(bool sw, String en, String swText) => sw ? swText : en;

  String _labelize(String value) => value
      .split('_')
      .map((w) =>
          w.isEmpty ? w : '${w[0].toUpperCase()}${w.substring(1).toLowerCase()}')
      .join(' ');

  Color _statusColor(String status) =>
      _statusPalette[status] ?? AppColors.brandBlue;

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDark = ref.watch(isDarkModeProvider);

    final args = _BoardArgs(widget.search, widget.statusFilter, widget.refreshTick);
    final async = ref.watch(_ccIndexProvider(args));
    final refsAsync = ref.watch(_ccReferenceProvider);

    return RefreshIndicator(
      onRefresh: () async {
        ref.invalidate(_ccIndexProvider(args));
        ref.invalidate(_ccReferenceProvider);
        await ref.read(_ccIndexProvider(args).future);
      },
      child: async.when(
        loading: () => ListView(
          children: const [
            SizedBox(height: 200),
            Center(child: CircularProgressIndicator()),
          ],
        ),
        error: (err, _) => ListView(
          children: [
            SizedBox(
              height: 320,
              child: _ErrorView(
                message: err.toString(),
                onRetry: () => ref.invalidate(_ccIndexProvider(args)),
              ),
            ),
          ],
        ),
        data: (data) {
          final board = data['board'] is Map
              ? Map<String, dynamic>.from(data['board'] as Map)
              : const <String, dynamic>{};
          final crew = (data['crew'] as List?) ?? const [];

          // Pull statuses from reference data if available, else use the
          // keys present in the board map.
          final refStatuses = refsAsync.maybeWhen(
            data: (d) => (d['statuses'] as List?) ?? const [],
            orElse: () => const [],
          );
          final statuses = <Map<String, String>>[];
          if (refStatuses.isNotEmpty) {
            for (final s in refStatuses) {
              if (s is Map) {
                final value = s['value']?.toString() ?? '';
                final label = s['label']?.toString() ?? _labelize(value);
                if (value.isNotEmpty) {
                  statuses.add({'value': value, 'label': label});
                }
              }
            }
          } else {
            for (final key in board.keys) {
              statuses.add({'value': key, 'label': _labelize(key)});
            }
          }

          // Flatten tasks honoring the optional status filter and search.
          final allTasks = <Map<String, dynamic>>[];
          board.forEach((statusKey, list) {
            if (list is List) {
              for (final t in list) {
                if (t is Map) {
                  allTasks.add(Map<String, dynamic>.from(t));
                }
              }
            }
          });

          final lowerSearch = widget.search.trim().toLowerCase();
          final filtered = allTasks.where((t) {
            if (widget.statusFilter != null &&
                t['status']?.toString() != widget.statusFilter) {
              return false;
            }
            if (lowerSearch.isEmpty) return true;
            final fields = [
              t['title']?.toString() ?? '',
              t['assignee_name']?.toString() ?? '',
              t['status']?.toString() ?? '',
              t['platform']?.toString() ?? '',
              t['task_type']?.toString() ?? '',
            ];
            return fields
                .any((f) => f.toLowerCase().contains(lowerSearch));
          }).toList()
            ..sort((a, b) {
              // Overdue first, then by deadline asc, then by id desc.
              final aOverdue = a['is_overdue'] == true ? 0 : 1;
              final bOverdue = b['is_overdue'] == true ? 0 : 1;
              if (aOverdue != bOverdue) return aOverdue - bOverdue;
              final aDl = a['deadline']?.toString() ?? '';
              final bDl = b['deadline']?.toString() ?? '';
              if (aDl.isEmpty && bDl.isEmpty) return 0;
              if (aDl.isEmpty) return 1;
              if (bDl.isEmpty) return -1;
              return aDl.compareTo(bDl);
            });

          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 16),
            children: [
              if (crew.isNotEmpty) ...[
                _CrewSection(
                  crew: crew,
                  isSwahili: isSwahili,
                  onChanged: widget.onTaskChanged,
                ),
              ],
              const SizedBox(height: 16),
              TextField(
                controller: _searchCtrl,
                decoration: InputDecoration(
                  hintText: _tr(
                    isSwahili,
                    'Search title, assignee, status…',
                    'Tafuta kichwa, mhusika, hali…',
                  ),
                  prefixIcon: const Icon(Icons.search_rounded),
                  suffixIcon: _searchCtrl.text.isEmpty
                      ? null
                      : IconButton(
                          icon: const Icon(Icons.clear),
                          onPressed: () {
                            _searchCtrl.clear();
                            widget.onSearchChanged('');
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
                onSubmitted: widget.onSearchChanged,
                onChanged: (_) => setState(() {}),
              ),
              const SizedBox(height: 12),
              SizedBox(
                height: 36,
                child: ListView(
                  scrollDirection: Axis.horizontal,
                  children: [
                    _statusChip(
                      value: null,
                      label: _tr(isSwahili, 'All', 'Zote'),
                      count: allTasks.length,
                      color: AppColors.brandBlue,
                    ),
                    for (final s in statuses)
                      _statusChip(
                        value: s['value'],
                        label: s['label']!,
                        count: ((board[s['value']] as List?)?.length) ?? 0,
                        color: _statusColor(s['value']!),
                      ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              if (filtered.isEmpty)
                _EmptyState(
                  icon: Icons.task_alt_rounded,
                  title: _tr(isSwahili, 'No tasks.', 'Hakuna kazi.'),
                )
              else
                ...filtered.map(
                  (t) => _TaskCard(
                    task: t,
                    isSwahili: isSwahili,
                    statusColor: _statusColor(t['status']?.toString() ?? ''),
                    onChanged: widget.onTaskChanged,
                  ),
                ),
            ],
          );
        },
      ),
    );
  }

  Widget _statusChip({
    required String? value,
    required String label,
    required int count,
    required Color color,
  }) {
    final active = widget.statusFilter == value;
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ChoiceChip(
        label: Text(
          '$label ($count)',
          style: TextStyle(
            color: active ? Colors.white : color,
            fontWeight: FontWeight.w700,
          ),
        ),
        selected: active,
        selectedColor: color,
        backgroundColor: color.withValues(alpha: 0.10),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
          side: BorderSide.none,
        ),
        onSelected: (_) => widget.onStatusChanged(active ? null : value),
      ),
    );
  }
}

// ───────────────────────────── Web tabs (calendar, workability, targets) ────

String _ccErr(Object e) {
  try {
    final data = (e as dynamic).response?.data;
    if (data is Map && data['message'] is String) return data['message'];
  } catch (_) {}
  return e.toString();
}

int _ccInt(dynamic v) => v is num ? v.toInt() : int.tryParse('$v') ?? 0;

List<Map<String, dynamic>> _ccMaps(dynamic v) => (v as List? ?? const [])
    .whereType<Map>()
    .map((e) => Map<String, dynamic>.from(e))
    .toList();

/// Loads one content-creator endpoint and hands its `data` to [builder].
class _CcRemote extends ConsumerStatefulWidget {
  final String path;
  final Map<String, dynamic> query;
  final Widget Function(BuildContext context, dynamic data) builder;

  const _CcRemote({
    super.key,
    required this.path,
    required this.query,
    required this.builder,
  });

  @override
  ConsumerState<_CcRemote> createState() => _CcRemoteState();
}

class _CcRemoteState extends ConsumerState<_CcRemote> {
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
              message: _ccErr(snap.error!),
              onRetry: () => setState(() => _future = _load()),
            );
          }
          return widget.builder(context, snap.data);
        },
      ),
    );
  }
}

/// The web's five stat tiles (posts, videos, designs, on-time rate, overdue).
class _CcStatsRow extends StatelessWidget {
  final String month;
  final bool sw;

  const _CcStatsRow({super.key, required this.month, required this.sw});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 112,
      child: _CcRemote(
        path: '/content-creator/stats',
        query: {'month': month},
        builder: (context, data) {
          final s = data is Map ? Map<String, dynamic>.from(data) : const <String, dynamic>{};
          final published = _ccInt(s['published']);
          final videos = _ccInt(s['videos']);
          final designs = _ccInt(s['designs']);
          final rate = _ccInt(s['on_time_rate']);
          final overdue = _ccInt(s['overdue']);
          final total = _ccInt(s['total']);

          Widget tile(String label, String value, Color color, IconData icon, int pct) =>
              Container(
                width: 132,
                margin: const EdgeInsets.only(right: 10),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(14),
                  border: Border(top: BorderSide(color: color, width: 3)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(icon, color: color, size: 18),
                    const SizedBox(height: 4),
                    Text(value,
                        style: AppType.display(22,
                            weight: FontWeight.w800, color: color)),
                    Text(label,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontSize: 11)),
                    const Spacer(),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(4),
                      child: LinearProgressIndicator(
                        value: pct.clamp(0, 100) / 100,
                        minHeight: 4,
                        color: color,
                        backgroundColor: color.withValues(alpha: 0.15),
                      ),
                    ),
                  ],
                ),
              );

          return ListView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.fromLTRB(16, 4, 6, 4),
            children: [
              tile(sw ? 'Machapisho' : 'Posts Published', '$published',
                  AppColors.brandBlue, Icons.campaign_outlined, published * 5),
              tile(sw ? 'Video Zilizopigwa' : 'Videos Shot', '$videos',
                  Colors.deepPurple, Icons.videocam_outlined, videos * 10),
              tile(sw ? 'Miundo' : 'Designs Made', '$designs', Colors.lightBlue,
                  Icons.palette_outlined, designs * 10),
              tile(sw ? 'Kwa Wakati' : 'On-Time Rate', '$rate%',
                  AppColors.brandGreen, Icons.check_circle_outline, rate),
              tile(sw ? 'Zilizochelewa' : 'Overdue Tasks', '$overdue',
                  AppColors.error, Icons.warning_amber_rounded,
                  total > 0 ? (overdue * 100 / total).round() : 0),
            ],
          );
        },
      ),
    );
  }
}

Color _ccTypeColor(String? type) => switch (type) {
      'video_shoot' => Colors.deepPurple,
      'post_publish' => AppColors.brandBlue,
      'design_task' => Colors.teal,
      'review_approval' => AppColors.brandGreen,
      'day_off' => AppColors.error,
      _ => Colors.blueGrey,
    };

/// Content Calendar tab: a week grid, a row per crew member.
class _CalendarTab extends ConsumerStatefulWidget {
  final bool sw;
  const _CalendarTab({super.key, required this.sw});

  @override
  ConsumerState<_CalendarTab> createState() => _CalendarTabState();
}

class _CalendarTabState extends ConsumerState<_CalendarTab> {
  DateTime _week = DateTime.now();

  static String _fmt(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  static const _dow = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

  @override
  Widget build(BuildContext context) {
    final sw = widget.sw;
    final isDark = ref.watch(isDarkModeProvider);
    final today = _fmt(DateTime.now());

    return _CcRemote(
      key: ValueKey(_fmt(_week)),
      path: '/content-creator/calendar',
      query: {'week': _fmt(_week)},
      builder: (context, data) {
        final d = data is Map ? Map<String, dynamic>.from(data) : const <String, dynamic>{};
        final days = (d['days'] as List? ?? const []).map((e) => '$e').toList();
        final members = _ccMaps(d['members']);

        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Row(
              children: [
                IconButton(
                  tooltip: sw ? 'Wiki iliyopita' : 'Previous week',
                  icon: const Icon(Icons.chevron_left_rounded),
                  onPressed: () => setState(
                      () => _week = _week.subtract(const Duration(days: 7))),
                ),
                Expanded(
                  child: Text(
                    '${d['start'] ?? ''} – ${d['end'] ?? ''}',
                    textAlign: TextAlign.center,
                    style: AppType.display(14, weight: FontWeight.w700),
                  ),
                ),
                IconButton(
                  tooltip: sw ? 'Wiki ijayo' : 'Next week',
                  icon: const Icon(Icons.chevron_right_rounded),
                  onPressed: () =>
                      setState(() => _week = _week.add(const Duration(days: 7))),
                ),
                TextButton(
                  onPressed: () => setState(() => _week = DateTime.now()),
                  child: Text(sw ? 'Leo' : 'Today'),
                ),
              ],
            ),
            Wrap(
              spacing: 6,
              runSpacing: 6,
              children: [
                for (final e in {
                  'video_shoot': sw ? 'Video' : 'Video Shoot',
                  'post_publish': sw ? 'Chapisho' : 'Post/Publish',
                  'design_task': sw ? 'Muundo' : 'Design',
                  'review_approval': sw ? 'Mapitio' : 'Review',
                  'day_off': sw ? 'Siku ya mapumziko' : 'Day Off',
                }.entries)
                  Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: _ccTypeColor(e.key).withValues(alpha: 0.14),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(e.value,
                        style: TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w700,
                            color: _ccTypeColor(e.key))),
                  ),
              ],
            ),
            const SizedBox(height: 12),
            if (members.isEmpty)
              _EmptyState(
                icon: Icons.groups_outlined,
                title: sw
                    ? 'Hakuna wanatimu. Ongeza timu ili kujaza kalenda.'
                    : 'No crew members. Add crew to populate the calendar.',
              )
            else
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Column(
                  children: [
                    Row(
                      children: [
                        _cell(isDark, 96, Text(sw ? 'Mwanatimu' : 'Member',
                            style: const TextStyle(
                                fontWeight: FontWeight.w800, fontSize: 11)),
                            header: true),
                        for (var i = 0; i < days.length; i++)
                          _cell(
                            isDark,
                            104,
                            Column(
                              children: [
                                Text(_dow[i],
                                    style: const TextStyle(
                                        fontWeight: FontWeight.w800,
                                        fontSize: 11)),
                                Text(days[i].substring(8),
                                    style: const TextStyle(fontSize: 11)),
                              ],
                            ),
                            header: true,
                            highlight: days[i] == today,
                          ),
                      ],
                    ),
                    for (final m in members)
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _cell(
                            isDark,
                            96,
                            Text('${m['name'] ?? '-'}',
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(
                                    fontWeight: FontWeight.w700, fontSize: 11)),
                          ),
                          for (final day in days)
                            _cell(
                              isDark,
                              104,
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  for (final it in _ccMaps(
                                      (m['days'] is Map ? m['days'][day] : null)))
                                    Container(
                                      width: double.infinity,
                                      margin: const EdgeInsets.only(bottom: 3),
                                      padding: const EdgeInsets.symmetric(
                                          horizontal: 5, vertical: 2),
                                      decoration: BoxDecoration(
                                        color: _ccTypeColor(
                                                it['task_type']?.toString())
                                            .withValues(alpha: 0.16),
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: Text('${it['title']}',
                                          maxLines: 2,
                                          overflow: TextOverflow.ellipsis,
                                          style: TextStyle(
                                              fontSize: 10,
                                              fontWeight: FontWeight.w600,
                                              color: _ccTypeColor(
                                                  it['task_type']?.toString()))),
                                    ),
                                ],
                              ),
                              highlight: day == today,
                            ),
                        ],
                      ),
                  ],
                ),
              ),
          ],
        );
      },
    );
  }

  Widget _cell(bool isDark, double w, Widget child,
      {bool header = false, bool highlight = false}) {
    final line = isDark
        ? Colors.white.withValues(alpha: 0.12)
        : Colors.black.withValues(alpha: 0.10);
    return Container(
      width: w,
      constraints: BoxConstraints(minHeight: header ? 44 : 56),
      padding: const EdgeInsets.all(5),
      alignment: header ? Alignment.center : Alignment.topLeft,
      decoration: BoxDecoration(
        color: highlight
            ? AppColors.brandBlue.withValues(alpha: 0.10)
            : (header
                ? (isDark
                    ? Colors.white.withValues(alpha: 0.05)
                    : Colors.black.withValues(alpha: 0.03))
                : null),
        border: Border.all(color: line, width: 0.5),
      ),
      child: child,
    );
  }
}

/// Crew Workability tab.
class _WorkabilityTab extends StatelessWidget {
  final String month;
  final bool sw;

  const _WorkabilityTab({super.key, required this.month, required this.sw});

  static const _dots = {
    'online': AppColors.brandGreen,
    'busy': Colors.orange,
    'away': Colors.amber,
    'offline': Colors.grey,
  };

  @override
  Widget build(BuildContext context) {
    return _CcRemote(
      path: '/content-creator/workability',
      query: {'month': month},
      builder: (context, data) {
        final rows = _ccMaps(data);
        if (rows.isEmpty) {
          return ListView(children: [
            const SizedBox(height: 40),
            _EmptyState(
              icon: Icons.groups_outlined,
              title: sw
                  ? 'Hakuna data ya timu kwa kipindi hiki.'
                  : 'No crew workability data for this period.',
            ),
          ]);
        }
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            for (final w in rows)
              Card(
                margin: const EdgeInsets.only(bottom: 12),
                child: Padding(
                  padding: const EdgeInsets.all(14),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          CircleAvatar(
                            backgroundColor: AppColors.brandBlue,
                            child: Text(
                              '${w['name'] ?? 'U'}'
                                  .padRight(2)
                                  .substring(0, 2)
                                  .toUpperCase(),
                              style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w700,
                                  fontSize: 13),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text('${w['name'] ?? 'Unknown'}',
                                    style: AppType.display(14,
                                        weight: FontWeight.w700)),
                                Text('${w['role'] ?? ''}',
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                        fontSize: 11,
                                        color: AppColors.textSecondary)),
                              ],
                            ),
                          ),
                          Icon(Icons.circle,
                              size: 12,
                              color: _dots['${w['online_status']}'] ??
                                  Colors.grey),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Text(
                          '${sw ? 'Mzigo wa kazi' : 'Workload'}: ${_ccInt(w['workload'])}%',
                          style: const TextStyle(
                              fontSize: 12, fontWeight: FontWeight.w600)),
                      const SizedBox(height: 4),
                      ClipRRect(
                        borderRadius: BorderRadius.circular(6),
                        child: LinearProgressIndicator(
                          value: _ccInt(w['workload']) / 100,
                          minHeight: 8,
                          color: AppColors.brandBlue,
                          backgroundColor:
                              AppColors.brandBlue.withValues(alpha: 0.15),
                        ),
                      ),
                      const SizedBox(height: 10),
                      _line(Icons.check_circle, AppColors.brandGreen,
                          sw ? 'Imekamilika' : 'Done', _ccInt(w['done'])),
                      _line(Icons.autorenew_rounded, AppColors.brandBlue,
                          sw ? 'Inaendelea' : 'In Progress',
                          _ccInt(w['in_progress'])),
                      _line(Icons.checklist_rounded, Colors.blueGrey,
                          sw ? 'Jumla ya kazi' : 'Total Tasks',
                          _ccInt(w['total'])),
                      if (_ccInt(w['overdue']) > 0)
                        _line(Icons.warning_amber_rounded, AppColors.error,
                            sw ? 'Zilizochelewa' : 'Overdue',
                            _ccInt(w['overdue']),
                            bold: true),
                      if ((w['skills'] as List? ?? const []).isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 6,
                          runSpacing: 6,
                          children: [
                            for (final s in (w['skills'] as List))
                              Container(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 8, vertical: 2),
                                decoration: BoxDecoration(
                                  color: AppColors.brandBlue
                                      .withValues(alpha: 0.12),
                                  borderRadius: BorderRadius.circular(20),
                                ),
                                child: Text('$s',
                                    style: const TextStyle(fontSize: 10)),
                              ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
              ),
          ],
        );
      },
    );
  }

  Widget _line(IconData icon, Color color, String label, int value,
      {bool bold = false}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(
        children: [
          Icon(icon, size: 15, color: color),
          const SizedBox(width: 6),
          Expanded(child: Text(label, style: const TextStyle(fontSize: 12))),
          Text('$value',
              style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w800,
                  color: bold ? color : null)),
        ],
      ),
    );
  }
}

/// Targets & Matrix tab.
class _TargetsTab extends StatelessWidget {
  final String month;
  final bool sw;
  final VoidCallback onSetTarget;

  const _TargetsTab({
    super.key,
    required this.month,
    required this.sw,
    required this.onSetTarget,
  });

  static const _platformColors = {
    'instagram': Color(0xFFE1306C),
    'tiktok': Color(0xFF555555),
    'facebook': Color(0xFF1877F2),
    'linkedin': Color(0xFF0A66C2),
    'youtube': Color(0xFFFF0000),
  };

  @override
  Widget build(BuildContext context) {
    return _CcRemote(
      path: '/content-creator/targets',
      query: {'month': month},
      builder: (context, data) {
        final d = data is Map ? Map<String, dynamic>.from(data) : const <String, dynamic>{};
        final platforms = _ccMaps(d['platforms']);
        final matrix = _ccMaps(d['matrix']);

        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    '${sw ? 'Malengo ya Machapisho' : 'Post Targets'} — $month',
                    style: AppType.display(15, weight: FontWeight.w700),
                  ),
                ),
                FilledButton.tonalIcon(
                  onPressed: onSetTarget,
                  icon: const Icon(Icons.edit_outlined, size: 16),
                  label: Text(sw ? 'Weka Lengo' : 'Set Target'),
                ),
              ],
            ),
            const SizedBox(height: 10),
            for (final p in platforms)
              Card(
                margin: const EdgeInsets.only(bottom: 8),
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              '${p['platform']}'.replaceRange(
                                  0, 1, '${p['platform']}'[0].toUpperCase()),
                              style: const TextStyle(fontWeight: FontWeight.w700),
                            ),
                          ),
                          Text(
                              '${_ccInt(p['current'])} / ${_ccInt(p['target']) == 0 ? '—' : _ccInt(p['target'])}'),
                          const SizedBox(width: 10),
                          Text(
                            '${_ccInt(p['percent'])}%',
                            style: TextStyle(
                              fontWeight: FontWeight.w800,
                              color: _ccInt(p['percent']) >= 100
                                  ? AppColors.brandGreen
                                  : (_ccInt(p['percent']) >= 60
                                      ? Colors.orange
                                      : AppColors.error),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      ClipRRect(
                        borderRadius: BorderRadius.circular(6),
                        child: LinearProgressIndicator(
                          value: _ccInt(p['percent']) / 100,
                          minHeight: 8,
                          color: _platformColors['${p['platform']}'] ??
                              AppColors.brandBlue,
                          backgroundColor:
                              (_platformColors['${p['platform']}'] ??
                                      AppColors.brandBlue)
                                  .withValues(alpha: 0.15),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            const SizedBox(height: 16),
            Text(sw ? 'Jedwali la Majukumu' : 'Responsibility Matrix',
                style: AppType.display(15, weight: FontWeight.w700)),
            const SizedBox(height: 8),
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: DataTable(
                columnSpacing: 18,
                headingRowHeight: 40,
                dataRowMinHeight: 40,
                dataRowMaxHeight: 48,
                columns: [
                  DataColumn(label: Text(sw ? 'Aina ya Kazi' : 'Task Type')),
                  const DataColumn(label: Text('PRIMARY')),
                  const DataColumn(label: Text('SUPPORT')),
                  const DataColumn(label: Text('REVIEW')),
                  const DataColumn(label: Text('OVERSIGHT')),
                ],
                rows: [
                  for (final r in matrix)
                    DataRow(cells: [
                      DataCell(Text('${r['task_type']}',
                          style:
                              const TextStyle(fontWeight: FontWeight.w700))),
                      DataCell(Text('${r['primary']}')),
                      DataCell(Text('${r['support']}')),
                      DataCell(Text('${r['review']}')),
                      DataCell(Text('${r['oversight']}')),
                    ]),
                ],
              ),
            ),
          ],
        );
      },
    );
  }
}

class _TaskCard extends ConsumerWidget {
  final Map<String, dynamic> task;
  final bool isSwahili;
  final Color statusColor;
  final VoidCallback onChanged;

  const _TaskCard({
    required this.task,
    required this.isSwahili,
    required this.statusColor,
    required this.onChanged,
  });

  String _initials(String name) {
    final parts = name.trim().split(RegExp(r'\s+')).where((p) => p.isNotEmpty);
    if (parts.isEmpty) return '?';
    if (parts.length == 1) {
      return parts.first.substring(0, 1).toUpperCase();
    }
    return (parts.first.substring(0, 1) + parts.last.substring(0, 1))
        .toUpperCase();
  }

  String _labelize(String value) => value
      .split('_')
      .map((w) =>
          w.isEmpty ? w : '${w[0].toUpperCase()}${w.substring(1).toLowerCase()}')
      .join(' ');

  Color _priorityColor(String priority) => switch (priority) {
        'high' => AppColors.error,
        'medium' => AppColors.brandYellow,
        'low' => AppColors.brandGreen,
        _ => AppColors.textSecondary,
      };

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final status = task['status']?.toString() ?? '';
    final priority = task['priority']?.toString() ?? '';
    final platform = task['platform']?.toString() ?? '';
    final taskType = task['task_type']?.toString() ?? '';
    final assignee = task['assignee_name']?.toString() ?? '';
    final deadline = task['deadline']?.toString();
    final overdue = task['is_overdue'] == true;
    final attachments = (task['attachments'] as List?) ?? const [];

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () => _openDetail(context),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      task['title']?.toString() ?? '-',
                      style: AppType.display(15, weight: FontWeight.w700),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: statusColor.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Text(
                      _labelize(status),
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: statusColor,
                      ),
                    ),
                  ),
                ],
              ),
              if ((task['description'] ?? '').toString().isNotEmpty)
                Padding(
                  padding: const EdgeInsets.only(top: 6),
                  child: Text(
                    task['description'].toString(),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style:
                        const TextStyle(color: AppColors.textSecondary),
                  ),
                ),
              const SizedBox(height: 10),
              Row(
                children: [
                  CircleAvatar(
                    radius: 14,
                    backgroundColor: AppColors.brandBlue.withValues(alpha: 0.15),
                    child: Text(
                      assignee.isEmpty ? '?' : _initials(assignee),
                      style: const TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: AppColors.brandBlue,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      assignee.isEmpty
                          ? (isSwahili ? 'Hajachaguliwa' : 'Unassigned')
                          : assignee,
                      style: const TextStyle(
                        fontSize: 12,
                        color: AppColors.textSecondary,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                  if (deadline != null && deadline.isNotEmpty) ...[
                    Icon(
                      Icons.event_outlined,
                      size: 14,
                      color: overdue
                          ? AppColors.error
                          : AppColors.textSecondary,
                    ),
                    const SizedBox(width: 4),
                    Text(
                      deadline,
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight:
                            overdue ? FontWeight.w700 : FontWeight.w500,
                        color: overdue
                            ? AppColors.error
                            : AppColors.textSecondary,
                      ),
                    ),
                  ],
                ],
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 6,
                runSpacing: 4,
                children: [
                  if (priority.isNotEmpty)
                    _miniTag(
                      label: _labelize(priority),
                      color: _priorityColor(priority),
                      icon: Icons.flag_outlined,
                    ),
                  if (platform.isNotEmpty)
                    _miniTag(
                      label: _labelize(platform),
                      color: AppColors.brandBlue,
                      icon: Icons.public_rounded,
                    ),
                  if (taskType.isNotEmpty)
                    _miniTag(
                      label: _labelize(taskType),
                      color: AppColors.textSecondary,
                      icon: Icons.layers_outlined,
                    ),
                  if (attachments.isNotEmpty)
                    _miniTag(
                      label: '${attachments.length}',
                      color: AppColors.brandYellow,
                      icon: Icons.attach_file_rounded,
                    ),
                  if (overdue)
                    _miniTag(
                      label: isSwahili ? 'Imechelewa' : 'Overdue',
                      color: AppColors.error,
                      icon: Icons.warning_amber_rounded,
                    ),
                ],
              ),
              const SizedBox(height: 4),
              Row(
                children: [
                  if (status != 'published')
                    TextButton.icon(
                      onPressed: () => _approve(context, ref),
                      icon: const Icon(Icons.check_circle_outline, size: 16),
                      label: Text(isSwahili ? 'Idhinisha' : 'Approve'),
                      style: TextButton.styleFrom(
                        foregroundColor: AppColors.brandGreen,
                        minimumSize: const Size(0, 36),
                      ),
                    ),
                  const Spacer(),
                  IconButton(
                    tooltip: isSwahili ? 'Hariri' : 'Edit',
                    onPressed: () => _edit(context),
                    icon: const Icon(Icons.edit_outlined),
                  ),
                  IconButton(
                    tooltip: isSwahili ? 'Futa' : 'Delete',
                    onPressed: () => _delete(context, ref),
                    icon: const Icon(Icons.delete_outline,
                        color: AppColors.error),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _miniTag({
    required String label,
    required Color color,
    required IconData icon,
  }) =>
      Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.10),
          borderRadius: BorderRadius.circular(14),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 11, color: color),
            const SizedBox(width: 4),
            Text(label,
                style: TextStyle(
                    fontSize: 11, fontWeight: FontWeight.w700, color: color)),
          ],
        ),
      );

  Future<void> _openDetail(BuildContext context) async {
    final rawId = task['id'];
    final id = rawId is int ? rawId : int.tryParse('${rawId ?? ''}');
    if (id == null) return;
    await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.92,
        child: _TaskDetailSheet(
          taskId: id,
          summary: task,
          isSwahili: isSwahili,
          onChanged: onChanged,
        ),
      ),
    );
  }

  Future<void> _edit(BuildContext context) async {
    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.92,
        child: _TaskFormSheet(task: task),
      ),
    );
    if (saved == true) onChanged();
  }

  Future<void> _delete(BuildContext context, WidgetRef ref) async {
    final id = task['id'];
    if (id == null) return;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Futa kazi?' : 'Delete task?'),
        content: Text('${task['title'] ?? '-'}'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: Text(isSwahili ? 'Ghairi' : 'Cancel')),
          TextButton(
              onPressed: () => Navigator.pop(ctx, true),
              child: Text(isSwahili ? 'Futa' : 'Delete')),
        ],
      ),
    );
    if (confirmed != true) return;
    try {
      await ref.read(apiClientProvider).delete('/content-creator/tasks/$id');
      onChanged();
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('${isSwahili ? 'Imeshindwa' : 'Failed'}: $e')));
      }
    }
  }

  Future<void> _approve(BuildContext context, WidgetRef ref) async {
    final id = task['id'];
    if (id == null) return;
    try {
      await ref
          .read(apiClientProvider)
          .post('/content-creator/tasks/$id/approve');
      onChanged();
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content:
                Text(isSwahili ? 'Imeidhinishwa.' : 'Approved & published.'),
          ),
        );
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('${isSwahili ? 'Imeshindwa' : 'Failed'}: $e')));
      }
    }
  }
}

// ───────────────────────────────────── Form sheet ───────────────────────────

class _TaskFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? task;
  const _TaskFormSheet({this.task});

  @override
  ConsumerState<_TaskFormSheet> createState() => _TaskFormSheetState();
}

class _TaskFormSheetState extends ConsumerState<_TaskFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final _titleCtrl =
      TextEditingController(text: widget.task?['title']?.toString() ?? '');
  late final _descCtrl = TextEditingController(
      text: widget.task?['description']?.toString() ?? '');
  late final _instructionsCtrl = TextEditingController(
      text: widget.task?['instructions']?.toString() ?? '');

  String _priority = 'medium';
  String _platform = 'instagram';
  String _taskType = 'post_publish';
  String? _status;
  int? _assignedTo;
  DateTime? _deadline;

  bool _saving = false;
  bool _refsLoading = true;
  List<Map<String, dynamic>> _statuses = const [];
  List<Map<String, dynamic>> _platforms = const [];
  List<Map<String, dynamic>> _taskTypes = const [];
  List<Map<String, dynamic>> _priorities = const [];
  List<Map<String, dynamic>> _assignees = const [];

  static DateTime? _parseDate(String? s) {
    if (s == null || s.isEmpty) return null;
    return DateTime.tryParse(s);
  }

  String _fmt(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  @override
  void initState() {
    super.initState();
    final t = widget.task;
    if (t != null) {
      _priority = t['priority']?.toString() ?? _priority;
      _platform = t['platform']?.toString() ?? _platform;
      _taskType = t['task_type']?.toString() ?? _taskType;
      _status = t['status']?.toString();
      _assignedTo = t['assigned_to'] is int
          ? t['assigned_to'] as int
          : int.tryParse(t['assigned_to']?.toString() ?? '');
      _deadline = _parseDate(t['deadline']?.toString());
    }
    _loadRefs();
  }

  Future<void> _loadRefs() async {
    try {
      final res = await ref
          .read(apiClientProvider)
          .get('/content-creator/reference-data');
      final d =
          (res.data is Map ? res.data['data'] : null) as Map<String, dynamic>?;
      if (d == null) return;
      List<Map<String, dynamic>> coerce(dynamic raw) =>
          ((raw as List?) ?? const [])
              .whereType<Map>()
              .map((e) => Map<String, dynamic>.from(e))
              .toList();
      setState(() {
        _statuses = coerce(d['statuses']);
        _platforms = coerce(d['platforms']);
        _taskTypes = coerce(d['task_types']);
        _priorities = coerce(d['priorities']);
        _assignees = coerce(d['assignees']);
      });
    } catch (_) {
      // swallow; the dropdowns will simply show seeded defaults.
    } finally {
      if (mounted) setState(() => _refsLoading = false);
    }
  }

  @override
  void dispose() {
    _titleCtrl.dispose();
    _descCtrl.dispose();
    _instructionsCtrl.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final payload = <String, dynamic>{
        'title': _titleCtrl.text.trim(),
        'description': _descCtrl.text.trim(),
        'instructions': _instructionsCtrl.text.trim(),
        'priority': _priority,
        'platform': _platform,
        'task_type': _taskType,
        if (_assignedTo != null) 'assigned_to': _assignedTo,
        if (_deadline != null) 'deadline': _fmt(_deadline!),
      };
      final id = widget.task?['id'];
      if (id != null) {
        await api.put('/content-creator/tasks/$id', data: payload);
      } else {
        await api.post('/content-creator/tasks', data: payload);
      }
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text('Save failed: $e')));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDark = ref.watch(isDarkModeProvider);

    final priorityItems = _priorities.isNotEmpty
        ? _priorities
        : const [
            {'value': 'high', 'label': 'High'},
            {'value': 'medium', 'label': 'Medium'},
            {'value': 'low', 'label': 'Low'},
          ];
    final platformItems = _platforms.isNotEmpty
        ? _platforms
        : const [
            {'value': 'instagram', 'label': 'Instagram'},
            {'value': 'tiktok', 'label': 'Tiktok'},
            {'value': 'facebook', 'label': 'Facebook'},
            {'value': 'linkedin', 'label': 'Linkedin'},
            {'value': 'youtube', 'label': 'Youtube'},
            {'value': 'general', 'label': 'General'},
          ];
    final typeItems = _taskTypes.isNotEmpty
        ? _taskTypes
        : const [
            {'value': 'video_shoot', 'label': 'Video Shoot'},
            {'value': 'post_publish', 'label': 'Post Publish'},
            {'value': 'design_task', 'label': 'Design Task'},
            {'value': 'review_approval', 'label': 'Review Approval'},
            {'value': 'other', 'label': 'Other'},
          ];

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
                  padding: EdgeInsets.fromLTRB(
                    20,
                    16,
                    20,
                    MediaQuery.of(context).viewInsets.bottom + 28,
                  ),
                  children: [
                    Text(
                      widget.task == null
                          ? (isSwahili ? 'Kazi Mpya' : 'New Task')
                          : (isSwahili ? 'Hariri Kazi' : 'Edit Task'),
                      textAlign: TextAlign.center,
                      style: AppType.display(20),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _titleCtrl,
                      decoration: _dec(isSwahili ? 'Kichwa' : 'Title'),
                      validator: (v) =>
                          (v ?? '').trim().isEmpty ? 'Required' : null,
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _descCtrl,
                      maxLines: 3,
                      decoration: _dec(isSwahili ? 'Maelezo' : 'Brief'),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<int?>(
      isExpanded: true,
                      value: _assignedTo,
                      decoration:
                          _dec(isSwahili ? 'Mhusika' : 'Assignee (optional)'),
                      items: [
                        const DropdownMenuItem<int?>(
                            value: null, child: Text('—')),
                        ..._assignees.map(
                          (u) => DropdownMenuItem<int?>(
                            value: u['id'] is int
                                ? u['id'] as int
                                : int.tryParse(u['id']?.toString() ?? ''),
                            child: Text('${u['name'] ?? '-'}'),
                          ),
                        ),
                      ],
                      onChanged: (v) => setState(() => _assignedTo = v),
                    ),
                    const SizedBox(height: 12),
                    InkWell(
                      onTap: () async {
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: _deadline ?? DateTime.now(),
                          firstDate: DateTime(2020),
                          lastDate: DateTime.now()
                              .add(const Duration(days: 365 * 2)),
                        );
                        if (picked != null) {
                          setState(() => _deadline = picked);
                        }
                      },
                      child: InputDecorator(
                        decoration:
                            _dec(isSwahili ? 'Tarehe ya mwisho' : 'Due date'),
                        child: Row(
                          children: [
                            Expanded(
                              child: Text(
                                _deadline == null ? '—' : _fmt(_deadline!),
                              ),
                            ),
                            if (_deadline != null)
                              IconButton(
                                icon: const Icon(Icons.clear, size: 18),
                                onPressed: () =>
                                    setState(() => _deadline = null),
                              ),
                            const Icon(Icons.event_outlined, size: 18),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
      isExpanded: true,
                      value: _priority,
                      decoration: _dec(isSwahili ? 'Kipaumbele' : 'Priority'),
                      items: priorityItems
                          .map((p) => DropdownMenuItem<String>(
                                value: p['value']?.toString(),
                                child: Text('${p['label'] ?? '-'}'),
                              ))
                          .toList(),
                      onChanged: (v) =>
                          setState(() => _priority = v ?? _priority),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
      isExpanded: true,
                      value: _platform,
                      decoration: _dec(isSwahili ? 'Jukwaa' : 'Platform'),
                      items: platformItems
                          .map((p) => DropdownMenuItem<String>(
                                value: p['value']?.toString(),
                                child: Text('${p['label'] ?? '-'}'),
                              ))
                          .toList(),
                      onChanged: (v) =>
                          setState(() => _platform = v ?? _platform),
                    ),
                    const SizedBox(height: 12),
                    DropdownButtonFormField<String>(
      isExpanded: true,
                      value: _taskType,
                      decoration:
                          _dec(isSwahili ? 'Aina ya kazi' : 'Task type'),
                      items: typeItems
                          .map((t) => DropdownMenuItem<String>(
                                value: t['value']?.toString(),
                                child: Text('${t['label'] ?? '-'}'),
                              ))
                          .toList(),
                      onChanged: (v) =>
                          setState(() => _taskType = v ?? _taskType),
                    ),
                    if (widget.task != null && _statuses.isNotEmpty) ...[
                      const SizedBox(height: 12),
                      DropdownButtonFormField<String>(
      isExpanded: true,
                        value: _status,
                        decoration: _dec(isSwahili ? 'Hali' : 'Status'),
                        items: _statuses
                            .map((s) => DropdownMenuItem<String>(
                                  value: s['value']?.toString(),
                                  child: Text('${s['label'] ?? '-'}'),
                                ))
                            .toList(),
                        onChanged: (v) => setState(() => _status = v),
                      ),
                    ],
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _instructionsCtrl,
                      maxLines: 4,
                      decoration:
                          _dec(isSwahili ? 'Maagizo' : 'Instructions'),
                    ),
                    const SizedBox(height: 20),
                    ElevatedButton(
                      onPressed: _saving ? null : _save,
                      child: _saving
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : Text(isSwahili ? 'Hifadhi' : 'Save'),
                    ),
                  ],
                ),
              ),
      ),
    );
  }
}

// ───────────────────────────────────── Detail sheet ─────────────────────────

/// Full task detail with progress control, comment thread and attachments.
///
/// Fetches `/content-creator/tasks/{id}` (which includes `comments` and
/// `attachments`). Every mutation re-fetches the task and notifies the board
/// via [onChanged] so counts/cards stay in sync.
class _TaskDetailSheet extends ConsumerStatefulWidget {
  final int taskId;
  final Map<String, dynamic> summary;
  final bool isSwahili;
  final VoidCallback onChanged;

  const _TaskDetailSheet({
    required this.taskId,
    required this.summary,
    required this.isSwahili,
    required this.onChanged,
  });

  @override
  ConsumerState<_TaskDetailSheet> createState() => _TaskDetailSheetState();
}

class _TaskDetailSheetState extends ConsumerState<_TaskDetailSheet> {
  Map<String, dynamic>? _task;
  bool _loading = true;
  String? _error;
  bool _savingProgress = false;
  bool _postingComment = false;
  bool _uploading = false;
  final _commentCtrl = TextEditingController();

  static const _progressSteps = <String>['not_started', 'in_progress', 'completed'];

  @override
  void initState() {
    super.initState();
    _task = widget.summary;
    _load();
  }

  @override
  void dispose() {
    _commentCtrl.dispose();
    super.dispose();
  }

  String _tr(String en, String sw) => widget.isSwahili ? sw : en;

  String _labelize(String value) => value
      .split('_')
      .map((w) =>
          w.isEmpty ? w : '${w[0].toUpperCase()}${w.substring(1).toLowerCase()}')
      .join(' ');

  String _errMsg(Object e) {
    if (e is DioException) {
      final data = e.response?.data;
      if (data is Map && data['message'] != null) {
        return data['message'].toString();
      }
    }
    return e.toString();
  }

  void _snack(String msg) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final res = await ref
          .read(apiClientProvider)
          .get('/content-creator/tasks/${widget.taskId}');
      final data =
          res.data is Map ? Map<String, dynamic>.from(res.data as Map) : {};
      final task = data['data'] is Map
          ? Map<String, dynamic>.from(data['data'] as Map)
          : null;
      if (!mounted) return;
      setState(() {
        _task = task ?? _task;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = _errMsg(e);
        _loading = false;
      });
    }
  }

  Future<void> _setProgress(String progress) async {
    if (_savingProgress) return;
    setState(() => _savingProgress = true);
    try {
      await ref.read(apiClientProvider).post(
        '/content-creator/tasks/${widget.taskId}/progress',
        data: {'progress': progress},
      );
      widget.onChanged();
      await _load();
    } catch (e) {
      _snack('${_tr('Failed', 'Imeshindwa')}: ${_errMsg(e)}');
    } finally {
      if (mounted) setState(() => _savingProgress = false);
    }
  }

  Future<void> _addComment() async {
    final text = _commentCtrl.text.trim();
    if (text.isEmpty || _postingComment) return;
    setState(() => _postingComment = true);
    try {
      await ref.read(apiClientProvider).post(
        '/content-creator/tasks/${widget.taskId}/comments',
        data: {'comment': text},
      );
      _commentCtrl.clear();
      widget.onChanged();
      await _load();
    } catch (e) {
      _snack('${_tr('Failed', 'Imeshindwa')}: ${_errMsg(e)}');
    } finally {
      if (mounted) setState(() => _postingComment = false);
    }
  }

  Future<void> _uploadAttachment() async {
    if (_uploading) return;
    final result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: const [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'mp4', 'mov', 'avi', 'pdf', 'ai', 'psd', 'svg', 'zip', 'sketch',
      ],
    );
    final path = result?.files.single.path;
    if (path == null) return;
    setState(() => _uploading = true);
    try {
      final form = FormData.fromMap({
        'file': await MultipartFile.fromFile(path),
      });
      await ref
          .read(apiClientProvider)
          .uploadFile('/content-creator/tasks/${widget.taskId}/attachments',
              data: form);
      widget.onChanged();
      await _load();
      _snack(_tr('Attachment uploaded.', 'Kiambatisho kimepakiwa.'));
    } catch (e) {
      _snack('${_tr('Upload failed', 'Upakiaji umeshindwa')}: ${_errMsg(e)}');
    } finally {
      if (mounted) setState(() => _uploading = false);
    }
  }

  Future<void> _openEdit() async {
    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.92,
        child: _TaskFormSheet(task: _task),
      ),
    );
    if (saved == true) {
      widget.onChanged();
      await _load();
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ref.watch(isDarkModeProvider);
    final t = _task ?? const <String, dynamic>{};

    final status = t['status']?.toString() ?? '';
    final priority = t['priority']?.toString() ?? '';
    final platform = t['platform']?.toString() ?? '';
    final taskType = t['task_type']?.toString() ?? '';
    final assignee = t['assignee_name']?.toString() ?? '';
    final deadline = t['deadline']?.toString() ?? '';
    final description = t['description']?.toString() ?? '';
    final instructions = t['instructions']?.toString() ?? '';
    final progress = t['progress']?.toString() ?? 'not_started';
    final comments = (t['comments'] as List?) ?? const [];
    final attachments = (t['attachments'] as List?) ?? const [];

    return Container(
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF1A1A1A) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 16, 12, 4),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      t['title']?.toString() ?? '-',
                      style: AppType.display(18, weight: FontWeight.w700),
                    ),
                  ),
                  IconButton(
                    tooltip: _tr('Edit', 'Hariri'),
                    onPressed: _openEdit,
                    icon: const Icon(Icons.edit_outlined),
                  ),
                  IconButton(
                    tooltip: _tr('Close', 'Funga'),
                    onPressed: () => Navigator.of(context).pop(),
                    icon: const Icon(Icons.close_rounded),
                  ),
                ],
              ),
            ),
            if (_loading) const LinearProgressIndicator(minHeight: 2),
            Flexible(
              child: ListView(
                padding: EdgeInsets.fromLTRB(
                  20,
                  8,
                  20,
                  MediaQuery.of(context).viewInsets.bottom + 28,
                ),
                children: [
                  if (_error != null)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: Text(
                        _error!,
                        style: const TextStyle(color: AppColors.error),
                      ),
                    ),
                  Wrap(
                    spacing: 6,
                    runSpacing: 4,
                    children: [
                      if (status.isNotEmpty)
                        _chip(_labelize(status), AppColors.brandBlue,
                            Icons.dashboard_customize_outlined),
                      if (priority.isNotEmpty)
                        _chip(_labelize(priority), _priorityColor(priority),
                            Icons.flag_outlined),
                      if (platform.isNotEmpty)
                        _chip(_labelize(platform), AppColors.brandBlue,
                            Icons.public_rounded),
                      if (taskType.isNotEmpty)
                        _chip(_labelize(taskType), AppColors.textSecondary,
                            Icons.layers_outlined),
                    ],
                  ),
                  const SizedBox(height: 14),
                  _infoRow(Icons.person_outline,
                      assignee.isEmpty ? _tr('Unassigned', 'Hajachaguliwa') : assignee),
                  if (deadline.isNotEmpty)
                    _infoRow(Icons.event_outlined, deadline,
                        danger: t['is_overdue'] == true),
                  if (description.isNotEmpty) ...[
                    const SizedBox(height: 12),
                    _sectionLabel(_tr('Brief', 'Maelezo')),
                    Text(description),
                  ],
                  if (instructions.isNotEmpty) ...[
                    const SizedBox(height: 12),
                    _sectionLabel(_tr('Instructions', 'Maagizo')),
                    Text(instructions),
                  ],
                  const SizedBox(height: 18),
                  _sectionLabel(_tr('Progress', 'Maendeleo')),
                  const SizedBox(height: 6),
                  Wrap(
                    spacing: 8,
                    children: [
                      for (final step in _progressSteps)
                        ChoiceChip(
                          label: Text(_progressLabel(step)),
                          selected: progress == step,
                          onSelected: _savingProgress
                              ? null
                              : (_) {
                                  if (progress != step) _setProgress(step);
                                },
                          selectedColor: AppColors.brandGreen,
                          labelStyle: TextStyle(
                            color: progress == step
                                ? Colors.white
                                : AppColors.textSecondary,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 18),
                  _sectionLabel(
                      '${_tr('Attachments', 'Viambatisho')} (${attachments.length})'),
                  const SizedBox(height: 6),
                  if (attachments.isEmpty)
                    Text(
                      _tr('No attachments yet.', 'Hakuna viambatisho bado.'),
                      style: const TextStyle(color: AppColors.textSecondary),
                    )
                  else
                    ...attachments.whereType<Map>().map((a) {
                      final name = a['name']?.toString() ??
                          (a['url']?.toString() ?? '-');
                      final url = a['url']?.toString() ?? '';
                      return ListTile(
                        contentPadding: EdgeInsets.zero,
                        dense: true,
                        leading: const Icon(Icons.attach_file_rounded,
                            color: AppColors.brandBlue),
                        title: Text(name, overflow: TextOverflow.ellipsis),
                        trailing: const Icon(Icons.open_in_new_rounded, size: 16),
                        onTap: url.isEmpty
                            ? null
                            : () => ExternalLauncherService.openMenuUrl(url),
                      );
                    }),
                  const SizedBox(height: 6),
                  OutlinedButton.icon(
                    onPressed: _uploading ? null : _uploadAttachment,
                    icon: _uploading
                        ? const SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.upload_file_rounded, size: 18),
                    label: Text(_tr('Upload', 'Pakia')),
                  ),
                  const SizedBox(height: 18),
                  _sectionLabel(
                      '${_tr('Comments', 'Maoni')} (${comments.length})'),
                  const SizedBox(height: 6),
                  if (comments.isEmpty)
                    Text(
                      _tr('No comments yet.', 'Hakuna maoni bado.'),
                      style: const TextStyle(color: AppColors.textSecondary),
                    )
                  else
                    ...comments.whereType<Map>().map(_commentTile),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: _commentCtrl,
                          minLines: 1,
                          maxLines: 3,
                          decoration: _dec(_tr('Add a comment…', 'Ongeza maoni…')),
                        ),
                      ),
                      const SizedBox(width: 8),
                      IconButton.filled(
                        onPressed: _postingComment ? null : _addComment,
                        icon: _postingComment
                            ? const SizedBox(
                                width: 16,
                                height: 16,
                                child: CircularProgressIndicator(
                                    strokeWidth: 2, color: Colors.white),
                              )
                            : const Icon(Icons.send_rounded),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Color _priorityColor(String priority) => switch (priority) {
        'high' => AppColors.error,
        'medium' => AppColors.brandYellow,
        'low' => AppColors.brandGreen,
        _ => AppColors.textSecondary,
      };

  String _progressLabel(String step) => switch (step) {
        'not_started' => _tr('Not started', 'Haijaanza'),
        'in_progress' => _tr('In progress', 'Inaendelea'),
        'completed' => _tr('Completed', 'Imekamilika'),
        _ => _labelize(step),
      };

  Widget _sectionLabel(String label) => Padding(
        padding: const EdgeInsets.only(bottom: 4),
        child: Text(
          label,
          style: const TextStyle(
            fontWeight: FontWeight.w700,
            fontSize: 13,
            color: AppColors.textSecondary,
          ),
        ),
      );

  Widget _infoRow(IconData icon, String text, {bool danger = false}) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 3),
        child: Row(
          children: [
            Icon(icon,
                size: 16,
                color: danger ? AppColors.error : AppColors.textSecondary),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                text,
                style: TextStyle(
                  color: danger ? AppColors.error : null,
                  fontWeight: danger ? FontWeight.w700 : FontWeight.w500,
                ),
              ),
            ),
          ],
        ),
      );

  Widget _chip(String label, Color color, IconData icon) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.10),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 12, color: color),
            const SizedBox(width: 4),
            Text(label,
                style: TextStyle(
                    fontSize: 11, fontWeight: FontWeight.w700, color: color)),
          ],
        ),
      );

  Widget _commentTile(Map comment) {
    final name = comment['user_name']?.toString() ?? '—';
    final body = comment['comment']?.toString() ?? '';
    final at = comment['created_at']?.toString() ?? '';
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            radius: 14,
            backgroundColor: AppColors.brandBlue.withValues(alpha: 0.15),
            child: Text(
              name.isEmpty ? '?' : name.trim().substring(0, 1).toUpperCase(),
              style: const TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w700,
                color: AppColors.brandBlue,
              ),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(name,
                          style: const TextStyle(
                              fontWeight: FontWeight.w700, fontSize: 13)),
                    ),
                    if (at.length >= 10)
                      Text(at.substring(0, 10),
                          style: const TextStyle(
                              fontSize: 11, color: AppColors.textSecondary)),
                  ],
                ),
                const SizedBox(height: 2),
                Text(body),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ───────────────────────────────────── Crew section ─────────────────────────

/// Content-team roster with tappable presence (online / busy / away / offline).
class _CrewSection extends ConsumerWidget {
  final List crew;
  final bool isSwahili;
  final VoidCallback onChanged;

  const _CrewSection({
    required this.crew,
    required this.isSwahili,
    required this.onChanged,
  });

  static const _statusColors = <String, Color>{
    'online': AppColors.brandGreen,
    'busy': AppColors.error,
    'away': Colors.orange,
    'offline': Colors.grey,
  };

  String _tr(String en, String sw) => isSwahili ? sw : en;

  Color _dotColor(String status) => _statusColors[status] ?? Colors.grey;

  String _statusLabel(String status) => switch (status) {
        'online' => _tr('Online', 'Mtandaoni'),
        'busy' => _tr('Busy', 'Bize'),
        'away' => _tr('Away', 'Ametoka'),
        'offline' => _tr('Offline', 'Nje ya mtandao'),
        _ => status,
      };

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final members = crew.whereType<Map>().toList();
    if (members.isEmpty) return const SizedBox.shrink();

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.brandBlue.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.groups_rounded,
                  size: 18, color: AppColors.brandBlue),
              const SizedBox(width: 6),
              Text(
                _tr('Crew', 'Timu'),
                style: AppType.display(14, weight: FontWeight.w700),
              ),
            ],
          ),
          const SizedBox(height: 8),
          ...members.map((m) {
            final name = m['name']?.toString() ?? '—';
            final role = m['role']?.toString() ?? '';
            final status = m['online_status']?.toString() ?? 'offline';
            return InkWell(
              borderRadius: BorderRadius.circular(10),
              onTap: () => _pickStatus(context, ref, m),
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 6),
                child: Row(
                  children: [
                    Container(
                      width: 10,
                      height: 10,
                      decoration: BoxDecoration(
                        color: _dotColor(status),
                        shape: BoxShape.circle,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(name,
                              style:
                                  const TextStyle(fontWeight: FontWeight.w600)),
                          if (role.isNotEmpty)
                            Text(role,
                                style: const TextStyle(
                                    fontSize: 11,
                                    color: AppColors.textSecondary)),
                        ],
                      ),
                    ),
                    Text(
                      _statusLabel(status),
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: _dotColor(status),
                      ),
                    ),
                    const Icon(Icons.chevron_right_rounded,
                        size: 18, color: AppColors.textSecondary),
                  ],
                ),
              ),
            );
          }),
        ],
      ),
    );
  }

  Future<void> _pickStatus(
      BuildContext context, WidgetRef ref, Map member) async {
    final rawId = member['user_id'];
    final userId = rawId is int ? rawId : int.tryParse('${rawId ?? ''}');
    if (userId == null) return;

    final picked = await showModalBottomSheet<String>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (_) => Container(
        decoration: BoxDecoration(
          color: Theme.of(context).scaffoldBackgroundColor,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SafeArea(
          top: false,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
                child: Text(
                  '${member['name'] ?? '-'}',
                  style: AppType.display(16, weight: FontWeight.w700),
                ),
              ),
              for (final s in _statusColors.keys)
                ListTile(
                  leading: Container(
                    width: 12,
                    height: 12,
                    decoration: BoxDecoration(
                      color: _dotColor(s),
                      shape: BoxShape.circle,
                    ),
                  ),
                  title: Text(_statusLabel(s)),
                  onTap: () => Navigator.of(context).pop(s),
                ),
              const SizedBox(height: 8),
            ],
          ),
        ),
      ),
    );

    if (picked == null) return;
    try {
      await ref.read(apiClientProvider).post(
        '/content-creator/crew/$userId/status',
        data: {'online_status': picked},
      );
      onChanged();
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(_tr('Status updated.', 'Hali imesasishwa.'))),
        );
      }
    } catch (e) {
      if (context.mounted) {
        final msg = e is DioException &&
                e.response?.data is Map &&
                (e.response!.data as Map)['message'] != null
            ? (e.response!.data as Map)['message'].toString()
            : e.toString();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('${_tr('Failed', 'Imeshindwa')}: $msg')),
        );
      }
    }
  }
}

// ───────────────────────────────────── Target sheet ─────────────────────────

/// Set a monthly post target for a platform (`POST /content-creator/targets`).
class _TargetFormSheet extends ConsumerStatefulWidget {
  const _TargetFormSheet();

  @override
  ConsumerState<_TargetFormSheet> createState() => _TargetFormSheetState();
}

class _TargetFormSheetState extends ConsumerState<_TargetFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _targetCtrl = TextEditingController(text: '0');

  // Targets endpoint accepts these platforms only (no "general").
  static const _platforms = <String>[
    'instagram', 'tiktok', 'facebook', 'linkedin', 'youtube',
  ];

  String _platform = 'instagram';
  late int _month = DateTime.now().month;
  late int _year = DateTime.now().year;
  bool _saving = false;

  @override
  void dispose() {
    _targetCtrl.dispose();
    super.dispose();
  }

  String _tr(String en, String sw) =>
      ref.read(isSwahiliProvider) ? sw : en;

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      await ref.read(apiClientProvider).post(
        '/content-creator/targets',
        data: {
          'platform': _platform,
          'target_posts': int.tryParse(_targetCtrl.text.trim()) ?? 0,
          'month': _month,
          'year': _year,
        },
      );
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (e) {
      if (!mounted) return;
      final msg = e is DioException &&
              e.response?.data is Map &&
              (e.response!.data as Map)['message'] != null
          ? (e.response!.data as Map)['message'].toString()
          : e.toString();
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text('${_tr('Failed', 'Imeshindwa')}: $msg')));
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = ref.watch(isDarkModeProvider);
    final now = DateTime.now();
    final years = [for (var y = now.year - 1; y <= now.year + 2; y++) y];

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
            padding: EdgeInsets.fromLTRB(
              20,
              16,
              20,
              MediaQuery.of(context).viewInsets.bottom + 28,
            ),
            children: [
              Text(
                _tr('Set Platform Target', 'Weka Lengo la Jukwaa'),
                textAlign: TextAlign.center,
                style: AppType.display(20),
              ),
              const SizedBox(height: 16),
              DropdownButtonFormField<String>(
      isExpanded: true,
                value: _platform,
                decoration: _dec(_tr('Platform', 'Jukwaa')),
                items: _platforms
                    .map((p) => DropdownMenuItem<String>(
                          value: p,
                          child: Text('${p[0].toUpperCase()}${p.substring(1)}'),
                        ))
                    .toList(),
                onChanged: (v) => setState(() => _platform = v ?? _platform),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _targetCtrl,
                keyboardType: TextInputType.number,
                decoration: _dec(_tr('Target posts', 'Machapisho lengwa')),
                validator: (v) {
                  final n = int.tryParse((v ?? '').trim());
                  if (n == null || n < 0) {
                    return _tr('Enter a valid number', 'Weka namba sahihi');
                  }
                  return null;
                },
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<int>(
      isExpanded: true,
                value: _month,
                decoration: _dec(_tr('Month', 'Mwezi')),
                items: [
                  for (var m = 1; m <= 12; m++)
                    DropdownMenuItem<int>(value: m, child: Text('$m')),
                ],
                onChanged: (v) => setState(() => _month = v ?? _month),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<int>(
      isExpanded: true,
                value: years.contains(_year) ? _year : years.first,
                decoration: _dec(_tr('Year', 'Mwaka')),
                items: years
                    .map((y) =>
                        DropdownMenuItem<int>(value: y, child: Text('$y')))
                    .toList(),
                onChanged: (v) => setState(() => _year = v ?? _year),
              ),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: _saving ? null : _save,
                child: _saving
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : Text(_tr('Save', 'Hifadhi')),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ───────────────────────────────────── Utilities ────────────────────────────

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
