import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
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

  String _tr(bool sw, String en, String swText) => sw ? swText : en;

  void _bump() => setState(() => _refreshTick++);

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final scaffoldKey = ref.watch(rootScaffoldKeyProvider);

    return Scaffold(
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
      body: _BoardView(
        search: _search,
        statusFilter: _statusFilter,
        refreshTick: _refreshTick,
        onSearchChanged: (s) => setState(() => _search = s),
        onStatusChanged: (s) => setState(() => _statusFilter = s),
        onTaskChanged: _bump,
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
          final stats = data['stats'] is Map
              ? Map<String, dynamic>.from(data['stats'] as Map)
              : const <String, dynamic>{};

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
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
            children: [
              _CcHero(stats: stats, isSwahili: isSwahili),
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

// ───────────────────────────────────── Hero ─────────────────────────────────

class _CcHero extends StatelessWidget {
  final Map<String, dynamic> stats;
  final bool isSwahili;
  const _CcHero({required this.stats, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [AppColors.brandBlue, AppColors.brandGreen],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.movie_creation_outlined,
                  color: Colors.white, size: 28),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  isSwahili ? 'Bodi ya Maudhui' : 'Content Board',
                  style: AppType.display(20, color: Colors.white),
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          Row(
            children: [
              _heroStat(
                isSwahili ? 'Jumla' : 'Total',
                '${stats['total'] ?? 0}',
              ),
              _heroStat(
                isSwahili ? 'Zimechapishwa' : 'Published',
                '${stats['published'] ?? 0}',
              ),
              _heroStat(
                isSwahili ? 'Zimechelewa' : 'Overdue',
                '${stats['overdue'] ?? 0}',
              ),
              _heroStat(
                isSwahili ? 'Kwa wakati' : 'On time',
                '${stats['on_time_rate'] ?? 0}%',
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _heroStat(String label, String value) => Expanded(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(value,
                style: AppType.display(20,
                    color: Colors.white, weight: FontWeight.w800)),
            Text(label,
                style: const TextStyle(color: Colors.white70, fontSize: 11)),
          ],
        ),
      );
}

// ───────────────────────────────────── Task card ────────────────────────────

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
        onTap: () => _edit(context),
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
