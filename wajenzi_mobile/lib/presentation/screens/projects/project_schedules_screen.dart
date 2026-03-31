import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';

final _schedulesSearchProvider = StateProvider.autoDispose<String>((ref) => '');

class _ScheduleFilter {
  final DateTime? startDate;
  final DateTime? endDate;
  final String? status;

  _ScheduleFilter({this.startDate, this.endDate, this.status});

  _ScheduleFilter copyWith({
    DateTime? startDate,
    DateTime? endDate,
    String? status,
    bool clearStart = false,
    bool clearEnd = false,
    bool clearStatus = false,
  }) {
    return _ScheduleFilter(
      startDate: clearStart ? null : (startDate ?? this.startDate),
      endDate: clearEnd ? null : (endDate ?? this.endDate),
      status: clearStatus ? null : (status ?? this.status),
    );
  }

  Map<String, String> toQueryParams() {
    final params = <String, String>{};
    if (startDate != null)
      params['start_date'] = DateFormat('yyyy-MM-dd').format(startDate!);
    if (endDate != null)
      params['end_date'] = DateFormat('yyyy-MM-dd').format(endDate!);
    if (status != null && status!.isNotEmpty) params['status'] = status!;
    return params;
  }
}

final _schedulesFilterProvider = StateProvider.autoDispose<_ScheduleFilter>(
  (ref) => _ScheduleFilter(),
);

final _projectSchedulesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final filter = ref.watch(_schedulesFilterProvider);
      final response = await api.get(
        '/project-schedules',
        queryParameters: filter.toQueryParams(),
      );
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final items = data['data'] as List? ?? const [];

      return items
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

final _projectScheduleDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/project-schedules/$id');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      return data['data'] is Map
          ? Map<String, dynamic>.from(data['data'] as Map)
          : const <String, dynamic>{};
    });

String _scheduleErrorMessage(Object error, bool isSwahili) {
  if (error is DioException) {
    final data = error.response?.data;
    if (data is Map) {
      final message = data['message'];
      if (message is String && message.trim().isNotEmpty) {
        return message;
      }
    }
  }

  return isSwahili ? 'Hitilafu imetokea' : 'Something went wrong';
}

class ProjectSchedulesScreen extends ConsumerStatefulWidget {
  const ProjectSchedulesScreen({super.key});

  @override
  ConsumerState<ProjectSchedulesScreen> createState() =>
      _ProjectSchedulesScreenState();
}

class _ProjectSchedulesScreenState
    extends ConsumerState<ProjectSchedulesScreen> {
  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final schedulesAsync = ref.watch(_projectSchedulesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final filter = ref.watch(_schedulesFilterProvider);
    final search = ref.watch(_schedulesSearchProvider).trim().toLowerCase();

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Ratiba za Miradi' : 'Project Schedules'),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_projectSchedulesProvider),
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
                          ref.read(_schedulesSearchProvider.notifier).state =
                              value,
                      decoration: InputDecoration(
                        hintText: isSwahili
                            ? 'Tafuta ratiba...'
                            : 'Search schedules...',
                        prefixIcon: const Icon(Icons.search_rounded),
                        suffixIcon: search.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.clear),
                                onPressed: () =>
                                    ref
                                            .read(
                                              _schedulesSearchProvider.notifier,
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
                    _ScheduleFilters(
                      filter: filter,
                      isSwahili: isSwahili,
                      isDarkMode: isDarkMode,
                    ),
                  ],
                ),
              ),
            ),
            schedulesAsync.when(
              loading: () => const SliverFillRemaining(
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (error, _) => SliverFillRemaining(
                child: _ScheduleErrorView(
                  message: _scheduleErrorMessage(error, isSwahili),
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_projectSchedulesProvider),
                ),
              ),
              data: (allItems) {
                final schedules = search.isEmpty
                    ? allItems
                    : allItems.where((schedule) {
                        final haystack = [
                          schedule['lead_number'] ?? '',
                          schedule['lead_name'] ?? '',
                          schedule['client_name'] ?? '',
                          schedule['assigned_architect_name'] ?? '',
                          schedule['status'] ?? '',
                        ].join(' ').toLowerCase();
                        return haystack.contains(search);
                      }).toList();

                if (schedules.isEmpty) {
                  return SliverFillRemaining(
                    child: Center(
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.calendar_month_outlined,
                            size: 64,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(height: 16),
                          Text(
                            allItems.isEmpty
                                ? (isSwahili
                                      ? 'Hakuna ratiba zilizopatikana'
                                      : 'No project schedules found')
                                : (isSwahili
                                      ? 'Hakuna matokeo yanayolingana'
                                      : 'No schedules match your search'),
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
                                            _schedulesSearchProvider.notifier,
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
                    delegate: SliverChildBuilderDelegate((context, index) {
                      return _ScheduleCard(
                        schedule: schedules[index],
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onView: () => _showDetails(
                          context,
                          _toInt(schedules[index]['id']),
                        ),
                        onDelete: () =>
                            _deleteSchedule(context, schedules[index]),
                      );
                    }, childCount: schedules.length),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  void _showDetails(BuildContext context, int id) {
    showModalBottomSheet<void>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.92,
        child: _ScheduleDetailSheet(id: id),
      ),
    );
  }

  Future<void> _deleteSchedule(
    BuildContext context,
    Map<String, dynamic> schedule,
  ) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final isDarkMode = ref.read(isDarkModeProvider);

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        title: Text(
          isSwahili ? 'Futa Ratiba' : 'Delete Schedule',
          style: TextStyle(
            color: isDarkMode ? Colors.white : AppColors.textPrimary,
          ),
        ),
        content: Text(
          isSwahili
              ? 'Je, una uhakika unataka kufuta ratiba hii?'
              : 'Are you sure you want to delete this schedule?',
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
              style: const TextStyle(color: AppColors.error),
            ),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    try {
      await ref
          .read(apiClientProvider)
          .delete('/project-schedules/${schedule['id']}');
      ref.invalidate(_projectSchedulesProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Ratiba imefutwa' : 'Schedule deleted'),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_scheduleErrorMessage(e, isSwahili)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }
}

class _ScheduleFilters extends ConsumerWidget {
  final _ScheduleFilter filter;
  final bool isSwahili;
  final bool isDarkMode;

  const _ScheduleFilters({
    required this.filter,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return ExpansionTile(
      title: Text(isSwahili ? 'Vichungi' : 'Filters'),
      initiallyExpanded:
          filter.status != null ||
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
        _StatusFilterChips(
          isSwahili: isSwahili,
          isDarkMode: isDarkMode,
          selectedStatus: filter.status,
          onChanged: (value) =>
              ref.read(_schedulesFilterProvider.notifier).state = filter
                  .copyWith(status: value, clearStatus: value == null),
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
                    ref.read(_schedulesFilterProvider.notifier).state = filter
                        .copyWith(startDate: picked);
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
                    ref.read(_schedulesFilterProvider.notifier).state = filter
                        .copyWith(endDate: picked);
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
        if (filter.status != null ||
            filter.startDate != null ||
            filter.endDate != null)
          Padding(
            padding: const EdgeInsets.only(top: 8),
            child: OutlinedButton(
              onPressed: () =>
                  ref.read(_schedulesFilterProvider.notifier).state =
                      _ScheduleFilter(),
              child: Text(isSwahili ? 'Futa' : 'Clear'),
            ),
          ),
      ],
    );
  }
}

class _StatusFilterChips extends StatelessWidget {
  final bool isSwahili;
  final bool isDarkMode;
  final String? selectedStatus;
  final ValueChanged<String?> onChanged;

  const _StatusFilterChips({
    required this.isSwahili,
    required this.isDarkMode,
    required this.selectedStatus,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    final options = <String?, String>{
      null: isSwahili ? 'Zote' : 'All',
      'pending': isSwahili ? 'Inasubiri' : 'Pending',
      'in_progress': isSwahili ? 'Inaendelea' : 'In Progress',
      'completed': isSwahili ? 'Imekamilika' : 'Completed',
      'cancelled': isSwahili ? 'Imefutwa' : 'Cancelled',
    };

    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: options.entries.map((entry) {
          final selected = selectedStatus == entry.key;
          return Padding(
            padding: const EdgeInsets.only(right: 8, bottom: 12),
            child: ChoiceChip(
              selected: selected,
              label: Text(entry.value),
              onSelected: (_) => onChanged(entry.key),
              selectedColor: AppColors.primary.withValues(alpha: 0.15),
              labelStyle: TextStyle(
                color: selected
                    ? AppColors.primary
                    : (isDarkMode ? Colors.white70 : AppColors.textSecondary),
                fontWeight: selected ? FontWeight.w600 : FontWeight.w500,
              ),
              side: BorderSide(
                color: selected
                    ? AppColors.primary
                    : (isDarkMode
                          ? Colors.white12
                          : AppColors.textHint.withValues(alpha: 0.4)),
              ),
              backgroundColor: isDarkMode
                  ? const Color(0xFF1A2332)
                  : Colors.white,
            ),
          );
        }).toList(),
      ),
    );
  }
}

class _ScheduleCard extends StatelessWidget {
  final Map<String, dynamic> schedule;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onView;
  final VoidCallback onDelete;

  const _ScheduleCard({
    required this.schedule,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onView,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final progress = schedule['progress'] is Map
        ? Map<String, dynamic>.from(schedule['progress'] as Map)
        : const <String, dynamic>{};
    final percent = _toDouble(progress['percentage']);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: onView,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          (schedule['lead_number'] ??
                                  schedule['lead_name'] ??
                                  '-')
                              .toString(),
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          (schedule['client_name'] ??
                                  schedule['assigned_architect_name'] ??
                                  '-')
                              .toString(),
                          style: const TextStyle(
                            fontSize: 13,
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                  PopupMenuButton<String>(
                    onSelected: (value) {
                      if (value == 'view')
                        onView();
                      else if (value == 'delete')
                        onDelete();
                    },
                    itemBuilder: (_) => [
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
                        value: 'delete',
                        child: Row(
                          children: [
                            const Icon(
                              Icons.delete,
                              size: 20,
                              color: AppColors.error,
                            ),
                            const SizedBox(width: 8),
                            Text(
                              isSwahili ? 'Futa' : 'Delete',
                              style: const TextStyle(color: AppColors.error),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 8),
              _statusChip(schedule['status']?.toString()),
              const SizedBox(height: 12),
              _metaRow(
                isSwahili ? 'Architect' : 'Architect',
                schedule['assigned_architect_name'],
              ),
              _metaRow(
                isSwahili ? 'Start' : 'Start',
                _formatDate(schedule['start_date']?.toString()),
              ),
              _metaRow(
                isSwahili ? 'End' : 'End',
                _formatDate(schedule['end_date']?.toString()),
              ),
              const SizedBox(height: 10),
              ClipRRect(
                borderRadius: BorderRadius.circular(999),
                child: LinearProgressIndicator(
                  value: percent <= 0 ? 0 : percent / 100,
                  minHeight: 8,
                  backgroundColor: Colors.grey.shade300,
                  color: _statusColor(schedule['status']?.toString()),
                ),
              ),
              const SizedBox(height: 6),
              Text(
                isSwahili
                    ? 'Maendeleo: ${percent.toStringAsFixed(1)}%'
                    : 'Progress: ${percent.toStringAsFixed(1)}%',
                style: const TextStyle(
                  fontSize: 12,
                  color: AppColors.textSecondary,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _metaRow(String label, dynamic value) {
    final text = (value ?? '-').toString().trim();
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Text(
        '$label: ${text.isEmpty ? '-' : text}',
        style: const TextStyle(fontSize: 13),
      ),
    );
  }
}

class _ScheduleDetailSheet extends ConsumerWidget {
  final int id;

  const _ScheduleDetailSheet({required this.id});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final detailAsync = ref.watch(_projectScheduleDetailProvider(id));
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: detailAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _ScheduleErrorView(
            message: _scheduleErrorMessage(error, isSwahili),
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_projectScheduleDetailProvider(id)),
          ),
          data: (schedule) {
            final progress = schedule['progress'] is Map
                ? Map<String, dynamic>.from(schedule['progress'] as Map)
                : const <String, dynamic>{};
            final activities = (schedule['activities'] as List? ?? const [])
                .whereType<Map>()
                .map((item) => Map<String, dynamic>.from(item))
                .toList();

            return Column(
              children: [
                const SizedBox(height: 12),
                Container(
                  width: 44,
                  height: 5,
                  decoration: BoxDecoration(
                    color: isDarkMode ? Colors.white24 : Colors.black12,
                    borderRadius: BorderRadius.circular(999),
                  ),
                ),
                Expanded(
                  child: ListView(
                    padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              (schedule['lead_number'] ??
                                      schedule['lead_name'] ??
                                      '-')
                                  .toString(),
                              style: const TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                          IconButton(
                            icon: const Icon(Icons.close),
                            onPressed: () => Navigator.pop(context),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          _statusChip(schedule['status']?.toString()),
                          _infoChip(
                            Icons.person_outline,
                            (schedule['assigned_architect_name'] ?? '-')
                                .toString(),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      _detailLine(
                        isSwahili ? 'Client' : 'Client',
                        schedule['client_name'],
                      ),
                      _detailLine(
                        isSwahili ? 'Start Date' : 'Start Date',
                        _formatDate(schedule['start_date']?.toString()),
                      ),
                      _detailLine(
                        isSwahili ? 'End Date' : 'End Date',
                        _formatDate(schedule['end_date']?.toString()),
                      ),
                      _detailLine(
                        isSwahili ? 'Notes' : 'Notes',
                        schedule['notes'],
                      ),
                      const SizedBox(height: 16),
                      Text(
                        isSwahili ? 'Maendeleo' : 'Progress',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 10),
                      _progressGrid(progress: progress, isSwahili: isSwahili),
                      const SizedBox(height: 20),
                      Text(
                        isSwahili ? 'Shughuli' : 'Activities',
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 12),
                      if (activities.isEmpty)
                        Text(
                          isSwahili
                              ? 'Hakuna shughuli zilizopatikana'
                              : 'No activities found',
                          style: const TextStyle(
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ...activities.map(
                        (activity) => Card(
                          margin: const EdgeInsets.only(bottom: 10),
                          child: Padding(
                            padding: const EdgeInsets.all(14),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            (activity['name'] ?? '-')
                                                .toString(),
                                            style: const TextStyle(
                                              fontWeight: FontWeight.w700,
                                            ),
                                          ),
                                          const SizedBox(height: 4),
                                          Text(
                                            '${activity['activity_code'] ?? '-'} • ${activity['phase'] ?? '-'}',
                                            style: const TextStyle(
                                              fontSize: 12,
                                              color: AppColors.textSecondary,
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    _statusChip(activity['status']?.toString()),
                                  ],
                                ),
                                const SizedBox(height: 10),
                                _detailLine(
                                  isSwahili ? 'Assigned To' : 'Assigned To',
                                  activity['assigned_user_name'],
                                ),
                                _detailLine(
                                  isSwahili ? 'Role' : 'Role',
                                  activity['role_name'],
                                ),
                                _detailLine(
                                  isSwahili ? 'Start' : 'Start',
                                  _formatDate(
                                    activity['start_date']?.toString(),
                                  ),
                                ),
                                _detailLine(
                                  isSwahili ? 'End' : 'End',
                                  _formatDate(activity['end_date']?.toString()),
                                ),
                                _detailLine(
                                  isSwahili ? 'Duration' : 'Duration',
                                  '${activity['duration_days'] ?? 0} days',
                                ),
                                _detailLine(
                                  isSwahili ? 'Notes' : 'Notes',
                                  activity['notes'] ??
                                      activity['completion_notes'],
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }

  Widget _progressGrid({
    required Map<String, dynamic> progress,
    required bool isSwahili,
  }) {
    final items = <Map<String, String>>[
      {
        'label': isSwahili ? 'Jumla' : 'Total',
        'value': '${progress['total'] ?? 0}',
      },
      {
        'label': isSwahili ? 'Imekamilika' : 'Completed',
        'value': '${progress['completed'] ?? 0}',
      },
      {
        'label': isSwahili ? 'Inaendelea' : 'In Progress',
        'value': '${progress['in_progress'] ?? 0}',
      },
      {
        'label': isSwahili ? 'Pending' : 'Pending',
        'value': '${progress['pending'] ?? 0}',
      },
      {
        'label': isSwahili ? 'Imechelewa' : 'Overdue',
        'value': '${progress['overdue'] ?? 0}',
      },
      {
        'label': isSwahili ? 'Asilimia' : 'Percent',
        'value': '${_toDouble(progress['percentage']).toStringAsFixed(1)}%',
      },
    ];

    return Wrap(
      spacing: 10,
      runSpacing: 10,
      children: items
          .map(
            (item) => Container(
              width: 140,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: AppColors.primary.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    item['label']!,
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    item['value']!,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ),
          )
          .toList(),
    );
  }

  Widget _detailLine(String label, dynamic value) {
    final text = (value ?? '').toString().trim();
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: RichText(
        text: TextSpan(
          style: const TextStyle(color: AppColors.textPrimary, fontSize: 13),
          children: [
            TextSpan(
              text: '$label: ',
              style: const TextStyle(fontWeight: FontWeight.w700),
            ),
            TextSpan(text: text.isEmpty ? '-' : text),
          ],
        ),
      ),
    );
  }
}

class _ScheduleErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ScheduleErrorView({
    required this.message,
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
        ),
        const SizedBox(height: 8),
        Text(message, textAlign: TextAlign.center),
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

Widget _statusChip(String? status) {
  final color = _statusColor(status);
  return Container(
    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
    decoration: BoxDecoration(
      color: color.withValues(alpha: 0.12),
      borderRadius: BorderRadius.circular(999),
    ),
    child: Text(
      _statusLabel(status),
      style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: color),
    ),
  );
}

Widget _infoChip(IconData icon, String label) {
  return Container(
    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
    decoration: BoxDecoration(
      color: AppColors.info.withValues(alpha: 0.12),
      borderRadius: BorderRadius.circular(999),
    ),
    child: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 14, color: AppColors.info),
        const SizedBox(width: 6),
        Text(
          label,
          style: const TextStyle(
            fontSize: 11,
            fontWeight: FontWeight.w600,
            color: AppColors.info,
          ),
        ),
      ],
    ),
  );
}

Color _statusColor(String? status) {
  switch ((status ?? '').toLowerCase()) {
    case 'completed':
      return AppColors.success;
    case 'confirmed':
      return AppColors.info;
    case 'in_progress':
      return AppColors.primary;
    case 'pending_confirmation':
    case 'pending':
      return AppColors.warning;
    case 'cancelled':
    case 'overdue':
      return AppColors.error;
    default:
      return AppColors.draft;
  }
}

String _statusLabel(String? status) {
  if (status == null || status.trim().isEmpty) return '-';
  return status
      .replaceAll('_', ' ')
      .split(' ')
      .map(
        (word) => word.isEmpty
            ? word
            : '${word[0].toUpperCase()}${word.substring(1).toLowerCase()}',
      )
      .join(' ');
}

String _formatDate(String? value) {
  if (value == null || value.isEmpty) return '-';
  try {
    return DateFormat('dd MMM yyyy').format(DateTime.parse(value));
  } catch (_) {
    return value;
  }
}

double _toDouble(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse(value?.toString() ?? '') ?? 0;
}

int _toInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}
