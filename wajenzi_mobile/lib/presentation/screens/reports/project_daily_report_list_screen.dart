import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _dailyReportsSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);

final projectDailyReportFilterProvider =
    StateProvider.autoDispose<ReportFilter>((ref) => ReportFilter());

class ReportFilter {
  final DateTime? startDate;
  final DateTime? endDate;
  final int? projectId;

  ReportFilter({this.startDate, this.endDate, this.projectId});

  ReportFilter copyWith({
    DateTime? startDate,
    DateTime? endDate,
    int? projectId,
    bool clearStart = false,
    bool clearEnd = false,
    bool clearProject = false,
  }) {
    return ReportFilter(
      startDate: clearStart ? null : (startDate ?? this.startDate),
      endDate: clearEnd ? null : (endDate ?? this.endDate),
      projectId: clearProject ? null : (projectId ?? this.projectId),
    );
  }

  Map<String, String> toQueryParams() {
    final params = <String, String>{};
    if (startDate != null)
      params['start_date'] = DateFormat('yyyy-MM-dd').format(startDate!);
    if (endDate != null)
      params['end_date'] = DateFormat('yyyy-MM-dd').format(endDate!);
    if (projectId != null) params['project_id'] = projectId.toString();
    return params;
  }
}

final _projectDailyReportsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final filter = ref.watch(projectDailyReportFilterProvider);
      final response = await api.get(
        '/project-daily-reports',
        queryParameters: filter.toQueryParams(),
      );
      final data = response.data['data'] as List? ?? [];

      return {'items': data, 'total': data.length};
    });

final _projectDailyReportProjectsProvider =
    FutureProvider.autoDispose<List<dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/project-daily-reports/projects');
      return response.data['data'] as List? ?? [];
    });

final _projectDailyReportDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/project-daily-reports/$id');
      return response.data['data'] as Map<String, dynamic>? ?? {};
    });

class ProjectDailyReportListScreen extends ConsumerStatefulWidget {
  const ProjectDailyReportListScreen({super.key});

  @override
  ConsumerState<ProjectDailyReportListScreen> createState() =>
      _ProjectDailyReportListScreenState();
}

class _ProjectDailyReportListScreenState
    extends ConsumerState<ProjectDailyReportListScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final reportsAsync = ref.watch(_projectDailyReportsProvider);
    final projectsAsync = ref.watch(_projectDailyReportProjectsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final filter = ref.watch(projectDailyReportFilterProvider);
    final search = ref.watch(_dailyReportsSearchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(
          isSwahili ? 'Ripoti za Mradi kwa Siku' : 'Project Daily Reports',
        ),
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _showReportForm(context),
          child: const Icon(Icons.add_rounded),
          tooltip: isSwahili ? 'Ongeza' : 'Add',
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_projectDailyReportsProvider),
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
                          ref.read(_dailyReportsSearchProvider.notifier).state =
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
                                              _dailyReportsSearchProvider
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
                      data: (projects) => _ReportFilters(
                        projects: projects as List,
                        filter: filter,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                      ),
                    ),
                  ],
                ),
              ),
            ),
            SliverToBoxAdapter(
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: reportsAsync.when(
                  loading: () => const SizedBox(
                    height: 60,
                    child: Center(child: CircularProgressIndicator()),
                  ),
                  error: (_, __) => const SizedBox.shrink(),
                  data: (payload) {
                    final total = payload['total'] as int;
                    return Row(
                      children: [
                        Expanded(
                          child: _StatCard(
                            title: isSwahili
                                ? 'Jumla ya Ripoti'
                                : 'Total Reports',
                            value: '$total',
                            icon: Icons.assignment,
                            color: const Color(0xFF3498DB),
                            isDarkMode: isDarkMode,
                          ),
                        ),
                      ],
                    );
                  },
                ),
              ),
            ),
            const SliverToBoxAdapter(child: SizedBox(height: 16)),
            reportsAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (e, _) => SliverFillRemaining(
                child: _ErrorView(
                  error: e,
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_projectDailyReportsProvider),
                ),
              ),
              data: (payload) {
                final allItems = (payload['items'] as List)
                    .cast<Map<String, dynamic>>();
                final reports = search.isEmpty
                    ? allItems
                    : allItems.where((report) {
                        final haystack = [
                          report['project_name'] ?? '',
                          report['supervisor_name'] ?? '',
                          report['weather_conditions'] ?? '',
                          report['work_completed'] ?? '',
                          report['report_date'] ?? '',
                        ].join(' ').toLowerCase();
                        return haystack.contains(search);
                      }).toList();

                if (reports.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.assignment_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            allItems.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna ripoti zilizopatikana'
                                      : 'No reports found')
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
                                            _dailyReportsSearchProvider
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
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate((context, index) {
                      return _ReportCard(
                        report: reports[index],
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onTap: () => _showReportDetail(context, reports[index]),
                        onEdit: () =>
                            _showReportForm(context, report: reports[index]),
                        onDelete: () =>
                            _deleteReport(context, ref, reports[index]),
                      );
                    }, childCount: reports.length),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  void _showFilterSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => _FilterSheet(parentRef: ref),
    );
  }

  void _showReportForm(BuildContext context, {Map<String, dynamic>? report}) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => _ReportFormSheet(report: report),
    ).then((result) {
      if (result == true) ref.invalidate(_projectDailyReportsProvider);
    });
  }

  void _showReportDetail(BuildContext context, Map<String, dynamic> report) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (ctx) => _ReportDetailSheet(report: report),
    );
  }

  Future<void> _deleteReport(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> report,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final isDarkMode = ref.read(isDarkModeProvider);

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        title: Text(
          isSwahili ? 'Thibitisha Kufuta' : 'Confirm Delete',
          style: TextStyle(
            color: isDarkMode ? Colors.white : AppColors.textPrimary,
          ),
        ),
        content: Text(
          isSwahili
              ? 'Je, una uhakika unataka kufuta ripoti hii?'
              : 'Are you sure you want to delete this report?',
          style: TextStyle(
            color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(isSwahili ? 'Cancel' : 'Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(
              isSwahili ? 'Futa' : 'Delete',
              style: const TextStyle(color: Colors.red),
            ),
          ),
        ],
      ),
    );

    if (confirmed == true && context.mounted) {
      try {
        final api = ref.read(apiClientProvider);
        await api.delete('/project-daily-reports/${report['id']}');
        ref.invalidate(_projectDailyReportsProvider);
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(isSwahili ? 'Ripoti imefutwa' : 'Report deleted'),
              backgroundColor: Colors.green,
            ),
          );
        }
      } catch (e) {
        if (context.mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
          );
        }
      }
    }
  }
}

class _ReportFilters extends ConsumerWidget {
  final List projects;
  final ReportFilter filter;
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
              ref.read(projectDailyReportFilterProvider.notifier).state = filter
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
                    ref.read(projectDailyReportFilterProvider.notifier).state =
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
                                ? _formatDateStr(filter.startDate!.toString())
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
                    ref.read(projectDailyReportFilterProvider.notifier).state =
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
                                ? _formatDateStr(filter.endDate!.toString())
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
                ref.read(projectDailyReportFilterProvider.notifier).state =
                    ReportFilter(),
            child: Text(isSwahili ? 'Futa' : 'Clear'),
          ),
      ],
    );
  }

  String _formatDateStr(String date) {
    if (date.isEmpty) return '-';
    try {
      return DateFormat('dd MMM yyyy').format(DateTime.parse(date));
    } catch (_) {
      return date;
    }
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

class _StatCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final Color color;
  final bool isDarkMode;

  const _StatCard({
    required this.title,
    required this.value,
    required this.icon,
    required this.color,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Row(
        children: [
          Icon(icon, color: color, size: 24),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                value,
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: isDarkMode ? Colors.white : color,
                ),
              ),
              Text(
                title,
                style: TextStyle(
                  fontSize: 11,
                  color: isDarkMode ? Colors.white54 : Colors.grey[600],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _FilterSheet extends ConsumerWidget {
  final WidgetRef parentRef;

  const _FilterSheet({required this.parentRef});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final filter = ref.watch(projectDailyReportFilterProvider);
    final projectsAsync = ref.watch(_projectDailyReportProjectsProvider);

    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 42,
                  height: 4,
                  decoration: BoxDecoration(
                    color: isDarkMode ? Colors.white24 : Colors.grey[300],
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 18),
              Text(
                isSwahili ? 'Chuja Ripoti' : 'Filter Reports',
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w700,
                  color: isDarkMode ? Colors.white : AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: 24),
              Text(
                isSwahili ? 'Mradi' : 'Project',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12),
                decoration: BoxDecoration(
                  color: isDarkMode
                      ? const Color(0xFF2A2A3E)
                      : Colors.grey[100],
                  borderRadius: BorderRadius.circular(12),
                ),
                child: projectsAsync.when(
                  loading: () => const Padding(
                    padding: EdgeInsets.all(16),
                    child: CircularProgressIndicator(),
                  ),
                  error: (_, __) => Padding(
                    padding: const EdgeInsets.all(16),
                    child: Text(isSwahili ? 'Imeshindikana' : 'Failed'),
                  ),
                  data: (projects) => DropdownButtonHideUnderline(
                    child: DropdownButton<int?>(
                      value: filter.projectId,
                      hint: Text(isSwahili ? 'All Projects' : 'All Projects'),
                      isExpanded: true,
                      dropdownColor: isDarkMode
                          ? const Color(0xFF2A2A3E)
                          : Colors.white,
                      items: [
                        DropdownMenuItem(
                          value: null,
                          child: Text(
                            isSwahili ? 'All Projects' : 'All Projects',
                          ),
                        ),
                        ...projects.map(
                          (p) => DropdownMenuItem(
                            value: p['id'] as int,
                            child: Text(p['project_name'] as String? ?? '-'),
                          ),
                        ),
                      ],
                      onChanged: (v) =>
                          parentRef
                              .read(projectDailyReportFilterProvider.notifier)
                              .state = filter.copyWith(
                            projectId: v,
                            clearProject: v == null,
                          ),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          isSwahili ? 'Tarehe ya Kuanza' : 'Start Date',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: isDarkMode
                                ? Colors.white70
                                : AppColors.textSecondary,
                          ),
                        ),
                        const SizedBox(height: 8),
                        InkWell(
                          onTap: () async {
                            final date = await showDatePicker(
                              context: context,
                              initialDate: filter.startDate ?? DateTime.now(),
                              firstDate: DateTime(2020),
                              lastDate: DateTime.now().add(
                                const Duration(days: 365),
                              ),
                            );
                            if (date != null) {
                              parentRef
                                  .read(
                                    projectDailyReportFilterProvider.notifier,
                                  )
                                  .state = filter.copyWith(
                                startDate: date,
                              );
                            }
                          },
                          child: Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: isDarkMode
                                  ? const Color(0xFF2A2A3E)
                                  : Colors.grey[100],
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Row(
                              children: [
                                Icon(
                                  Icons.calendar_today,
                                  size: 18,
                                  color: isDarkMode
                                      ? Colors.white54
                                      : Colors.grey[600],
                                ),
                                const SizedBox(width: 8),
                                Text(
                                  filter.startDate != null
                                      ? _formatDateDateTime(filter.startDate!)
                                      : (isSwahili
                                            ? 'Chagua tarehe'
                                            : 'Select date'),
                                  style: TextStyle(
                                    color: isDarkMode
                                        ? Colors.white
                                        : AppColors.textPrimary,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          isSwahili ? 'Tarehe ya Kumaliza' : 'End Date',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: isDarkMode
                                ? Colors.white70
                                : AppColors.textSecondary,
                          ),
                        ),
                        const SizedBox(height: 8),
                        InkWell(
                          onTap: () async {
                            final date = await showDatePicker(
                              context: context,
                              initialDate: filter.endDate ?? DateTime.now(),
                              firstDate: DateTime(2020),
                              lastDate: DateTime.now().add(
                                const Duration(days: 365),
                              ),
                            );
                            if (date != null) {
                              parentRef
                                  .read(
                                    projectDailyReportFilterProvider.notifier,
                                  )
                                  .state = filter.copyWith(
                                endDate: date,
                              );
                            }
                          },
                          child: Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: isDarkMode
                                  ? const Color(0xFF2A2A3E)
                                  : Colors.grey[100],
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Row(
                              children: [
                                Icon(
                                  Icons.calendar_today,
                                  size: 18,
                                  color: isDarkMode
                                      ? Colors.white54
                                      : Colors.grey[600],
                                ),
                                const SizedBox(width: 8),
                                Text(
                                  filter.endDate != null
                                      ? _formatDateDateTime(filter.endDate!)
                                      : (isSwahili
                                            ? 'Chagua tarehe'
                                            : 'Select date'),
                                  style: TextStyle(
                                    color: isDarkMode
                                        ? Colors.white
                                        : AppColors.textPrimary,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 24),
              Row(
                children: [
                  Expanded(
                    child: OutlinedButton(
                      onPressed: () =>
                          parentRef
                                  .read(
                                    projectDailyReportFilterProvider.notifier,
                                  )
                                  .state =
                              ReportFilter(),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        side: BorderSide(
                          color: isDarkMode
                              ? Colors.white24
                              : Colors.grey[300]!,
                        ),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: Text(
                        isSwahili ? 'Ondoa' : 'Clear',
                        style: TextStyle(
                          color: isDarkMode
                              ? Colors.white
                              : AppColors.textPrimary,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: ElevatedButton(
                      onPressed: () => Navigator.pop(context),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: Text(
                        isSwahili ? 'Omba' : 'Apply',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
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

class _ReportCard extends StatelessWidget {
  final Map<String, dynamic> report;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onTap;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _ReportCard({
    required this.report,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onTap,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
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
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFF3498DB).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(
                      Icons.assignment,
                      color: Color(0xFF3498DB),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          report['project_name'] as String? ?? '-',
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          _formatDateStr(report['report_date'] as String?),
                          style: TextStyle(
                            fontSize: 12,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                  PopupMenuButton<String>(
                    onSelected: (value) {
                      if (value == 'view')
                        onTap();
                      else if (value == 'edit')
                        onEdit();
                      else if (value == 'delete')
                        onDelete();
                    },
                    itemBuilder: (ctx) => [
                      PopupMenuItem(
                        value: 'view',
                        child: Row(
                          children: [
                            const Icon(Icons.visibility, size: 20),
                            const SizedBox(width: 8),
                            Text(isSwahili ? 'Tazama' : 'View'),
                          ],
                        ),
                      ),
                      PopupMenuItem(
                        value: 'edit',
                        child: Row(
                          children: [
                            const Icon(Icons.edit, size: 20),
                            const SizedBox(width: 8),
                            Text(isSwahili ? 'Hariri' : 'Edit'),
                          ],
                        ),
                      ),
                      PopupMenuItem(
                        value: 'delete',
                        child: Row(
                          children: [
                            const Icon(
                              Icons.delete,
                              size: 20,
                              color: Colors.red,
                            ),
                            const SizedBox(width: 8),
                            Text(
                              isSwahili ? 'Futa' : 'Delete',
                              style: const TextStyle(color: Colors.red),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              const Divider(height: 20),
              Row(
                children: [
                  Expanded(
                    child: _InfoItem(
                      icon: Icons.person,
                      label: isSwahili ? 'Msimamizi' : 'Supervisor',
                      value: report['supervisor_name'] as String? ?? '-',
                      isDarkMode: isDarkMode,
                    ),
                  ),
                  Expanded(
                    child: _InfoItem(
                      icon: Icons.timer,
                      label: isSwahili ? 'Masaa ya Kazi' : 'Labor Hours',
                      value: '${report['labor_hours'] ?? 0}',
                      isDarkMode: isDarkMode,
                    ),
                  ),
                ],
              ),
              if (report['weather_conditions'] != null &&
                  (report['weather_conditions'] as String).isNotEmpty) ...[
                const SizedBox(height: 8),
                _InfoItem(
                  icon: Icons.cloud,
                  label: isSwahili ? 'Hali ya Hewa' : 'Weather',
                  value: report['weather_conditions'] as String,
                  isDarkMode: isDarkMode,
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _InfoItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final bool isDarkMode;

  const _InfoItem({
    required this.icon,
    required this.label,
    required this.value,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Icon(
          icon,
          size: 16,
          color: isDarkMode ? Colors.white54 : Colors.grey[600],
        ),
        const SizedBox(width: 6),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: TextStyle(
                  fontSize: 10,
                  color: isDarkMode ? Colors.white54 : Colors.grey[600],
                ),
              ),
              Text(
                value,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w500,
                  color: isDarkMode ? Colors.white : AppColors.textPrimary,
                ),
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _ReportDetailSheet extends ConsumerWidget {
  final Map<String, dynamic> report;

  const _ReportDetailSheet({required this.report});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Container(
      height: MediaQuery.of(context).size.height * 0.75,
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              child: Column(
                children: [
                  Container(
                    width: 42,
                    height: 4,
                    decoration: BoxDecoration(
                      color: isDarkMode ? Colors.white24 : Colors.grey[300],
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          isSwahili ? 'Maelezo ya Ripoti' : 'Report Details',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                            color: isDarkMode
                                ? Colors.white
                                : AppColors.textPrimary,
                          ),
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.close),
                        onPressed: () => Navigator.pop(context),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                children: [
                  _DetailRow(
                    label: isSwahili ? 'Mradi' : 'Project',
                    value: report['project_name'] as String? ?? '-',
                    dark: isDarkMode,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Tarehe ya Ripoti' : 'Report Date',
                    value: _formatDateStr(report['report_date'] as String?),
                    dark: isDarkMode,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Msimamizi' : 'Supervisor',
                    value: report['supervisor_name'] as String? ?? '-',
                    dark: isDarkMode,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Hali ya Hewa' : 'Weather Conditions',
                    value: report['weather_conditions'] as String? ?? '-',
                    dark: isDarkMode,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Masaa ya Kazi' : 'Labor Hours',
                    value: '${report['labor_hours'] ?? 0}',
                    dark: isDarkMode,
                  ),
                  if (report['work_completed'] != null &&
                      (report['work_completed'] as String).isNotEmpty)
                    _DetailRow(
                      label: isSwahili
                          ? 'Kazi Iliyokamilika'
                          : 'Work Completed',
                      value: report['work_completed'] as String,
                      dark: isDarkMode,
                    ),
                  if (report['materials_used'] != null &&
                      (report['materials_used'] as String).isNotEmpty)
                    _DetailRow(
                      label: isSwahili
                          ? 'Vifaa Vilivyotumika'
                          : 'Materials Used',
                      value: report['materials_used'] as String,
                      dark: isDarkMode,
                    ),
                  if (report['issues_faced'] != null &&
                      (report['issues_faced'] as String).isNotEmpty)
                    _DetailRow(
                      label: isSwahili
                          ? 'Masuala Yanayokumbana'
                          : 'Issues Faced',
                      value: report['issues_faced'] as String,
                      dark: isDarkMode,
                    ),
                  _DetailRow(
                    label: isSwahili ? 'Imeundwa' : 'Created',
                    value: _formatDateTime(report['created_at'] as String?),
                    dark: isDarkMode,
                  ),
                  _DetailRow(
                    label: isSwahili ? 'Imesasishwa' : 'Updated',
                    value: _formatDateTime(report['updated_at'] as String?),
                    dark: isDarkMode,
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

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool dark;

  const _DetailRow({
    required this.label,
    required this.value,
    required this.dark,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: dark
            ? const Color(0xFF252540)
            : Colors.grey.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: dark ? Colors.white54 : AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value.isEmpty ? '-' : value,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w500,
              color: dark ? Colors.white : AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }
}

class _ReportFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? report;

  const _ReportFormSheet({this.report});

  @override
  ConsumerState<_ReportFormSheet> createState() => _ReportFormSheetState();
}

class _ReportFormSheetState extends ConsumerState<_ReportFormSheet> {
  final _formKey = GlobalKey<FormState>();
  final _weatherController = TextEditingController();
  final _workCompletedController = TextEditingController();
  final _materialsUsedController = TextEditingController();
  final _laborHoursController = TextEditingController();
  final _issuesController = TextEditingController();
  int? _selectedProjectId;
  DateTime _reportDate = DateTime.now();
  bool _loading = false;
  bool _loadingData = true;
  List<dynamic> _projects = [];

  late final bool _isEditing;
  int? _reportId;

  @override
  void initState() {
    super.initState();
    _isEditing = widget.report != null;
    if (_isEditing) {
      _reportId = widget.report!['id'] as int?;
      _selectedProjectId = widget.report!['project_id'] as int?;
      _weatherController.text =
          widget.report!['weather_conditions'] as String? ?? '';
      _workCompletedController.text =
          widget.report!['work_completed'] as String? ?? '';
      _materialsUsedController.text =
          widget.report!['materials_used'] as String? ?? '';
      _laborHoursController.text = '${widget.report!['labor_hours'] ?? 0}';
      _issuesController.text = widget.report!['issues_faced'] as String? ?? '';
      final dateStr = widget.report!['report_date'] as String?;
      if (dateStr != null) {
        _reportDate = DateTime.tryParse(dateStr) ?? DateTime.now();
      }
    }
    _loadProjects();
  }

  Future<void> _loadProjects() async {
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/project-daily-reports/projects');
      if (mounted) {
        setState(() {
          _projects = response.data['data'] as List? ?? [];
          _loadingData = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _loadingData = false);
    }
  }

  @override
  void dispose() {
    _weatherController.dispose();
    _workCompletedController.dispose();
    _materialsUsedController.dispose();
    _laborHoursController.dispose();
    _issuesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SafeArea(
        child: _loadingData
            ? const Padding(
                padding: EdgeInsets.all(40),
                child: Center(child: CircularProgressIndicator()),
              )
            : SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Form(
                  key: _formKey,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Center(
                        child: Container(
                          width: 42,
                          height: 4,
                          decoration: BoxDecoration(
                            color: isDarkMode
                                ? Colors.white24
                                : Colors.grey[300],
                            borderRadius: BorderRadius.circular(2),
                          ),
                        ),
                      ),
                      const SizedBox(height: 18),
                      Text(
                        _isEditing
                            ? (isSwahili ? 'Hariri Ripoti' : 'Edit Report')
                            : (isSwahili ? 'Ripoti Mpya' : 'New Report'),
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w700,
                          color: isDarkMode
                              ? Colors.white
                              : AppColors.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 24),
                      _buildLabel(
                        isSwahili ? 'Mradi *' : 'Project *',
                        isDarkMode,
                      ),
                      const SizedBox(height: 8),
                      _buildDropdown(
                        value: _selectedProjectId,
                        hint: isSwahili ? 'Chagua mradi' : 'Select project',
                        items: _projects
                            .map(
                              (p) => DropdownMenuItem(
                                value: p['id'] as int,
                                child: Text(
                                  p['project_name'] as String? ?? '-',
                                ),
                              ),
                            )
                            .toList(),
                        onChanged: (v) =>
                            setState(() => _selectedProjectId = v),
                        isDarkMode: isDarkMode,
                        validator: (v) => v == null
                            ? (isSwahili
                                  ? 'Mradi yahitajika'
                                  : 'Project required')
                            : null,
                      ),
                      const SizedBox(height: 16),
                      _buildLabel(
                        isSwahili ? 'Tarehe ya Ripoti *' : 'Report Date *',
                        isDarkMode,
                      ),
                      const SizedBox(height: 8),
                      InkWell(
                        onTap: () async {
                          final date = await showDatePicker(
                            context: context,
                            initialDate: _reportDate,
                            firstDate: DateTime(2020),
                            lastDate: DateTime.now().add(
                              const Duration(days: 365),
                            ),
                          );
                          if (date != null) setState(() => _reportDate = date);
                        },
                        child: _buildInput(
                          child: Row(
                            children: [
                              Icon(
                                Icons.calendar_today,
                                size: 18,
                                color: isDarkMode
                                    ? Colors.white54
                                    : Colors.grey[600],
                              ),
                              const SizedBox(width: 8),
                              Text(
                                _formatDateDateTime(_reportDate),
                                style: TextStyle(
                                  color: isDarkMode
                                      ? Colors.white
                                      : AppColors.textPrimary,
                                ),
                              ),
                            ],
                          ),
                          isDarkMode: isDarkMode,
                        ),
                      ),
                      const SizedBox(height: 16),
                      Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                _buildLabel(
                                  isSwahili ? 'Masaa ya Kazi' : 'Labor Hours',
                                  isDarkMode,
                                ),
                                const SizedBox(height: 8),
                                TextFormField(
                                  controller: _laborHoursController,
                                  keyboardType: TextInputType.number,
                                  decoration: _inputDecoration(
                                    isSwahili ? '0' : '0',
                                    isDarkMode,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                _buildLabel(
                                  isSwahili ? 'Hali ya Hewa' : 'Weather',
                                  isDarkMode,
                                ),
                                const SizedBox(height: 8),
                                TextFormField(
                                  controller: _weatherController,
                                  decoration: _inputDecoration(
                                    isSwahili ? 'Hali ya hewa' : 'Weather',
                                    isDarkMode,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      _buildLabel(
                        isSwahili ? 'Kazi Iliyokamilika' : 'Work Completed',
                        isDarkMode,
                      ),
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _workCompletedController,
                        maxLines: 3,
                        decoration: _inputDecoration(
                          isSwahili
                              ? 'Maelezo ya kazi iliyokamilika...'
                              : 'Describe work completed...',
                          isDarkMode,
                        ),
                      ),
                      const SizedBox(height: 16),
                      _buildLabel(
                        isSwahili ? 'Vifaa Vilivyotumika' : 'Materials Used',
                        isDarkMode,
                      ),
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _materialsUsedController,
                        maxLines: 2,
                        decoration: _inputDecoration(
                          isSwahili
                              ? 'Vifaa vilivyotumika...'
                              : 'Materials used...',
                          isDarkMode,
                        ),
                      ),
                      const SizedBox(height: 16),
                      _buildLabel(
                        isSwahili ? 'Masuala Yanayokumbana' : 'Issues Faced',
                        isDarkMode,
                      ),
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: _issuesController,
                        maxLines: 2,
                        decoration: _inputDecoration(
                          isSwahili ? 'Masuala yoyote...' : 'Any issues...',
                          isDarkMode,
                        ),
                      ),
                      const SizedBox(height: 24),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: _loading ? null : _submit,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: _loading
                              ? const SizedBox(
                                  width: 24,
                                  height: 24,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : Text(
                                  _isEditing
                                      ? (isSwahili
                                            ? 'Hifadhi Mabadiliko'
                                            : 'Save Changes')
                                      : (isSwahili
                                            ? 'Unda Ripoti'
                                            : 'Create Report'),
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                        ),
                      ),
                      const SizedBox(height: 16),
                    ],
                  ),
                ),
              ),
      ),
    );
  }

  Widget _buildLabel(String text, bool isDarkMode) {
    return Text(
      text,
      style: TextStyle(
        fontSize: 14,
        fontWeight: FontWeight.w600,
        color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
      ),
    );
  }

  Widget _buildDropdown({
    int? value,
    required String hint,
    required List<DropdownMenuItem<int>> items,
    required ValueChanged<int?> onChanged,
    required bool isDarkMode,
    String? Function(int?)? validator,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
        borderRadius: BorderRadius.circular(12),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<int?>(
          value: value,
          hint: Text(
            hint,
            style: TextStyle(
              color: isDarkMode ? Colors.white54 : Colors.grey[600],
            ),
          ),
          isExpanded: true,
          dropdownColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
          items: items,
          onChanged: onChanged,
        ),
      ),
    );
  }

  Widget _buildInput({required Widget child, required bool isDarkMode}) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
        borderRadius: BorderRadius.circular(12),
      ),
      child: child,
    );
  }

  InputDecoration _inputDecoration(String hint, bool isDarkMode) {
    return InputDecoration(
      hintText: hint,
      filled: true,
      fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide.none,
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedProjectId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            ref.read(isSwahiliProvider) ? 'Chagua mradi' : 'Select a project',
          ),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() => _loading = true);
    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'project_id': _selectedProjectId,
        'report_date': DateFormat('yyyy-MM-dd').format(_reportDate),
        'weather_conditions': _weatherController.text,
        'work_completed': _workCompletedController.text,
        'materials_used': _materialsUsedController.text,
        'labor_hours': int.tryParse(_laborHoursController.text) ?? 0,
        'issues_faced': _issuesController.text,
      };

      if (_isEditing && _reportId != null) {
        await api.put('/project-daily-reports/$_reportId', data: data);
      } else {
        await api.post('/project-daily-reports', data: data);
      }
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (mounted)
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }
}

class _ErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ErrorView({
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
          '$error',
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

String _formatDateDateTime(DateTime date) =>
    DateFormat('dd MMM yyyy').format(date);
String _formatDateStr(String? date) => date != null && date.isNotEmpty
    ? DateFormat('dd MMM yyyy').format(DateTime.parse(date))
    : '-';
String _formatDateTime(String? date) => date != null && date.isNotEmpty
    ? DateFormat('dd MMM yyyy HH:mm').format(DateTime.parse(date))
    : '-';
