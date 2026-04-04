import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _projectSummaryReportsSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);

class _ProjectSummaryFilter {
  final DateTime? startDate;
  final DateTime? endDate;
  final int? projectId;

  const _ProjectSummaryFilter({
    this.startDate,
    this.endDate,
    this.projectId,
  });

  _ProjectSummaryFilter copyWith({
    DateTime? startDate,
    DateTime? endDate,
    int? projectId,
    bool clearStart = false,
    bool clearEnd = false,
    bool clearProject = false,
  }) {
    return _ProjectSummaryFilter(
      startDate: clearStart ? null : (startDate ?? this.startDate),
      endDate: clearEnd ? null : (endDate ?? this.endDate),
      projectId: clearProject ? null : (projectId ?? this.projectId),
    );
  }
}

final _projectSummaryFilterProvider =
    StateProvider.autoDispose<_ProjectSummaryFilter>(
      (ref) => const _ProjectSummaryFilter(),
    );

final _projectSummaryProjectsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/project-reports/projects');
      final payload = response.data['data'] as List? ?? const [];
      return payload
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

final _projectSummaryReportsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final filter = ref.watch(_projectSummaryFilterProvider);

      final queryParameters = <String, dynamic>{};
      if (filter.projectId != null) {
        queryParameters['project_id'] = filter.projectId;
      }
      if (filter.startDate != null) {
        queryParameters['start_date'] = DateFormat(
          'yyyy-MM-dd',
        ).format(filter.startDate!);
      }
      if (filter.endDate != null) {
        queryParameters['end_date'] = DateFormat(
          'yyyy-MM-dd',
        ).format(filter.endDate!);
      }

      final response = await api.get(
        '/project-reports',
        queryParameters: queryParameters,
      );

      final rawItems = response.data['data'] as List? ?? const [];
      final items = rawItems
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();

      final meta = response.data['meta'] as Map<String, dynamic>? ?? const {};

      return {
        'items': items,
        'meta': meta,
      };
    });

class ProjectSummaryReportsScreen extends ConsumerWidget {
  const ProjectSummaryReportsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final reportsAsync = ref.watch(_projectSummaryReportsProvider);
    final projectsAsync = ref.watch(_projectSummaryProjectsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final filter = ref.watch(_projectSummaryFilterProvider);
    final search = ref
        .watch(_projectSummaryReportsSearchProvider)
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
        onRefresh: () async {
          ref.invalidate(_projectSummaryReportsProvider);
          ref.invalidate(_projectSummaryProjectsProvider);
        },
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  children: [
                    TextField(
                      onChanged: (value) {
                        ref
                                .read(
                                  _projectSummaryReportsSearchProvider.notifier,
                                )
                                .state =
                            value;
                      },
                      decoration: InputDecoration(
                        hintText: isSwahili
                            ? 'Tafuta ripoti za miradi...'
                            : 'Search project reports...',
                        prefixIcon: const Icon(Icons.search_rounded),
                        suffixIcon: search.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear),
                                onPressed: () {
                                  ref
                                          .read(
                                            _projectSummaryReportsSearchProvider
                                                .notifier,
                                          )
                                          .state =
                                      '';
                                },
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
                      data: (projects) => projects.isEmpty
                          ? const SizedBox.shrink()
                          : _ProjectSummaryFilters(
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
              error: (error, _) => SliverFillRemaining(
                child: _ProjectSummaryErrorView(
                  error: '$error',
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_projectSummaryReportsProvider),
                ),
              ),
              data: (payload) {
                final meta = payload['meta'] as Map<String, dynamic>? ?? const {};
                final allItems =
                    (payload['items'] as List? ?? const [])
                        .whereType<Map<String, dynamic>>()
                        .toList();

                final items = search.isEmpty
                    ? allItems
                    : allItems.where((item) {
                        final haystack = [
                          item['title'] ?? '',
                          item['project_name'] ?? '',
                          item['author_name'] ?? '',
                          item['summary'] ?? '',
                          item['report_date'] ?? '',
                          item['status'] ?? '',
                        ].join(' ').toLowerCase();
                        return haystack.contains(search);
                      }).toList();

                if (items.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Text(
                        search.isNotEmpty
                            ? (isSwahili
                                  ? 'Hakuna matokeo yanayolingana'
                                  : 'No reports match your search')
                            : (isSwahili
                                  ? 'Hakuna ripoti za miradi'
                                  : 'No project reports found'),
                        style: TextStyle(
                          fontSize: 16,
                          color: Colors.grey[600],
                        ),
                      ),
                    ),
                  );
                }

                return SliverPadding(
                  padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
                  sliver: SliverList(
                    delegate: SliverChildListDelegate([
                      Row(
                        children: [
                          Expanded(
                            child: _ProjectSummaryStatChip(
                              label: isSwahili ? 'Jumla' : 'Total',
                              value: '${meta['total'] ?? items.length}',
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: _ProjectSummaryStatChip(
                              label: isSwahili ? 'Ripoti za Siku' : 'Daily',
                              value: '${meta['daily_reports'] ?? 0}',
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: _ProjectSummaryStatChip(
                              label: isSwahili ? 'Ziara' : 'Visits',
                              value: '${meta['site_visits'] ?? 0}',
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      ...items.map(
                        (item) => _ProjectSummaryCard(
                          item: item,
                          isDarkMode: isDarkMode,
                          isSwahili: isSwahili,
                        ),
                      ),
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

class _ProjectSummaryFilters extends ConsumerWidget {
  final List<Map<String, dynamic>> projects;
  final _ProjectSummaryFilter filter;
  final bool isSwahili;
  final bool isDarkMode;

  const _ProjectSummaryFilters({
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
      backgroundColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.white,
      collapsedBackgroundColor: isDarkMode
          ? const Color(0xFF2A2A3E)
          : Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      collapsedShape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(14),
      ),
      childrenPadding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
      children: [
        DropdownButtonFormField<int?>(
          isExpanded: true,
          value: filter.projectId,
          decoration: InputDecoration(
            labelText: isSwahili ? 'Mradi' : 'Project',
          ),
          items: [
            DropdownMenuItem<int?>(
              value: null,
              child: Text(isSwahili ? 'Miradi yote' : 'All projects'),
            ),
            ...projects.map(
              (project) => DropdownMenuItem<int?>(
                value: _toInt(project['id']),
                child: Text(
                  project['project_name']?.toString() ?? '-',
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ),
          ],
          onChanged: (value) {
            ref.read(_projectSummaryFilterProvider.notifier).state = filter
                .copyWith(projectId: value, clearProject: value == null);
          },
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _ProjectSummaryDateField(
                label: isSwahili ? 'Tarehe ya Kuanza' : 'Start Date',
                value: filter.startDate,
                onTap: () async {
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: filter.startDate ?? DateTime.now(),
                    firstDate: DateTime(2000),
                    lastDate: DateTime(2100),
                  );
                  if (picked != null) {
                    ref.read(_projectSummaryFilterProvider.notifier).state =
                        filter.copyWith(startDate: picked);
                  }
                },
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _ProjectSummaryDateField(
                label: isSwahili ? 'Tarehe ya Mwisho' : 'End Date',
                value: filter.endDate,
                onTap: () async {
                  final picked = await showDatePicker(
                    context: context,
                    initialDate: filter.endDate ?? DateTime.now(),
                    firstDate: DateTime(2000),
                    lastDate: DateTime(2100),
                  );
                  if (picked != null) {
                    ref.read(_projectSummaryFilterProvider.notifier).state =
                        filter.copyWith(endDate: picked);
                  }
                },
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Align(
          alignment: Alignment.centerRight,
          child: TextButton.icon(
            onPressed: () {
              ref.read(_projectSummaryFilterProvider.notifier).state =
                  const _ProjectSummaryFilter();
            },
            icon: const Icon(Icons.refresh_rounded),
            label: Text(isSwahili ? 'Reset' : 'Reset'),
          ),
        ),
      ],
    );
  }
}

class _ProjectSummaryDateField extends StatelessWidget {
  final String label;
  final DateTime? value;
  final VoidCallback onTap;

  const _ProjectSummaryDateField({
    required this.label,
    required this.value,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final text = value == null ? '-' : DateFormat('yyyy-MM-dd').format(value!);
    return InkWell(
      onTap: onTap,
      child: InputDecorator(
        decoration: InputDecoration(labelText: label),
        child: Row(
          children: [
            const Icon(Icons.calendar_today_rounded, size: 18),
            const SizedBox(width: 8),
            Expanded(child: Text(text, overflow: TextOverflow.ellipsis)),
          ],
        ),
      ),
    );
  }
}

class _ProjectSummaryStatChip extends StatelessWidget {
  final String label;
  final String value;

  const _ProjectSummaryStatChip({
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Column(
        children: [
          Text(
            value,
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: AppColors.primary,
            ),
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

class _ProjectSummaryCard extends ConsumerWidget {
  final Map<String, dynamic> item;
  final bool isDarkMode;
  final bool isSwahili;

  const _ProjectSummaryCard({
    required this.item,
    required this.isDarkMode,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final type = item['type']?.toString() ?? '-';
    final accent = type == 'site_visit'
        ? const Color(0xFF27AE60)
        : const Color(0xFF2980B9);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: () {
          showModalBottomSheet<void>(
            context: context,
            backgroundColor: Colors.transparent,
            isScrollControlled: true,
            builder: (_) => _ProjectSummaryDetailSheet(item: item),
          );
        },
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
                      item['title']?.toString() ??
                          (type == 'site_visit' ? 'Site Visit' : 'Daily Report'),
                      style: TextStyle(
                        color: accent,
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                  const Spacer(),
                  Text(
                    item['report_date']?.toString() ?? '-',
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
                item['project_name']?.toString() ?? '-',
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 6),
              Text(
                item['summary']?.toString() ?? '-',
                maxLines: 3,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: isDarkMode
                      ? Colors.white70
                      : AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  Icon(Icons.person_outline_rounded, size: 16, color: accent),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      item['author_name']?.toString() ?? '-',
                      style: TextStyle(
                        fontSize: 12,
                        color: isDarkMode
                            ? Colors.white70
                            : AppColors.textPrimary,
                      ),
                    ),
                  ),
                  Text(
                    item['status']?.toString() ?? '-',
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

class _ProjectSummaryDetailSheet extends ConsumerWidget {
  final Map<String, dynamic> item;

  const _ProjectSummaryDetailSheet({required this.item});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);

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
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 16),
              decoration: const BoxDecoration(
                color: AppColors.primary,
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              ),
              child: Column(
                children: [
                  Container(
                    width: 42,
                    height: 4,
                    decoration: BoxDecoration(
                      color: Colors.white38,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  const SizedBox(height: 18),
                  Row(
                    children: [
                      IconButton(
                        onPressed: () => Navigator.pop(context),
                        icon: const Icon(
                          Icons.arrow_back_rounded,
                          color: Colors.white,
                        ),
                        padding: EdgeInsets.zero,
                        constraints: const BoxConstraints(),
                      ),
                      Expanded(
                        child: Text(
                          isSwahili ? 'Maelezo ya Ripoti' : 'Report Details',
                          textAlign: TextAlign.center,
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
            ),
            Flexible(
              child: ListView(
                shrinkWrap: true,
                padding: const EdgeInsets.all(20),
                children: [
                  _ProjectSummaryDetailRow(
                    label: 'Type',
                    value: item['title']?.toString() ?? '-',
                  ),
                  _ProjectSummaryDetailRow(
                    label: 'Project',
                    value: item['project_name']?.toString() ?? '-',
                  ),
                  _ProjectSummaryDetailRow(
                    label: 'Date',
                    value: item['report_date']?.toString() ?? '-',
                  ),
                  _ProjectSummaryDetailRow(
                    label: 'Owner',
                    value: item['author_name']?.toString() ?? '-',
                  ),
                  _ProjectSummaryDetailRow(
                    label: 'Status',
                    value: item['status']?.toString() ?? '-',
                  ),
                  _ProjectSummaryDetailRow(
                    label: 'Summary',
                    value: item['summary']?.toString() ?? '-',
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () => Navigator.pop(context),
                      child: Text(isSwahili ? 'Funga' : 'Close'),
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

class _ProjectSummaryDetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _ProjectSummaryDetailRow({
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 110,
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
              style: const TextStyle(
                fontSize: 14,
                color: AppColors.textPrimary,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ProjectSummaryErrorView extends StatelessWidget {
  final String error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ProjectSummaryErrorView({
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

int? _toInt(dynamic value) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  return int.tryParse('$value');
}
