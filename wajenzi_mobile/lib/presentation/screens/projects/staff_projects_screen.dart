import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';

final _filtersProvider = StateProvider.autoDispose<Map<String, dynamic>>(
  (ref) => {},
);
final _searchProvider = StateProvider.autoDispose<String>((ref) => '');

final _referenceProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/projects/reference-data');
  return response.data['data'] as Map<String, dynamic>? ?? {};
});

final _statsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/projects/stats');
  return response.data['data'] as Map<String, dynamic>? ?? {};
});

final _projectsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final filters = ref.watch(_filtersProvider);
  final response = await api.get('/projects', queryParameters: filters);
  final payload = response.data['data'];
  final collection = payload is Map<String, dynamic> ? payload : null;
  return {
    'items': (collection?['data'] ?? const []) as List,
    'meta': collection?['meta'] as Map<String, dynamic>? ?? {},
  };
});

final _projectDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/projects/$id');
      return response.data['data'] as Map<String, dynamic>? ?? {};
    });

class StaffProjectsScreen extends ConsumerWidget {
  const StaffProjectsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final projectsAsync = ref.watch(_projectsProvider);
    final statsAsync = ref.watch(_statsProvider);
    final referenceAsync = ref.watch(_referenceProvider);
    final search = ref.watch(_searchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(_tr(context, en: 'Projects', sw: 'Miradi', ar: 'المشاريع')),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _showProjectForm(context, ref),
          child: const Icon(Icons.add_rounded),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_projectsProvider);
          ref.invalidate(_statsProvider);
          ref.invalidate(_referenceProvider);
        },
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: statsAsync.when(
                loading: () => const SizedBox.shrink(),
                error: (_, __) => const SizedBox.shrink(),
                data: (stats) => _StatsRow(stats: stats),
              ),
            ),
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
                child: referenceAsync.when(
                  loading: () => const SizedBox.shrink(),
                  error: (_, __) => const SizedBox.shrink(),
                  data: (reference) => _Filters(reference: reference),
                ),
              ),
            ),
            projectsAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (e, _) =>
                  SliverFillRemaining(child: Center(child: Text('$e'))),
              data: (payload) {
                final allItems = (payload['items'] as List)
                    .cast<Map<String, dynamic>>();
                final items = search.isEmpty
                    ? allItems
                    : allItems.where((project) {
                        final haystack = [
                          project['document_number'],
                          project['project_name'],
                          (project['client'] as Map<String, dynamic>?)?['name'],
                          (project['project_type']
                              as Map<String, dynamic>?)?['name'],
                          (project['service_type']
                              as Map<String, dynamic>?)?['name'],
                          project['status'],
                          project['approval_status'],
                          project['approval_summary'],
                          (project['salesperson']
                              as Map<String, dynamic>?)?['name'],
                          (project['project_manager']
                              as Map<String, dynamic>?)?['name'],
                        ].whereType<Object>().join(' ').toLowerCase();
                        return haystack.contains(search);
                      }).toList();
                if (items.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(
                              _tr(
                                context,
                                en: 'No projects found',
                                sw: 'Hakuna miradi iliyopatikana',
                                ar: 'لم يتم العثور على مشاريع',
                              ),
                            ),
                            if (search.isNotEmpty) ...[
                              const SizedBox(height: 12),
                              ElevatedButton.icon(
                                onPressed: () =>
                                    ref.read(_searchProvider.notifier).state =
                                        '',
                                icon: const Icon(Icons.arrow_back_rounded),
                                label: Text(
                                  _tr(
                                    context,
                                    en: 'Back',
                                    sw: 'Rudi',
                                    ar: 'رجوع',
                                  ),
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                    ),
                  );
                }
                return SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate((context, index) {
                      final project = items[index];
                      return _ProjectCard(
                        project: project,
                        onView: () =>
                            _showProjectDetails(context, project['id'] as int),
                        onEdit: () =>
                            _showProjectForm(context, ref, project: project),
                        onDelete: () => _deleteProject(context, ref, project),
                      );
                    }, childCount: items.length),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _showProjectForm(
    BuildContext context,
    WidgetRef ref, {
    Map<String, dynamic>? project,
  }) async {
    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _ProjectFormSheet(project: project),
    );
    if (result == true) {
      ref.invalidate(_projectsProvider);
      ref.invalidate(_statsProvider);
    }
  }

  void _showProjectDetails(BuildContext context, int id) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _ProjectDetailSheet(projectId: id),
    );
  }

  Future<void> _deleteProject(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> project,
  ) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(
          _tr(
            context,
            en: 'Delete Project',
            sw: 'Futa Mradi',
            ar: 'حذف المشروع',
          ),
        ),
        content: Text(
          _tr(
            context,
            en: 'Are you sure you want to delete ${project['project_name'] ?? 'this project'}?',
            sw: 'Una uhakika unataka kufuta ${project['project_name'] ?? 'mradi huu'}?',
            ar: 'هل أنت متأكد أنك تريد حذف ${project['project_name'] ?? 'هذا المشروع'}؟',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(_tr(context, en: 'Cancel', sw: 'Ghairi', ar: 'إلغاء')),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(_tr(context, en: 'Delete', sw: 'Futa', ar: 'حذف')),
          ),
        ],
      ),
    );
    if (confirmed != true) return;
    final api = ref.read(apiClientProvider);
    await api.delete('/projects/${project['id']}');
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _tr(
              context,
              en: 'Project deleted',
              sw: 'Mradi umefutwa',
              ar: 'تم حذف المشروع',
            ),
          ),
          backgroundColor: AppColors.success,
        ),
      );
    }
    ref.invalidate(_projectsProvider);
    ref.invalidate(_statsProvider);
  }
}

class _StatsRow extends StatelessWidget {
  final Map<String, dynamic> stats;
  const _StatsRow({required this.stats});

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    final cards = [
      (
        _tr(context, en: 'Total', sw: 'Jumla', ar: 'الإجمالي'),
        '${stats['total'] ?? 0}',
        const Color(0xFF3498DB),
        Icons.folder_rounded,
      ),
      (
        _tr(context, en: 'Active', sw: 'Inayoendelea', ar: 'نشط'),
        '${stats['active'] ?? 0}',
        const Color(0xFF1ABC9C),
        Icons.construction_rounded,
      ),
      (
        _tr(context, en: 'Completed', sw: 'Imekamilika', ar: 'مكتمل'),
        '${stats['completed'] ?? 0}',
        const Color(0xFF27AE60),
        Icons.check_circle_rounded,
      ),
      (
        _tr(context, en: 'Delayed', sw: 'Imechelewa', ar: 'متأخر'),
        '${stats['delayed'] ?? 0}',
        const Color(0xFFE74C3C),
        Icons.warning_amber_rounded,
      ),
      (
        _tr(context, en: 'Value', sw: 'Thamani', ar: 'القيمة'),
        'TZS ${NumberFormat.compact().format(_toDouble(stats['total_value']))}',
        const Color(0xFF9B59B6),
        Icons.monetization_on_rounded,
      ),
    ];
    return SizedBox(
      height: 110,
      child: ListView.separated(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
        scrollDirection: Axis.horizontal,
        itemBuilder: (_, index) {
          final (label, value, color, icon) = cards[index];
          return Container(
            width: 130,
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: cs.surface,
              borderRadius: BorderRadius.circular(14),
              border: Border(left: BorderSide(color: color, width: 4)),
              boxShadow: [
                BoxShadow(
                  color: color.withValues(alpha: 0.10),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: Row(
              children: [
                Icon(icon, color: color, size: 28),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        value,
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                          color: color,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        label,
                        style: TextStyle(
                          fontSize: 11,
                          color: cs.onSurface.withValues(alpha: 0.55),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          );
        },
        separatorBuilder: (_, __) => const SizedBox(width: 12),
        itemCount: cards.length,
      ),
    );
  }
}

class _Filters extends ConsumerWidget {
  final Map<String, dynamic> reference;
  const _Filters({required this.reference});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final filters = ref.watch(_filtersProvider);
    final search = ref.watch(_searchProvider);
    final surface = Theme.of(context).colorScheme.surface;
    return ExpansionTile(
      title: Text(
        _tr(context, en: 'All Projects', sw: 'Miradi Yote', ar: 'كل المشاريع'),
      ),
      subtitle: Text(
        _tr(context, en: 'Filters', sw: 'Vichujio', ar: 'عوامل التصفية'),
      ),
      initiallyExpanded: filters.isNotEmpty,
      childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
      backgroundColor: surface,
      collapsedBackgroundColor: surface,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      collapsedShape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(14),
      ),
      children: [
        Padding(
          padding: const EdgeInsets.only(bottom: 12),
          child: TextFormField(
            initialValue: search,
            onChanged: (value) =>
                ref.read(_searchProvider.notifier).state = value,
            decoration: InputDecoration(
              labelText: _tr(context, en: 'Search', sw: 'Tafuta', ar: 'بحث'),
              prefixIcon: Icon(Icons.search_rounded),
            ),
          ),
        ),
        _Drop<int>(
          label: _tr(
            context,
            en: 'Project Type',
            sw: 'Aina ya Mradi',
            ar: 'نوع المشروع',
          ),
          value: filters['project_type_id'] as int?,
          items: (reference['project_types'] as List? ?? const [])
              .cast<Map<String, dynamic>>(),
          onChanged: (value) => _set(ref, 'project_type_id', value),
        ),
        _Drop<int>(
          label: _tr(
            context,
            en: 'Service Type',
            sw: 'Aina ya Huduma',
            ar: 'نوع الخدمة',
          ),
          value: filters['service_type_id'] as int?,
          items: (reference['service_types'] as List? ?? const [])
              .cast<Map<String, dynamic>>(),
          onChanged: (value) => _set(ref, 'service_type_id', value),
        ),
        _Drop<String>(
          label: _tr(context, en: 'Status', sw: 'Hali', ar: 'الحالة'),
          value: filters['status'] as String?,
          items: (reference['statuses'] as List? ?? const [])
              .cast<Map<String, dynamic>>(),
          onChanged: (value) => _set(ref, 'status', value),
        ),
        _Drop<int>(
          label: _tr(
            context,
            en: 'Salesperson',
            sw: 'Muuzaji',
            ar: 'مندوب المبيعات',
          ),
          value: filters['salesperson_id'] as int?,
          items: (reference['salespersons'] as List? ?? const [])
              .cast<Map<String, dynamic>>(),
          onChanged: (value) => _set(ref, 'salesperson_id', value),
        ),
        _Drop<int>(
          label: _tr(
            context,
            en: 'Project Manager',
            sw: 'Meneja wa Mradi',
            ar: 'مدير المشروع',
          ),
          value: filters['project_manager_id'] as int?,
          items: (reference['project_managers'] as List? ?? const [])
              .cast<Map<String, dynamic>>(),
          onChanged: (value) => _set(ref, 'project_manager_id', value),
        ),
        OutlinedButton(
          onPressed: () {
            ref.read(_filtersProvider.notifier).state = {};
            ref.read(_searchProvider.notifier).state = '';
          },
          child: Text(_tr(context, en: 'Clear', sw: 'Futa', ar: 'مسح')),
        ),
      ],
    );
  }

  void _set(WidgetRef ref, String key, Object? value) {
    final next = {...ref.read(_filtersProvider)};
    if (value == null || value == '') {
      next.remove(key);
    } else {
      next[key] = value;
    }
    ref.read(_filtersProvider.notifier).state = next;
  }
}

class _Drop<T> extends StatelessWidget {
  final String label;
  final T? value;
  final List<Map<String, dynamic>> items;
  final void Function(T?) onChanged;
  const _Drop({
    required this.label,
    required this.value,
    required this.items,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: DropdownButtonFormField<T>(
        value: value,
        isExpanded: true,
        decoration: InputDecoration(labelText: label),
        items: [
          DropdownMenuItem<T>(
            value: null,
            child: Text(
              _tr(context, en: 'All', sw: 'Zote', ar: 'الكل'),
              overflow: TextOverflow.ellipsis,
            ),
          ),
          ...items.map(
            (item) => DropdownMenuItem<T>(
              value: item['id'] as T,
              child: Text(
                item['name']?.toString() ?? '-',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ),
        ],
        onChanged: onChanged,
      ),
    );
  }
}

class _ProjectCard extends StatelessWidget {
  final Map<String, dynamic> project;
  final VoidCallback onView;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _ProjectCard({
    required this.project,
    required this.onView,
    required this.onEdit,
    required this.onDelete,
  });

  Color _statusColor(String status) {
    final s = status.toUpperCase();
    return switch (s) {
      'APPROVED' => const Color(0xFF27AE60),
      'COMPLETED' => const Color(0xFF9B59B6),
      'REJECTED' => const Color(0xFFE74C3C),
      'IN_PROGRESS' || 'SUBMITTED' => const Color(0xFF3498DB),
      _ => const Color(0xFFF39C12),
    };
  }

  @override
  Widget build(BuildContext context) {
    final cs = Theme.of(context).colorScheme;
    final status =
        (project['approval_status'] ?? project['status'] ?? 'pending')
            .toString();
    final accent = _statusColor(status);
    final client =
        (project['client'] as Map<String, dynamic>?)?['name']?.toString() ??
        '-';
    final category =
        (project['project_type'] as Map<String, dynamic>?)?['name']
            ?.toString() ??
        '';
    final serviceType =
        (project['service_type'] as Map<String, dynamic>?)?['name']
            ?.toString() ??
        '';
    final salesperson =
        (project['salesperson'] as Map<String, dynamic>?)?['name']
            ?.toString() ??
        '';
    final manager =
        (project['project_manager'] as Map<String, dynamic>?)?['name']
            ?.toString() ??
        '';
    final contractValue = _money(project['contract_value']);
    final delayDays = _toInt(project['delay_days']) ?? 0;
    final hasDelay = delayDays > 0;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: cs.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border(left: BorderSide(color: accent, width: 4)),
        boxShadow: [
          BoxShadow(
            color: accent.withValues(alpha: 0.08),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: InkWell(
        onTap: onView,
        borderRadius: BorderRadius.circular(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Header ──────────────────────────────────────────────────
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 14, 8, 0),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          project['document_number']?.toString().isNotEmpty ==
                                  true
                              ? project['document_number'].toString()
                              : 'PRJ-${project['id']}',
                          style: TextStyle(
                            fontSize: 11,
                            color: accent,
                            fontWeight: FontWeight.w600,
                            letterSpacing: 0.5,
                          ),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          project['project_name']?.toString() ?? '-',
                          style: const TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.w700,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      _StatusPill(status: status),
                      PopupMenuButton<String>(
                        onSelected: (value) {
                          if (value == 'view') onView();
                          if (value == 'edit') onEdit();
                          if (value == 'delete') onDelete();
                        },
                        itemBuilder: (_) => [
                          PopupMenuItem(
                            value: 'view',
                            child: Text(
                              _tr(context, en: 'View', sw: 'Tazama', ar: 'عرض'),
                            ),
                          ),
                          PopupMenuItem(
                            value: 'edit',
                            child: Text(
                              _tr(
                                context,
                                en: 'Edit',
                                sw: 'Hariri',
                                ar: 'تعديل',
                              ),
                            ),
                          ),
                          PopupMenuItem(
                            value: 'delete',
                            child: Text(
                              _tr(context, en: 'Delete', sw: 'Futa', ar: 'حذف'),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ],
              ),
            ),

            // ── Client + category tags ───────────────────────────────────
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 10, 16, 0),
              child: Row(
                children: [
                  Icon(
                    Icons.person_rounded,
                    size: 14,
                    color: Theme.of(
                      context,
                    ).colorScheme.onSurface.withValues(alpha: 0.45),
                  ),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      client,
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: accent,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ),
            if (category.isNotEmpty || serviceType.isNotEmpty)
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 6, 16, 0),
                child: Wrap(
                  spacing: 6,
                  runSpacing: 4,
                  children: [
                    if (category.isNotEmpty)
                      _Tag(category, color: const Color(0xFF3498DB)),
                    if (serviceType.isNotEmpty)
                      _Tag(serviceType, color: const Color(0xFF9B59B6)),
                  ],
                ),
              ),

            // ── Date range ───────────────────────────────────────────────
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 10, 16, 0),
              child: Row(
                children: [
                  Icon(
                    Icons.calendar_today_rounded,
                    size: 13,
                    color: Theme.of(
                      context,
                    ).colorScheme.onSurface.withValues(alpha: 0.45),
                  ),
                  const SizedBox(width: 4),
                  Text(
                    _formatDate(project['start_date']?.toString()),
                    style: TextStyle(
                      fontSize: 12,
                      color: Theme.of(
                        context,
                      ).colorScheme.onSurface.withValues(alpha: 0.55),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 6),
                    child: Icon(
                      Icons.arrow_forward_rounded,
                      size: 12,
                      color: Theme.of(
                        context,
                      ).colorScheme.onSurface.withValues(alpha: 0.45),
                    ),
                  ),
                  Text(
                    _formatDate(project['expected_end_date']?.toString()),
                    style: TextStyle(
                      fontSize: 12,
                      color: Theme.of(
                        context,
                      ).colorScheme.onSurface.withValues(alpha: 0.55),
                    ),
                  ),
                  if (project['actual_end_date'] != null) ...[
                    const Padding(
                      padding: EdgeInsets.symmetric(horizontal: 6),
                      child: Icon(
                        Icons.check_rounded,
                        size: 12,
                        color: Color(0xFF27AE60),
                      ),
                    ),
                    Text(
                      _formatDate(project['actual_end_date']?.toString()),
                      style: const TextStyle(
                        fontSize: 12,
                        color: Color(0xFF27AE60),
                      ),
                    ),
                  ],
                ],
              ),
            ),

            // ── Metrics row ──────────────────────────────────────────────
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 10, 16, 0),
              child: Row(
                children: [
                  _Metric(
                    label: _tr(
                      context,
                      en: 'Planned',
                      sw: 'Iliyopangwa',
                      ar: 'المخطط',
                    ),
                    value: _display(project['planned_duration']),
                    icon: Icons.schedule_rounded,
                    color: const Color(0xFF3498DB),
                  ),
                  const SizedBox(width: 8),
                  _Metric(
                    label: _tr(
                      context,
                      en: 'Actual',
                      sw: 'Halisi',
                      ar: 'الفعلي',
                    ),
                    value: _display(project['actual_duration']),
                    icon: Icons.timer_rounded,
                    color: const Color(0xFF1ABC9C),
                  ),
                  const SizedBox(width: 8),
                  _Metric(
                    label: _tr(
                      context,
                      en: 'Delay',
                      sw: 'Ucheleweshaji',
                      ar: 'التأخير',
                    ),
                    value: _display(project['delay_days']),
                    icon: hasDelay
                        ? Icons.warning_rounded
                        : Icons.check_rounded,
                    color: hasDelay
                        ? const Color(0xFFE74C3C)
                        : const Color(0xFF27AE60),
                  ),
                ],
              ),
            ),

            // ── Contract value ───────────────────────────────────────────
            if (contractValue != '-')
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
                child: Row(
                  children: [
                    const Icon(
                      Icons.monetization_on_rounded,
                      size: 14,
                      color: Color(0xFF27AE60),
                    ),
                    const SizedBox(width: 4),
                    Text(
                      contractValue,
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF27AE60),
                      ),
                    ),
                  ],
                ),
              ),

            // ── People ───────────────────────────────────────────────────
            if (salesperson.isNotEmpty || manager.isNotEmpty)
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 14),
                child: Wrap(
                  spacing: 8,
                  runSpacing: 4,
                  children: [
                    if (salesperson.isNotEmpty)
                      _PersonChip(
                        icon: Icons.sell_rounded,
                        label: salesperson,
                        color: const Color(0xFFF39C12),
                      ),
                    if (manager.isNotEmpty)
                      _PersonChip(
                        icon: Icons.manage_accounts_rounded,
                        label: manager,
                        color: const Color(0xFF3498DB),
                      ),
                  ],
                ),
              )
            else
              const SizedBox(height: 14),
          ],
        ),
      ),
    );
  }
}

class _Tag extends StatelessWidget {
  final String label;
  final Color color;
  const _Tag(this.label, {required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 10,
          color: color,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

class _Metric extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final Color color;
  const _Metric({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Row(
          children: [
            Icon(icon, size: 13, color: color),
            const SizedBox(width: 4),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    value,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: color,
                    ),
                  ),
                  Text(
                    label,
                    style: TextStyle(
                      fontSize: 9,
                      color: Theme.of(
                        context,
                      ).colorScheme.onSurface.withValues(alpha: 0.55),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PersonChip extends StatelessWidget {
  final IconData icon;
  final String label;
  final Color color;
  const _PersonChip({
    required this.icon,
    required this.label,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 12, color: color),
        const SizedBox(width: 4),
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            color: Theme.of(
              context,
            ).colorScheme.onSurface.withValues(alpha: 0.65),
          ),
        ),
      ],
    );
  }
}

class _ProjectDetailSheet extends ConsumerStatefulWidget {
  final int projectId;
  const _ProjectDetailSheet({required this.projectId});

  @override
  ConsumerState<_ProjectDetailSheet> createState() =>
      _ProjectDetailSheetState();
}

class _ProjectDetailSheetState extends ConsumerState<_ProjectDetailSheet> {
  Map<String, dynamic>? _detail;
  bool _busy = false;

  @override
  Widget build(BuildContext context) {
    final detailAsync = ref.watch(_projectDetailProvider(widget.projectId));
    final cs = Theme.of(context).colorScheme;
    return Container(
      decoration: BoxDecoration(
        color: cs.surface,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: (_detail != null ? AsyncValue.data(_detail!) : detailAsync).when(
          loading: () => const SizedBox(
            height: 320,
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (e, _) =>
              SizedBox(height: 320, child: Center(child: Text('$e'))),
          data: (project) => SingleChildScrollView(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _SheetHeader(
                  title: _tr(
                    context,
                    en: 'Project',
                    sw: 'Mradi',
                    ar: 'المشروع',
                  ),
                  onBack: () => Navigator.pop(context),
                ),
                Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _tr(
                          context,
                          en: 'Project Details',
                          sw: 'Maelezo ya Mradi',
                          ar: 'تفاصيل المشروع',
                        ),
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 12),
                      ...(((project['project_details']
                                  as Map<String, dynamic>?) ??
                              {})
                          .entries
                          .map(
                            (entry) => _DetailLine(
                              entry.key,
                              entry.value?.toString() ?? '-',
                            ),
                          )),
                      if ((project['description']?.toString().isNotEmpty ??
                              false) &&
                          ((project['project_details']
                                  as Map<String, dynamic>?)?['Description'] ==
                              null))
                        _DetailLine(
                          _tr(
                            context,
                            en: 'Description',
                            sw: 'Maelezo',
                            ar: 'الوصف',
                          ),
                          project['description'].toString(),
                        ),
                      const SizedBox(height: 20),
                      Text(
                        _tr(
                          context,
                          en: 'Approval Flow',
                          sw: 'Mtiririko wa Idhini',
                          ar: 'مسار الموافقة',
                        ),
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 12),
                      _DetailLine(
                        '',
                        ((project['approval_flow']
                                    as Map<String, dynamic>?)?['status_label']
                                ?.toString() ??
                            _tr(
                              context,
                              en: 'In Progress',
                              sw: 'Inaendelea',
                              ar: 'قيد التنفيذ',
                            )),
                      ),
                      const SizedBox(height: 12),
                      Text(
                        _tr(
                          context,
                          en: 'Approvals',
                          sw: 'Idhini',
                          ar: 'الموافقات',
                        ),
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 12),
                      Text(
                        ((project['approval_flow']
                                    as Map<String, dynamic>?)?['message']
                                ?.toString() ??
                            ''),
                        style: TextStyle(
                          fontSize: 14,
                          color: cs.onSurface.withValues(alpha: 0.55),
                        ),
                      ),
                      if (_busy) ...[
                        const SizedBox(height: 12),
                        const LinearProgressIndicator(),
                      ],
                      const SizedBox(height: 12),
                      _ApprovalActions(
                        flow:
                            (project['approval_flow']
                                as Map<String, dynamic>?) ??
                            const {},
                        busy: _busy,
                        onAction: (action) => _performAction(action),
                      ),
                      if ((((project['approval_flow']
                                      as Map<String, dynamic>?)?['steps'])
                                  as List?)
                              ?.isNotEmpty ??
                          false) ...[
                        const SizedBox(height: 12),
                        ...(((project['approval_flow']
                                    as Map<String, dynamic>)['steps']
                                as List)
                            .cast<Map<String, dynamic>>()
                            .map((step) => _ApprovalStepCard(step: step))),
                      ],
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _performAction(String action) async {
    String? comment;
    if (action == 'reject' || action == 'return') {
      comment = await _promptForComment(
        action == 'reject'
            ? _tr(
                context,
                en: 'Reject Request',
                sw: 'Kataa Ombi',
                ar: 'رفض الطلب',
              )
            : _tr(
                context,
                en: 'Return Request',
                sw: 'Rudisha Ombi',
                ar: 'إرجاع الطلب',
              ),
        required: true,
      );
      if (comment == null || comment.trim().isEmpty) return;
    } else if (action == 'approve' || action == 'discard') {
      comment = await _promptForComment(
        action == 'approve'
            ? _tr(
                context,
                en: 'Approval Comment',
                sw: 'Maoni ya Idhini',
                ar: 'تعليق الموافقة',
              )
            : _tr(
                context,
                en: 'Discard Request',
                sw: 'Tupa Ombi',
                ar: 'تجاهل الطلب',
              ),
        required: false,
      );
      if (comment == null) return;
    }

    setState(() => _busy = true);
    try {
      final api = ref.read(apiClientProvider);
      late final response;
      switch (action) {
        case 'submit':
          response = await api.post('/projects/${widget.projectId}/submit');
          break;
        case 'approve':
          response = await api.post(
            '/projects/${widget.projectId}/approve',
            data: {'comment': comment},
          );
          break;
        case 'reject':
          response = await api.post(
            '/projects/${widget.projectId}/reject',
            data: {'comment': comment},
          );
          break;
        case 'return':
          response = await api.post(
            '/projects/${widget.projectId}/return',
            data: {'comment': comment},
          );
          break;
        case 'discard':
          response = await api.post(
            '/projects/${widget.projectId}/discard',
            data: {'comment': comment},
          );
          break;
        default:
          return;
      }
      final data = response.data['data'];
      if (data is Map<String, dynamic>) {
        setState(() => _detail = data);
      }
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text((response.data['message'] ?? 'Success').toString()),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              '${_tr(context, en: 'Error', sw: 'Hitilafu', ar: 'خطأ')}: $e',
            ),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<String?> _promptForComment(
    String title, {
    required bool required,
  }) async {
    final controller = TextEditingController();
    return showDialog<String>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(title),
        content: TextField(
          controller: controller,
          maxLines: 4,
          decoration: InputDecoration(
            hintText: required
                ? _tr(
                    context,
                    en: 'Comment is required',
                    sw: 'Maoni yanahitajika',
                    ar: 'التعليق مطلوب',
                  )
                : _tr(
                    context,
                    en: 'Optional comment',
                    sw: 'Maoni ya hiari',
                    ar: 'تعليق اختياري',
                  ),
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, null),
            child: Text(_tr(context, en: 'Cancel', sw: 'Ghairi', ar: 'إلغاء')),
          ),
          TextButton(
            onPressed: () {
              final value = controller.text.trim();
              if (required && value.isEmpty) return;
              Navigator.pop(ctx, value);
            },
            child: Text(
              _tr(context, en: 'Continue', sw: 'Endelea', ar: 'متابعة'),
            ),
          ),
        ],
      ),
    );
  }
}

class _ProjectFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? project;
  const _ProjectFormSheet({this.project});

  @override
  ConsumerState<_ProjectFormSheet> createState() => _ProjectFormSheetState();
}

class _ProjectFormSheetState extends ConsumerState<_ProjectFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _projectName;
  late final TextEditingController _description;
  late final TextEditingController _startDate;
  late final TextEditingController _expectedEndDate;
  late final TextEditingController _actualEndDate;
  late final TextEditingController _contractValue;
  int? _clientId;
  int? _projectTypeId;
  int? _serviceTypeId;
  int? _salespersonId;
  int? _projectManagerId;
  String? _priority;
  bool _loading = false;

  bool get _isNew => widget.project == null;

  @override
  void initState() {
    super.initState();
    final p = widget.project;
    _projectName = TextEditingController(
      text: p?['project_name']?.toString() ?? '',
    );
    _description = TextEditingController(
      text: p?['description']?.toString() ?? '',
    );
    _startDate = TextEditingController(
      text:
          p?['start_date']?.toString() ??
          DateFormat('yyyy-MM-dd').format(DateTime.now()),
    );
    _expectedEndDate = TextEditingController(
      text:
          p?['expected_end_date']?.toString() ??
          DateFormat('yyyy-MM-dd').format(DateTime.now()),
    );
    _actualEndDate = TextEditingController(
      text: p?['actual_end_date']?.toString() ?? '',
    );
    _contractValue = TextEditingController(
      text: p?['contract_value']?.toString() ?? '',
    );
    _clientId = _toInt((p?['client'] as Map<String, dynamic>?)?['id']);
    _projectTypeId = _toInt(
      (p?['project_type'] as Map<String, dynamic>?)?['id'],
    );
    _serviceTypeId = _toInt(
      (p?['service_type'] as Map<String, dynamic>?)?['id'],
    );
    _salespersonId = _toInt(
      (p?['salesperson'] as Map<String, dynamic>?)?['id'],
    );
    _projectManagerId = _toInt(
      (p?['project_manager'] as Map<String, dynamic>?)?['id'],
    );
    _priority = p?['priority']?.toString().isNotEmpty == true
        ? p!['priority'].toString()
        : 'normal';
  }

  @override
  void dispose() {
    _projectName.dispose();
    _description.dispose();
    _startDate.dispose();
    _expectedEndDate.dispose();
    _actualEndDate.dispose();
    _contractValue.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final referenceAsync = ref.watch(_referenceProvider);
    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SafeArea(
        top: false,
        child: referenceAsync.when(
          loading: () => const SizedBox(
            height: 320,
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (e, _) =>
              SizedBox(height: 320, child: Center(child: Text('$e'))),
          data: (reference) => SingleChildScrollView(
            child: Form(
              key: _formKey,
              child: Column(
                children: [
                  _SheetHeader(
                    title: _isNew
                        ? _tr(
                            context,
                            en: 'Create New Project',
                            sw: 'Unda Mradi Mpya',
                            ar: 'إنشاء مشروع جديد',
                          )
                        : _tr(
                            context,
                            en: 'Edit Project',
                            sw: 'Hariri Mradi',
                            ar: 'تعديل المشروع',
                          ),
                    onBack: () => Navigator.pop(context),
                  ),
                  Padding(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      children: [
                        TextFormField(
                          controller: _projectName,
                          decoration: InputDecoration(
                            labelText: _tr(
                              context,
                              en: 'Project Name',
                              sw: 'Jina la Mradi',
                              ar: 'اسم المشروع',
                            ),
                          ),
                          validator: (v) => v == null || v.trim().isEmpty
                              ? _tr(
                                  context,
                                  en: 'Required',
                                  sw: 'Inahitajika',
                                  ar: 'مطلوب',
                                )
                              : null,
                        ),
                        const SizedBox(height: 12),
                        _Drop<int>(
                          label: _tr(
                            context,
                            en: 'Client',
                            sw: 'Mteja',
                            ar: 'العميل',
                          ),
                          value: _clientId,
                          items: (reference['clients'] as List? ?? const [])
                              .cast<Map<String, dynamic>>(),
                          onChanged: (v) => setState(() => _clientId = v),
                        ),
                        _Drop<int>(
                          label: _tr(
                            context,
                            en: 'Project Category',
                            sw: 'Kundi la Mradi',
                            ar: 'فئة المشروع',
                          ),
                          value: _projectTypeId,
                          items:
                              (reference['project_types'] as List? ?? const [])
                                  .cast<Map<String, dynamic>>(),
                          onChanged: (v) => setState(() => _projectTypeId = v),
                        ),
                        _Drop<int>(
                          label: _tr(
                            context,
                            en: 'Service Type',
                            sw: 'Aina ya Huduma',
                            ar: 'نوع الخدمة',
                          ),
                          value: _serviceTypeId,
                          items:
                              (reference['service_types'] as List? ?? const [])
                                  .cast<Map<String, dynamic>>(),
                          onChanged: (v) => setState(() => _serviceTypeId = v),
                        ),
                        _DateField(
                          label: _tr(
                            context,
                            en: 'Start Date',
                            sw: 'Tarehe ya Kuanza',
                            ar: 'تاريخ البدء',
                          ),
                          controller: _startDate,
                        ),
                        _DateField(
                          label: _tr(
                            context,
                            en: 'Expected End Date',
                            sw: 'Tarehe ya Mwisho Inayotarajiwa',
                            ar: 'تاريخ الانتهاء المتوقع',
                          ),
                          controller: _expectedEndDate,
                        ),
                        _DateField(
                          label: _tr(
                            context,
                            en: 'Actual End Date',
                            sw: 'Tarehe Halisi ya Mwisho',
                            ar: 'تاريخ الانتهاء الفعلي',
                          ),
                          controller: _actualEndDate,
                          allowBlank: true,
                        ),
                        TextFormField(
                          controller: _contractValue,
                          decoration: InputDecoration(
                            labelText: _tr(
                              context,
                              en: 'Contract Value (TZS)',
                              sw: 'Thamani ya Mkataba (TZS)',
                              ar: 'قيمة العقد (TZS)',
                            ),
                          ),
                          keyboardType: TextInputType.number,
                        ),
                        const SizedBox(height: 12),
                        _Drop<String>(
                          label: _tr(
                            context,
                            en: 'Priority',
                            sw: 'Kipaumbele',
                            ar: 'الأولوية',
                          ),
                          value: _priority,
                          items: (reference['priorities'] as List? ?? const [])
                              .cast<Map<String, dynamic>>(),
                          onChanged: (v) => setState(() => _priority = v),
                        ),
                        _Drop<int>(
                          label: _tr(
                            context,
                            en: 'Salesperson',
                            sw: 'Muuzaji',
                            ar: 'مندوب المبيعات',
                          ),
                          value: _salespersonId,
                          items:
                              (reference['salespersons'] as List? ?? const [])
                                  .cast<Map<String, dynamic>>(),
                          onChanged: (v) => setState(() => _salespersonId = v),
                        ),
                        _Drop<int>(
                          label: _tr(
                            context,
                            en: 'Project Manager',
                            sw: 'Meneja wa Mradi',
                            ar: 'مدير المشروع',
                          ),
                          value: _projectManagerId,
                          items:
                              (reference['project_managers'] as List? ??
                                      const [])
                                  .cast<Map<String, dynamic>>(),
                          onChanged: (v) =>
                              setState(() => _projectManagerId = v),
                        ),
                        TextFormField(
                          controller: _description,
                          decoration: InputDecoration(
                            labelText: _tr(
                              context,
                              en: 'Description',
                              sw: 'Maelezo',
                              ar: 'الوصف',
                            ),
                          ),
                          maxLines: 2,
                        ),
                        const SizedBox(height: 20),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: _loading ? null : _submit,
                            child: _loading
                                ? const SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  )
                                : Text(
                                    _isNew
                                        ? _tr(
                                            context,
                                            en: 'Create Project',
                                            sw: 'Unda Mradi',
                                            ar: 'إنشاء مشروع',
                                          )
                                        : _tr(
                                            context,
                                            en: 'Update Project',
                                            sw: 'Sasisha Mradi',
                                            ar: 'تحديث المشروع',
                                          ),
                                  ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_clientId == null || _projectTypeId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            _tr(
              context,
              en: 'Client and Project Category are required',
              sw: 'Mteja na Kundi la Mradi vinahitajika',
              ar: 'العميل وفئة المشروع مطلوبان',
            ),
          ),
        ),
      );
      return;
    }
    setState(() => _loading = true);
    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'name': _projectName.text.trim(),
        'client_id': _clientId,
        'project_type_id': _projectTypeId,
        'service_type_id': _serviceTypeId,
        'salesperson_id': _salespersonId,
        'project_manager_id': _projectManagerId,
        'start_date': _startDate.text.trim(),
        'expected_end_date': _expectedEndDate.text.trim(),
        'actual_end_date': _actualEndDate.text.trim().isEmpty
            ? null
            : _actualEndDate.text.trim(),
        'contract_value': _contractValue.text.trim().isEmpty
            ? null
            : double.tryParse(_contractValue.text.trim()),
        'priority': _priority,
        'description': _description.text.trim().isEmpty
            ? null
            : _description.text.trim(),
      };
      if (_isNew) {
        await api.post('/projects', data: data);
      } else {
        await api.put('/projects/${widget.project!['id']}', data: data);
      }
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              '${_tr(context, en: 'Error', sw: 'Hitilafu', ar: 'خطأ')}: $e',
            ),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }
}

class _SheetHeader extends StatelessWidget {
  final String title;
  final VoidCallback onBack;

  const _SheetHeader({required this.title, required this.onBack});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 16),
      decoration: const BoxDecoration(
        color: AppColors.primary,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: Column(
        children: [
          Center(
            child: Container(
              width: 42,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.white38,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: 18),
          Row(
            children: [
              IconButton(
                onPressed: onBack,
                icon: const Icon(Icons.arrow_back_rounded, color: Colors.white),
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
              ),
              Expanded(
                child: Text(
                  title,
                  textAlign: TextAlign.center,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              const SizedBox(width: 48),
            ],
          ),
        ],
      ),
    );
  }
}

class _DateField extends StatelessWidget {
  final String label;
  final TextEditingController controller;
  final bool allowBlank;

  const _DateField({
    required this.label,
    required this.controller,
    this.allowBlank = false,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: TextFormField(
        controller: controller,
        readOnly: true,
        decoration: InputDecoration(
          labelText: label,
          suffixIcon: const Icon(Icons.calendar_today_rounded),
        ),
        validator: allowBlank
            ? null
            : (value) => value == null || value.trim().isEmpty
                  ? _tr(context, en: 'Required', sw: 'Inahitajika', ar: 'مطلوب')
                  : null,
        onTap: () async {
          final initial = controller.text.trim().isNotEmpty
              ? DateTime.tryParse(controller.text.trim()) ?? DateTime.now()
              : DateTime.now();
          final picked = await showDatePicker(
            context: context,
            initialDate: initial,
            firstDate: DateTime(2000),
            lastDate: DateTime(2100),
          );
          if (picked != null) {
            controller.text = DateFormat('yyyy-MM-dd').format(picked);
          }
        },
      ),
    );
  }
}

class _StatusPill extends StatelessWidget {
  final String status;
  const _StatusPill({required this.status});

  @override
  Widget build(BuildContext context) {
    final normalized = status.toUpperCase();
    final color = switch (normalized) {
      'APPROVED' => AppColors.success,
      'COMPLETED' => const Color(0xFF9B59B6),
      'REJECTED' => AppColors.error,
      'PENDING' || 'CREATED' => const Color(0xFFF39C12),
      'IN_PROGRESS' || 'SUBMITTED' => const Color(0xFF3498DB),
      _ => Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.45),
    };
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        normalized.replaceAll('_', ' '),
        style: TextStyle(
          color: color,
          fontSize: 11,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}

class _DetailLine extends StatelessWidget {
  final String label;
  final String value;
  const _DetailLine(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (label.isNotEmpty)
            SizedBox(
              width: 130,
              child: Text(
                label,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: Theme.of(
                    context,
                  ).colorScheme.onSurface.withValues(alpha: 0.55),
                ),
              ),
            ),
          Expanded(
            child: Text(
              value,
              style: TextStyle(
                fontSize: 14,
                color: Theme.of(context).colorScheme.onSurface,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ApprovalActions extends StatelessWidget {
  final Map<String, dynamic> flow;
  final bool busy;
  final ValueChanged<String> onAction;

  const _ApprovalActions({
    required this.flow,
    required this.busy,
    required this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    final buttons = <Widget>[];
    final nextAction = (flow['next_action']?.toString() ?? 'APPROVE')
        .toUpperCase();
    final approveLabel = flow['is_rejected'] == true
        ? 'Re-Approve'
        : _capitalize(nextAction.toLowerCase());

    if (flow['can_be_submitted'] == true) {
      buttons.add(
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: busy ? null : () => onAction('submit'),
            child: Text(
              _tr(context, en: 'Submit', sw: 'Wasilisha', ar: 'إرسال'),
            ),
          ),
        ),
      );
    }

    if (flow['can_be_approved'] == true) {
      buttons.add(
        Wrap(
          spacing: 10,
          runSpacing: 10,
          children: [
            if (flow['can_be_discarded'] == true)
              OutlinedButton(
                onPressed: busy ? null : () => onAction('discard'),
                child: Text(
                  _tr(context, en: 'Discard', sw: 'Tupa', ar: 'تجاهل'),
                ),
              )
            else ...[
              if (flow['can_be_rejected'] == true)
                OutlinedButton(
                  onPressed: busy ? null : () => onAction('reject'),
                  child: Text(
                    _tr(context, en: 'Reject', sw: 'Kataa', ar: 'رفض'),
                  ),
                ),
              if (flow['can_be_returned'] == true)
                OutlinedButton(
                  onPressed: busy ? null : () => onAction('return'),
                  child: Text(
                    _tr(context, en: 'Return', sw: 'Rudisha', ar: 'إرجاع'),
                  ),
                ),
            ],
            ElevatedButton(
              onPressed: busy ? null : () => onAction('approve'),
              child: Text(approveLabel),
            ),
          ],
        ),
      );
    }

    if (buttons.isEmpty) {
      return const SizedBox.shrink();
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: buttons
          .map(
            (button) => Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: button,
            ),
          )
          .toList(),
    );
  }
}

class _ApprovalStepCard extends StatelessWidget {
  final Map<String, dynamic> step;
  const _ApprovalStepCard({required this.step});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '${step['approver_name'] ?? '-'}',
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 4),
          Text(
            '${step['role_name'] ?? '-'}',
            style: TextStyle(
              fontSize: 12,
              color: Theme.of(
                context,
              ).colorScheme.onSurface.withValues(alpha: 0.55),
            ),
          ),
          if ((step['date']?.toString().isNotEmpty ?? false)) ...[
            const SizedBox(height: 4),
            Text(
              '${step['date']}',
              style: TextStyle(
                fontSize: 12,
                color: Theme.of(
                  context,
                ).colorScheme.onSurface.withValues(alpha: 0.55),
              ),
            ),
          ],
          if ((step['comment']?.toString().isNotEmpty ?? false)) ...[
            const SizedBox(height: 4),
            Text(
              '${step['comment']}',
              style: TextStyle(
                fontSize: 12,
                color: Theme.of(context).colorScheme.onSurface,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

double _toDouble(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse('$value') ?? 0;
}

int? _toInt(dynamic value) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  return int.tryParse('$value');
}

String _formatDate(String? value) {
  if (value == null || value.isEmpty) return '-';
  try {
    return DateFormat('yyyy-MM-dd').format(DateTime.parse(value));
  } catch (_) {
    return value;
  }
}

String _money(dynamic value) {
  final amount = _toDouble(value);
  if (amount <= 0) return '-';
  return 'TZS ${NumberFormat('#,##0.00').format(amount)}';
}

String _display(dynamic value) {
  if (value == null || '$value'.isEmpty) return '-';
  return value.toString();
}

String _capitalize(String value) {
  if (value.isEmpty) return value;
  return value[0].toUpperCase() + value.substring(1);
}

String _tr(
  BuildContext context, {
  required String en,
  required String sw,
  required String ar,
}) {
  final code = Localizations.localeOf(context).languageCode.toLowerCase();
  if (code == 'ar') return ar;
  if (code == 'sw') return sw;
  return en;
}
