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
      final response = await api.get('/projects');
      return response.data['data'] as List? ?? [];
    });

final _projectReportsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/project-reports');
      return {
        'items': response.data['data'] as List? ?? const [],
        'meta': response.data['meta'] as Map<String, dynamic>? ?? const {},
      };
    });

class ProjectReportsScreen extends ConsumerStatefulWidget {
  const ProjectReportsScreen({super.key});

  @override
  ConsumerState<ProjectReportsScreen> createState() =>
      _ProjectReportsScreenState();
}

class _ProjectReportsScreenState extends ConsumerState<ProjectReportsScreen> {
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
        title: Text(isSwahili ? 'Ripoti za Miradi' : 'Project Reports'),
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
                                      ? 'Hakuna ripoti za miradi'
                                      : 'No project reports found')
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

  const _ReportItemCard({
    required this.item,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    final isVisit = item['type'] == 'site_visit';
    final accent = isVisit ? const Color(0xFFF39C12) : const Color(0xFF1ABC9C);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
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
                        : (isSwahili ? 'Ripoti ya Siku' : 'Daily Report'),
                    style: TextStyle(
                      color: accent,
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
                const Spacer(),
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
    );
  }
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
