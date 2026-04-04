import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _projectReportsSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);

class _ReportFilter {
  final DateTime? startDate;
  final DateTime? endDate;
  final int? projectId;

  _ReportFilter({this.startDate, this.endDate, this.projectId});

  _ReportFilter copyWith({
    DateTime? startDate,
    DateTime? endDate,
    int? projectId,
    bool clearStart = false,
    bool clearEnd = false,
    bool clearProject = false,
  }) {
    return _ReportFilter(
      startDate: clearStart ? null : (startDate ?? this.startDate),
      endDate: clearEnd ? null : (endDate ?? this.endDate),
      projectId: clearProject ? null : (projectId ?? this.projectId),
    );
  }
}

final _projectReportsFilterProvider = StateProvider.autoDispose<_ReportFilter>(
  (ref) => _ReportFilter(),
);

final _projectReportsProjectsProvider =
    FutureProvider.autoDispose<List<dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get('/project-daily-reports/projects');
        return response.data['data'] as List? ?? [];
      } catch (_) {
        return const [];
      }
    });

final _projectReportsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      try {
        final response = await api.get(
          '/project-daily-reports',
          queryParameters: {'per_page': 100},
        );
        final rawItems = response.data['data'] as List? ?? const [];
        final items = rawItems.whereType<Map>().map((item) {
          final map = Map<String, dynamic>.from(item);
          return {
            ...map,
            'project_name': map['project_name']?.toString() ?? '-',
            'summary':
                map['work_completed']?.toString() ??
                map['issues_faced']?.toString() ??
                map['materials_used']?.toString() ??
                '-',
            'author_name':
                map['supervisor_name']?.toString() ??
                '-',
            'status': 'CREATED',
            'type': 'project_daily_report',
            'report_date': map['report_date']?.toString() ?? '',
          };
        }).toList();

        final meta = response.data['meta'] as Map<String, dynamic>? ?? const {};
        return {
          'items': items,
          'meta': {
            ...meta,
            'total': meta['total'] ?? items.length,
            'daily_reports': items.length,
            'site_visits': 0,
          },
          'unavailable_on_live': false,
        };
      } catch (e) {
        if ('$e'.contains('404')) {
          return {
            'items': const <Map<String, dynamic>>[],
            'meta': const <String, dynamic>{},
            'unavailable_on_live': true,
          };
        }
        rethrow;
      }
    });

class ProjectReportsScreen extends ConsumerStatefulWidget {
  const ProjectReportsScreen({super.key});

  @override
  ConsumerState<ProjectReportsScreen> createState() =>
      _ProjectReportsScreenState();
}

class _ProjectReportsScreenState extends ConsumerState<ProjectReportsScreen> {
  void _openReportForm({Map<String, dynamic>? report}) {
    showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _ProjectDailyReportFormSheet(report: report),
    ).then((result) {
      if (result == true) {
        ref.invalidate(_projectReportsProvider);
      }
    });
  }

  void _openReportDetails(Map<String, dynamic> report) {
    final id = report['id'] as int?;
    if (id == null) return;
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => _ProjectDailyReportDetailSheet(
        reportId: id,
        fallback: report,
        onEdit: () {
          Navigator.pop(context);
          _openReportForm(report: report);
        },
        onDeleted: () => ref.invalidate(_projectReportsProvider),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final reportsAsync = ref.watch(_projectReportsProvider);
    final projectsAsync = ref.watch(_projectReportsProjectsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final filter = ref.watch(_projectReportsFilterProvider);
    final search = ref
        .watch(_projectReportsSearchProvider)
        .trim()
        .toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(
          isSwahili ? 'Ripoti za Kila Siku za Miradi' : 'Project Daily Reports',
        ),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _openReportForm(),
          child: const Icon(Icons.add_rounded),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_projectReportsProvider),
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    TextField(
                      onChanged: (value) =>
                          ref
                                  .read(_projectReportsSearchProvider.notifier)
                                  .state =
                              value,
                      decoration: InputDecoration(
                        hintText: isSwahili
                            ? 'Tafuta ripoti...'
                            : 'Search reports...',
                        prefixIcon: const Icon(Icons.search_rounded),
                        suffixIcon: search.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear),
                                onPressed: () =>
                                    ref
                                            .read(
                                              _projectReportsSearchProvider
                                                  .notifier,
                                            )
                                            .state =
                                        '',
                              )
                            : null,
                        filled: true,
                        fillColor: isDarkMode
                            ? const Color(0xFF2A2A3E)
                            : Colors.white,
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide.none,
                        ),
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 16,
                          vertical: 12,
                        ),
                      ),
                    ),
                    const SizedBox(height: 12),
                    projectsAsync.when(
                      loading: () => const SizedBox.shrink(),
                      error: (_, __) => const SizedBox.shrink(),
                      data: (projects) => (projects as List).isEmpty
                          ? const SizedBox.shrink()
                          : _ReportFilters(
                              projects: projects,
                              filter: filter,
                              isSwahili: isSwahili,
                              isDarkMode: isDarkMode,
                            ),
                    ),
                  ],
                ),
              ),
            ),
            reportsAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (e, _) => SliverFillRemaining(
                child: _ReportsErrorView(
                  error: '$e',
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_projectReportsProvider),
                ),
              ),
              data: (payload) {
                if (payload['unavailable_on_live'] == true) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.assessment_outlined,
                              size: 64,
                              color: Colors.grey[400],
                            ),
                            const SizedBox(height: 16),
                            Text(
                              isSwahili
                                  ? 'Project Daily Reports haipatikani kwenye live API kwa sasa.'
                                  : 'Project Daily Reports is not available on the live API right now.',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                fontSize: 16,
                                color: Colors.grey[700],
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                }

                final allItems = (payload['items'] as List)
                    .cast<Map<String, dynamic>>();
                final meta = payload['meta'] as Map<String, dynamic>;

                final items = search.isEmpty
                    ? allItems
                    : allItems.where((item) {
                        final haystack = [
                          item['project_name'] ?? '',
                          item['summary'] ?? '',
                          item['author_name'] ?? '',
                          item['report_date'] ?? '',
                          item['type'] ?? '',
                        ].join(' ').toLowerCase();
                        return haystack.contains(search);
                      }).toList();

                if (items.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.assessment_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            allItems.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna ripoti za kila siku za miradi'
                                      : 'No project daily reports found')
                                : (isSwahili
                                      ? 'Hakuna matokeo yanayolingana'
                                      : 'No reports match your search'),
                            style: TextStyle(
                              fontSize: 16,
                              color: Colors.grey[600],
                            ),
                          ),
                          if (search.isNotEmpty) ...[
                            const SizedBox(height: 16),
                            ElevatedButton.icon(
                              onPressed: () =>
                                  ref
                                          .read(
                                            _projectReportsSearchProvider
                                                .notifier,
                                          )
                                          .state =
                                      '',
                              icon: const Icon(Icons.arrow_back_rounded),
                              label: Text(isSwahili ? 'Rudi' : 'Back'),
                            ),
                          ],
                        ],
                      ),
                    ),
                  );
                }

                return SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                  sliver: SliverList(
                    delegate: SliverChildListDelegate([
                      Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: Row(
                          children: [
                            Expanded(
                              child: _StatChip(
                                label: isSwahili ? 'Jumla' : 'Total',
                                value: '${meta['total'] ?? items.length}',
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: _StatChip(
                                label: isSwahili ? 'Ripoti za Siku' : 'Daily',
                                value: '${meta['daily_reports'] ?? 0}',
                              ),
                            ),
                            const SizedBox(width: 8),
                            Expanded(
                              child: _StatChip(
                                label: isSwahili ? 'Ziara' : 'Visits',
                                value: '${meta['site_visits'] ?? 0}',
                              ),
                            ),
                          ],
                        ),
                      ),
                      ...items.map(
                        (item) => _ReportItemCard(
                          item: item,
                          isSwahili: isSwahili,
                          isDarkMode: isDarkMode,
                          onTap: () => _openReportDetails(item),
                          onEdit: () => _openReportForm(report: item),
                          onDelete: () => _deleteReport(item),
                        ),
                      ),
                      const SizedBox(height: 80),
                    ]),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _deleteReport(Map<String, dynamic> item) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final id = item['id'] as int?;
    if (id == null) return;
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(
          isSwahili ? 'Futa Ripoti ya Mradi' : 'Delete Project Daily Report',
        ),
        content: Text(
          isSwahili
              ? 'Una uhakika unataka kufuta ripoti hii?'
              : 'Are you sure you want to delete this report?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(isSwahili ? 'Futa' : 'Delete'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;
    try {
      final api = ref.read(apiClientProvider);
      await api.delete('/project-daily-reports/$id');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              isSwahili ? 'Ripoti imefutwa' : 'Report deleted successfully',
            ),
            backgroundColor: AppColors.success,
          ),
        );
      }
      ref.invalidate(_projectReportsProvider);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: $e'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }
}

class _ReportFilters extends ConsumerWidget {
  final List projects;
  final _ReportFilter filter;
  final bool isSwahili;
  final bool isDarkMode;

  const _ReportFilters({
    required this.projects,
    required this.filter,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return ExpansionTile(
      title: Text(isSwahili ? 'Vichungi' : 'Filters'),
      initiallyExpanded:
          filter.projectId != null ||
          filter.startDate != null ||
          filter.endDate != null,
      childrenPadding: const EdgeInsets.fromLTRB(0, 0, 0, 8),
      backgroundColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
      collapsedBackgroundColor: isDarkMode
          ? const Color(0xFF2A2A3E)
          : Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      collapsedShape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(14),
      ),
      children: [
        _Drop<int>(
          label: isSwahili ? 'Mradi' : 'Project',
          value: filter.projectId,
          items: projects.cast<Map<String, dynamic>>(),
          onChanged: (v) =>
              ref.read(_projectReportsFilterProvider.notifier).state = filter
                  .copyWith(projectId: v, clearProject: v == null),
          displayField: 'project_name',
        ),
        Row(
          children: [
            Expanded(
              child: InkWell(
                onTap: () async {
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: filter.startDate ?? DateTime.now(),
                    firstDate: DateTime(2020),
                    lastDate: DateTime.now(),
                  );
                  if (picked != null)
                    ref.read(_projectReportsFilterProvider.notifier).state =
                        filter.copyWith(startDate: picked);
                },
                child: Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Colors.grey[100],
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.calendar_today, size: 20),
                      const SizedBox(width: 12),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            isSwahili ? 'Tarehe ya Kuanza' : 'Start Date',
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.grey[600],
                            ),
                          ),
                          Text(
                            filter.startDate != null
                                ? DateFormat(
                                    'dd MMM yyyy',
                                  ).format(filter.startDate!)
                                : '-',
                            style: const TextStyle(fontSize: 14),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: InkWell(
                onTap: () async {
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: filter.endDate ?? DateTime.now(),
                    firstDate: filter.startDate ?? DateTime(2020),
                    lastDate: DateTime.now().add(const Duration(days: 365)),
                  );
                  if (picked != null)
                    ref.read(_projectReportsFilterProvider.notifier).state =
                        filter.copyWith(endDate: picked);
                },
                child: Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: Colors.grey[100],
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.calendar_today, size: 20),
                      const SizedBox(width: 12),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            isSwahili ? 'Tarehe ya Mwisho' : 'End Date',
                            style: TextStyle(
                              fontSize: 11,
                              color: Colors.grey[600],
                            ),
                          ),
                          Text(
                            filter.endDate != null
                                ? DateFormat(
                                    'dd MMM yyyy',
                                  ).format(filter.endDate!)
                                : '-',
                            style: const TextStyle(fontSize: 14),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
        if (filter.projectId != null ||
            filter.startDate != null ||
            filter.endDate != null)
          OutlinedButton(
            onPressed: () =>
                ref.read(_projectReportsFilterProvider.notifier).state =
                    _ReportFilter(),
            child: Text(isSwahili ? 'Futa' : 'Clear'),
          ),
      ],
    );
  }
}

class _Drop<T> extends StatelessWidget {
  final String label;
  final T? value;
  final List<Map<String, dynamic>> items;
  final void Function(T?) onChanged;
  final String displayField;

  const _Drop({
    required this.label,
    required this.value,
    required this.items,
    required this.onChanged,
    required this.displayField,
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
            child: const Text('All', overflow: TextOverflow.ellipsis),
          ),
          ...items.map(
            (item) => DropdownMenuItem<T>(
              value: item['id'] as T,
              child: Text(
                item[displayField]?.toString() ?? '-',
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

class _StatChip extends StatelessWidget {
  final String label;
  final String value;

  const _StatChip({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFF3498DB).withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        children: [
          Text(
            value,
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: const TextStyle(
              fontSize: 12,
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}

class _ReportItemCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onTap;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _ReportItemCard({
    required this.item,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onTap,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final isVisit = item['type'] == 'site_visit';
    final accent = isVisit ? const Color(0xFFF39C12) : const Color(0xFF1ABC9C);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 6,
                  ),
                  decoration: BoxDecoration(
                    color: accent.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    isVisit
                        ? (isSwahili ? 'Ziara ya Tovuti' : 'Site Visit')
                        : (isSwahili
                              ? 'Ripoti ya Kila Siku ya Mradi'
                              : 'Project Daily Report'),
                    style: TextStyle(
                      color: accent,
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
                const Spacer(),
                PopupMenuButton<String>(
                  onSelected: (value) {
                    if (value == 'view') onTap();
                    if (value == 'edit') onEdit();
                    if (value == 'delete') onDelete();
                  },
                  itemBuilder: (_) => [
                    PopupMenuItem(
                      value: 'view',
                      child: Text(isSwahili ? 'Tazama' : 'View'),
                    ),
                    PopupMenuItem(
                      value: 'edit',
                      child: Text(isSwahili ? 'Hariri' : 'Edit'),
                    ),
                    PopupMenuItem(
                      value: 'delete',
                      child: Text(isSwahili ? 'Futa' : 'Delete'),
                    ),
                  ],
                ),
                Text(
                  item['report_date'] as String? ?? '-',
                  style: TextStyle(
                    fontSize: 12,
                    color: isDarkMode
                        ? Colors.white54
                        : AppColors.textSecondary,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Text(
              item['project_name'] as String? ?? '-',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: isDarkMode ? Colors.white : AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              item['summary'] as String? ?? '-',
              maxLines: 3,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                Icon(Icons.person_outline, size: 16, color: accent),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    item['author_name'] as String? ?? '-',
                    style: TextStyle(
                      fontSize: 12,
                      color: isDarkMode
                          ? Colors.white70
                          : AppColors.textPrimary,
                    ),
                  ),
                ),
                Text(
                  item['status'] as String? ?? '-',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: accent,
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
      ),
    );
  }
}

class _ProjectDailyReportDetailSheet extends ConsumerWidget {
  final int reportId;
  final Map<String, dynamic> fallback;
  final VoidCallback onEdit;
  final VoidCallback onDeleted;

  const _ProjectDailyReportDetailSheet({
    required this.reportId,
    required this.fallback,
    required this.onEdit,
    required this.onDeleted,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);
    return FutureBuilder<Map<String, dynamic>>(
      future: () async {
        try {
          final api = ref.read(apiClientProvider);
          final response = await api.get('/project-daily-reports/$reportId');
          return Map<String, dynamic>.from(
            response.data['data'] as Map? ?? fallback,
          );
        } catch (_) {
          return fallback;
        }
      }(),
      builder: (context, snapshot) {
        final data = snapshot.data ?? fallback;
        return Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          child: SafeArea(
            top: false,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                _ProjectDailyReportHeader(
                  title: isSwahili
                      ? 'Maelezo ya Ripoti ya Mradi'
                      : 'Project Daily Report Details',
                  onBack: () => Navigator.pop(context),
                ),
                Flexible(
                  child: ListView(
                    padding: const EdgeInsets.all(20),
                    shrinkWrap: true,
                    children: [
                      _ReportDetailRow(
                        'Project',
                        data['project_name']?.toString() ?? '-',
                      ),
                      _ReportDetailRow(
                        'Supervisor',
                        data['supervisor_name']?.toString() ?? '-',
                      ),
                      _ReportDetailRow(
                        'Report Date',
                        data['report_date']?.toString() ?? '-',
                      ),
                      _ReportDetailRow(
                        'Weather Conditions',
                        data['weather_conditions']?.toString() ?? '-',
                      ),
                      _ReportDetailRow(
                        'Work Completed',
                        data['work_completed']?.toString() ?? '-',
                      ),
                      _ReportDetailRow(
                        'Materials Used',
                        data['materials_used']?.toString() ?? '-',
                      ),
                      _ReportDetailRow(
                        'Labor Hours',
                        data['labor_hours']?.toString() ?? '-',
                      ),
                      _ReportDetailRow(
                        'Issues Faced',
                        data['issues_faced']?.toString() ?? '-',
                      ),
                      const SizedBox(height: 20),
                      Row(
                        children: [
                          Expanded(
                            child: OutlinedButton(
                              onPressed: onEdit,
                              child: Text(isSwahili ? 'Hariri' : 'Edit'),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: ElevatedButton(
                              onPressed: () async {
                                Navigator.pop(context);
                                onDeleted();
                              },
                              child: Text(isSwahili ? 'Funga' : 'Close'),
                            ),
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
      },
    );
  }
}

class _ProjectDailyReportFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? report;
  const _ProjectDailyReportFormSheet({this.report});

  @override
  ConsumerState<_ProjectDailyReportFormSheet> createState() =>
      _ProjectDailyReportFormSheetState();
}

class _ProjectDailyReportFormSheetState
    extends ConsumerState<_ProjectDailyReportFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _reportDate;
  late final TextEditingController _weatherConditions;
  late final TextEditingController _workCompleted;
  late final TextEditingController _materialsUsed;
  late final TextEditingController _laborHours;
  late final TextEditingController _issuesFaced;
  int? _projectId;
  bool _loading = false;

  bool get _isNew => widget.report == null;

  @override
  void initState() {
    super.initState();
    final report = widget.report;
    _projectId = _toInt(report?['project_id']);
    _reportDate = TextEditingController(
      text: report?['report_date']?.toString() ??
          DateFormat('yyyy-MM-dd').format(DateTime.now()),
    );
    _weatherConditions = TextEditingController(
      text: report?['weather_conditions']?.toString() ?? '',
    );
    _workCompleted = TextEditingController(
      text: report?['work_completed']?.toString() ?? '',
    );
    _materialsUsed = TextEditingController(
      text: report?['materials_used']?.toString() ?? '',
    );
    _laborHours = TextEditingController(
      text: report?['labor_hours']?.toString() ?? '',
    );
    _issuesFaced = TextEditingController(
      text: report?['issues_faced']?.toString() ?? '',
    );
  }

  @override
  void dispose() {
    _reportDate.dispose();
    _weatherConditions.dispose();
    _workCompleted.dispose();
    _materialsUsed.dispose();
    _laborHours.dispose();
    _issuesFaced.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final projectsAsync = ref.watch(_projectReportsProjectsProvider);
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
      child: SafeArea(
        top: false,
        child: projectsAsync.when(
          loading: () => const SizedBox(
            height: 320,
            child: Center(child: CircularProgressIndicator()),
          ),
          error: (e, _) => SizedBox(height: 320, child: Center(child: Text('$e'))),
          data: (projects) => SingleChildScrollView(
            child: Form(
              key: _formKey,
              child: Column(
                children: [
                  _ProjectDailyReportHeader(
                    title: _isNew
                        ? (isSwahili
                              ? 'Ripoti Mpya ya Mradi'
                              : 'New Project Daily Report')
                        : (isSwahili
                              ? 'Hariri Ripoti ya Mradi'
                              : 'Edit Project Daily Report'),
                    onBack: () => Navigator.pop(context),
                  ),
                  Padding(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      children: [
                        _Drop<int>(
                          label: 'Project',
                          value: _projectId,
                          items: (projects as List)
                              .cast<Map<String, dynamic>>(),
                          onChanged: (v) => setState(() => _projectId = v),
                          displayField: 'project_name',
                        ),
                        _DateInput(
                          label: 'Report Date',
                          controller: _reportDate,
                        ),
                        TextFormField(
                          controller: _weatherConditions,
                          decoration: const InputDecoration(
                            labelText: 'Weather Conditions',
                          ),
                        ),
                        const SizedBox(height: 12),
                        TextFormField(
                          controller: _workCompleted,
                          decoration: const InputDecoration(
                            labelText: 'Work Completed',
                          ),
                          maxLines: 3,
                        ),
                        const SizedBox(height: 12),
                        TextFormField(
                          controller: _materialsUsed,
                          decoration: const InputDecoration(
                            labelText: 'Materials Used',
                          ),
                          maxLines: 3,
                        ),
                        const SizedBox(height: 12),
                        TextFormField(
                          controller: _laborHours,
                          decoration: const InputDecoration(
                            labelText: 'Labor Hours',
                          ),
                          keyboardType: TextInputType.number,
                        ),
                        const SizedBox(height: 12),
                        TextFormField(
                          controller: _issuesFaced,
                          decoration: const InputDecoration(
                            labelText: 'Issues Faced',
                          ),
                          maxLines: 3,
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
                                        ? (isSwahili ? 'Hifadhi' : 'Save')
                                        : (isSwahili ? 'Sasisha' : 'Update'),
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
    if (_projectId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Project is required')),
      );
      return;
    }
    setState(() => _loading = true);
    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'project_id': _projectId,
        'report_date': _reportDate.text.trim(),
        'weather_conditions': _weatherConditions.text.trim().isEmpty
            ? null
            : _weatherConditions.text.trim(),
        'work_completed': _workCompleted.text.trim().isEmpty
            ? null
            : _workCompleted.text.trim(),
        'materials_used': _materialsUsed.text.trim().isEmpty
            ? null
            : _materialsUsed.text.trim(),
        'labor_hours': _laborHours.text.trim().isEmpty
            ? null
            : int.tryParse(_laborHours.text.trim()),
        'issues_faced': _issuesFaced.text.trim().isEmpty
            ? null
            : _issuesFaced.text.trim(),
      };
      if (_isNew) {
        await api.post('/project-daily-reports', data: data);
      } else {
        await api.put(
          '/project-daily-reports/${widget.report!['id']}',
          data: data,
        );
      }
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: $e'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }
}

class _ProjectDailyReportHeader extends StatelessWidget {
  final String title;
  final VoidCallback onBack;

  const _ProjectDailyReportHeader({
    required this.title,
    required this.onBack,
  });

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

class _DateInput extends StatelessWidget {
  final String label;
  final TextEditingController controller;
  const _DateInput({required this.label, required this.controller});

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
        onTap: () async {
          final initial = DateTime.tryParse(controller.text.trim()) ?? DateTime.now();
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

class _ReportDetailRow extends StatelessWidget {
  final String label;
  final String value;
  const _ReportDetailRow(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 130,
            child: Text(
              label,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w700,
                color: AppColors.textSecondary,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(fontSize: 14, color: AppColors.textPrimary),
            ),
          ),
        ],
      ),
    );
  }
}

int? _toInt(dynamic value) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  return int.tryParse('$value');
}

class _ReportsErrorView extends StatelessWidget {
  final String error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ReportsErrorView({
    required this.error,
    required this.isSwahili,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(32),
      children: [
        const SizedBox(height: 100),
        const Icon(Icons.error_outline, size: 64, color: AppColors.error),
        const SizedBox(height: 16),
        Text(
          isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 8),
        Text(
          error,
          textAlign: TextAlign.center,
          style: const TextStyle(color: AppColors.textSecondary),
        ),
        const SizedBox(height: 24),
        Center(
          child: ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh),
            label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
          ),
        ),
      ],
    );
  }
}
