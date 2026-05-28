import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
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
  String _search = '';

  static String _formatMonth(DateTime dt) =>
      '${dt.year.toString().padLeft(4, '0')}-${dt.month.toString().padLeft(2, '0')}';

  String _tr(bool sw, String en, String swText) => sw ? swText : en;

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final api = ref.watch(apiClientProvider);
    final scaffoldKey = ref.watch(rootScaffoldKeyProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => scaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(
          _tr(isSwahili, 'Field Marketing', 'Masoko ya Uwandani'),
          style: AppType.display(18),
        ),
        actions: [
          IconButton(
            tooltip: _tr(isSwahili, 'Refresh', 'Onyesha tena'),
            onPressed: () => setState(() {}),
            icon: const Icon(Icons.refresh_rounded),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        icon: const Icon(Icons.add),
        label: Text(_tr(isSwahili, 'New Session', 'Kikao Kipya')),
        onPressed: () async {
          final saved = await _openSessionSheet(context, api);
          if (saved == true && mounted) setState(() {});
        },
      ),
      body: _FmFeedView(
        month: _month,
        search: _search,
        onMonthChanged: (m) => setState(() => _month = m),
        onSearchChanged: (s) => setState(() => _search = s),
      ),
    );
  }

  Future<bool?> _openSessionSheet(
    BuildContext context,
    ApiClient api, {
    Map<String, dynamic>? session,
  }) {
    return showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.85,
        child: _SessionFormSheet(session: session),
      ),
    );
  }
}

class _FmFeedView extends ConsumerStatefulWidget {
  final String month;
  final String search;
  final ValueChanged<String> onMonthChanged;
  final ValueChanged<String> onSearchChanged;

  const _FmFeedView({
    required this.month,
    required this.search,
    required this.onMonthChanged,
    required this.onSearchChanged,
  });

  @override
  ConsumerState<_FmFeedView> createState() => _FmFeedViewState();
}

class _FmFeedViewState extends ConsumerState<_FmFeedView> {
  late Future<Map<String, dynamic>> _future;
  final _searchCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _searchCtrl.text = widget.search;
    _future = _load();
  }

  @override
  void didUpdateWidget(_FmFeedView oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.month != widget.month || oldWidget.search != widget.search) {
      _future = _load();
    }
  }

  Future<Map<String, dynamic>> _load() async {
    final api = ref.read(apiClientProvider);
    final res = await api.get(
      '/field-marketing',
      queryParameters: {
        'month': widget.month,
        if (widget.search.trim().isNotEmpty) 'search': widget.search.trim(),
      },
    );
    final data = res.data is Map ? Map<String, dynamic>.from(res.data as Map) : {};
    return data['data'] is Map
        ? Map<String, dynamic>.from(data['data'] as Map)
        : <String, dynamic>{};
  }

  @override
  void dispose() {
    _searchCtrl.dispose();
    super.dispose();
  }

  String _tr(bool sw, String en, String swText) => sw ? swText : en;

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDark = ref.watch(isDarkModeProvider);

    return RefreshIndicator(
      onRefresh: () async => setState(() => _future = _load()),
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
          final sessions = (data['sessions'] as List? ?? const [])
              .whereType<Map>()
              .map((e) => Map<String, dynamic>.from(e))
              .toList();
          final stats = data['stats'] is Map
              ? Map<String, dynamic>.from(data['stats'] as Map)
              : const <String, dynamic>{};

          return ListView(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
            children: [
              _FmHero(
                month: widget.month,
                stats: stats,
                isSwahili: isSwahili,
                onMonthChanged: widget.onMonthChanged,
              ),
              const SizedBox(height: 16),
              _StatsRow(stats: stats, isSwahili: isSwahili),
              const SizedBox(height: 16),
              TextField(
                controller: _searchCtrl,
                decoration: InputDecoration(
                  hintText: _tr(isSwahili,
                      'Search sessions by area or number…',
                      'Tafuta kikao kwa eneo au nambari…'),
                  prefixIcon: const Icon(Icons.search_rounded),
                  suffixIcon: _searchCtrl.text.isEmpty
                      ? null
                      : IconButton(
                          icon: const Icon(Icons.clear_rounded),
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
                onChanged: (v) => setState(() {}),
              ),
              const SizedBox(height: 16),
              Text(
                _tr(isSwahili, 'Sessions', 'Vikao'),
                style: AppType.display(16),
              ),
              const SizedBox(height: 8),
              if (sessions.isEmpty)
                _EmptyState(
                  icon: Icons.travel_explore_outlined,
                  title: _tr(isSwahili,
                      'No sessions this month.', 'Hakuna vikao mwezi huu.'),
                )
              else
                ...sessions.map(
                  (s) => _SessionCard(
                    session: s,
                    isSwahili: isSwahili,
                    onChanged: () => setState(() => _future = _load()),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}

class _FmHero extends StatelessWidget {
  final String month;
  final Map<String, dynamic> stats;
  final bool isSwahili;
  final ValueChanged<String> onMonthChanged;

  const _FmHero({
    required this.month,
    required this.stats,
    required this.isSwahili,
    required this.onMonthChanged,
  });

  @override
  Widget build(BuildContext context) {
    final dt = _parseMonth(month);
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
              Expanded(
                child: Text(
                  isSwahili ? 'Masoko ya Uwandani' : 'Field Marketing',
                  style: AppType.display(20, color: Colors.white),
                ),
              ),
              IconButton(
                tooltip: isSwahili ? 'Chagua mwezi' : 'Pick month',
                onPressed: () async {
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: dt,
                    firstDate: DateTime(2020),
                    lastDate: DateTime.now().add(const Duration(days: 365)),
                    helpText:
                        isSwahili ? 'Chagua siku ndani ya mwezi' : 'Pick any day in month',
                  );
                  if (picked != null) {
                    onMonthChanged(
                      '${picked.year.toString().padLeft(4, '0')}-${picked.month.toString().padLeft(2, '0')}',
                    );
                  }
                },
                icon: const Icon(Icons.calendar_month_rounded,
                    color: Colors.white),
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            isSwahili
                ? 'Mwezi: $month — fuatilia ziara na ubadilishaji.'
                : 'Month $month — track visits and conversions.',
            style: const TextStyle(color: Colors.white70),
          ),
          const SizedBox(height: 18),
          Text(
            '${stats['total'] ?? 0}',
            style: AppType.display(36, color: Colors.white, weight: FontWeight.w900),
          ),
          const SizedBox(height: 4),
          Text(
            isSwahili ? 'Jumla ya ziara mwezi huu' : 'Total visits this month',
            style: const TextStyle(color: Colors.white70),
          ),
        ],
      ),
    );
  }

  DateTime _parseMonth(String s) {
    final parts = s.split('-');
    if (parts.length != 2) return DateTime.now();
    return DateTime(int.parse(parts[0]), int.parse(parts[1]), 1);
  }
}

class _StatsRow extends StatelessWidget {
  final Map<String, dynamic> stats;
  final bool isSwahili;
  const _StatsRow({required this.stats, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    Widget tile(String label, int value, Color color, IconData icon) =>
        Expanded(
          child: Container(
            margin: const EdgeInsets.symmetric(horizontal: 4),
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.10),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(icon, color: color, size: 18),
                const SizedBox(height: 6),
                Text('$value',
                    style: AppType.display(20,
                        weight: FontWeight.w800, color: color)),
                Text(label, style: const TextStyle(fontSize: 11)),
              ],
            ),
          ),
        );

    return Row(
      children: [
        tile(isSwahili ? 'Zilizobadilika' : 'Converted',
            (stats['converted'] ?? 0) as int, AppColors.brandGreen,
            Icons.task_alt_rounded),
        tile(isSwahili ? 'Wanavutiwa' : 'Interested',
            (stats['interested'] ?? 0) as int, AppColors.brandBlue,
            Icons.thumb_up_alt_outlined),
        tile(isSwahili ? 'Kuendeleza' : 'Follow-up',
            (stats['follow_up'] ?? 0) as int, AppColors.brandYellow,
            Icons.event_repeat_rounded),
      ],
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
