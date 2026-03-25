import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _projectSchedulesProvider = FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/project-schedules');
  final data = response.data is Map<String, dynamic> ? response.data as Map<String, dynamic> : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];

  return items.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
});

final _projectScheduleDetailProvider = FutureProvider.autoDispose.family<Map<String, dynamic>, int>((ref, id) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/project-schedules/$id');
  final data = response.data is Map<String, dynamic> ? response.data as Map<String, dynamic> : const <String, dynamic>{};
  return data['data'] is Map ? Map<String, dynamic>.from(data['data'] as Map) : const <String, dynamic>{};
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

class ProjectSchedulesScreen extends ConsumerWidget {
  const ProjectSchedulesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final schedulesAsync = ref.watch(_projectSchedulesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Ratiba za Miradi' : 'Project Schedules'),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_projectSchedulesProvider),
        child: schedulesAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _ScheduleErrorView(
            message: _scheduleErrorMessage(error, isSwahili),
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_projectSchedulesProvider),
          ),
          data: (items) {
            if (items.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(
                    Icons.calendar_month_outlined,
                    size: 56,
                    color: isDarkMode ? Colors.white24 : Colors.grey[300],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hakuna ratiba zilizopatikana' : 'No project schedules found',
                    textAlign: TextAlign.center,
                  ),
                ],
              );
            }

            return ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: items.length + 1,
              itemBuilder: (context, index) {
                if (index == items.length) {
                  return const SizedBox(height: 80);
                }

                final schedule = items[index];
                return _ScheduleCard(
                  schedule: schedule,
                  isSwahili: isSwahili,
                  onTap: () => _showDetails(context, ref, _toInt(schedule['id'])),
                );
              },
            );
          },
        ),
      ),
    );
  }

  void _showDetails(BuildContext context, WidgetRef ref, int id) {
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
}

class _ScheduleCard extends StatelessWidget {
  final Map<String, dynamic> schedule;
  final bool isSwahili;
  final VoidCallback onTap;

  const _ScheduleCard({
    required this.schedule,
    required this.isSwahili,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final progress = schedule['progress'] is Map ? Map<String, dynamic>.from(schedule['progress'] as Map) : const <String, dynamic>{};
    final percent = _toDouble(progress['percentage']);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: onTap,
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
                          (schedule['lead_number'] ?? schedule['lead_name'] ?? '-').toString(),
                          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          (schedule['client_name'] ?? schedule['assigned_architect_name'] ?? '-').toString(),
                          style: const TextStyle(fontSize: 13, color: AppColors.textSecondary),
                        ),
                      ],
                    ),
                  ),
                  _statusChip(schedule['status']?.toString()),
                ],
              ),
              const SizedBox(height: 12),
              _metaRow(isSwahili ? 'Architect' : 'Architect', schedule['assigned_architect_name']),
              _metaRow(isSwahili ? 'Start' : 'Start', _formatDate(schedule['start_date']?.toString())),
              _metaRow(isSwahili ? 'End' : 'End', _formatDate(schedule['end_date']?.toString())),
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
                isSwahili ? 'Maendeleo: ${percent.toStringAsFixed(1)}%' : 'Progress: ${percent.toStringAsFixed(1)}%',
                style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
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
      child: Text('$label: ${text.isEmpty ? '-' : text}', style: const TextStyle(fontSize: 13)),
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
            final progress = schedule['progress'] is Map ? Map<String, dynamic>.from(schedule['progress'] as Map) : const <String, dynamic>{};
            final activities = (schedule['activities'] as List? ?? const []).whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();

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
                      Text(
                        (schedule['lead_number'] ?? schedule['lead_name'] ?? '-').toString(),
                        style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          _statusChip(schedule['status']?.toString()),
                          _infoChip(Icons.person_outline, (schedule['assigned_architect_name'] ?? '-').toString()),
                        ],
                      ),
                      const SizedBox(height: 16),
                      _detailLine(isSwahili ? 'Client' : 'Client', schedule['client_name']),
                      _detailLine(isSwahili ? 'Start Date' : 'Start Date', _formatDate(schedule['start_date']?.toString())),
                      _detailLine(isSwahili ? 'End Date' : 'End Date', _formatDate(schedule['end_date']?.toString())),
                      _detailLine(isSwahili ? 'Notes' : 'Notes', schedule['notes']),
                      const SizedBox(height: 16),
                      Text(
                        isSwahili ? 'Maendeleo' : 'Progress',
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(height: 10),
                      _progressGrid(progress: progress, isSwahili: isSwahili),
                      const SizedBox(height: 20),
                      Text(
                        isSwahili ? 'Shughuli' : 'Activities',
                        style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(height: 12),
                      if (activities.isEmpty)
                        Text(
                          isSwahili ? 'Hakuna shughuli zilizopatikana' : 'No activities found',
                          style: const TextStyle(color: AppColors.textSecondary),
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
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            (activity['name'] ?? '-').toString(),
                                            style: const TextStyle(fontWeight: FontWeight.w700),
                                          ),
                                          const SizedBox(height: 4),
                                          Text(
                                            '${activity['activity_code'] ?? '-'} • ${activity['phase'] ?? '-'}',
                                            style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                                          ),
                                        ],
                                      ),
                                    ),
                                    _statusChip(activity['status']?.toString()),
                                  ],
                                ),
                                const SizedBox(height: 10),
                                _detailLine(isSwahili ? 'Assigned To' : 'Assigned To', activity['assigned_user_name']),
                                _detailLine(isSwahili ? 'Role' : 'Role', activity['role_name']),
                                _detailLine(isSwahili ? 'Start' : 'Start', _formatDate(activity['start_date']?.toString())),
                                _detailLine(isSwahili ? 'End' : 'End', _formatDate(activity['end_date']?.toString())),
                                _detailLine(isSwahili ? 'Duration' : 'Duration', '${activity['duration_days'] ?? 0} days'),
                                _detailLine(isSwahili ? 'Notes' : 'Notes', activity['notes'] ?? activity['completion_notes']),
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

  Widget _progressGrid({required Map<String, dynamic> progress, required bool isSwahili}) {
    final items = <Map<String, String>>[
      {'label': isSwahili ? 'Jumla' : 'Total', 'value': '${progress['total'] ?? 0}'},
      {'label': isSwahili ? 'Imekamilika' : 'Completed', 'value': '${progress['completed'] ?? 0}'},
      {'label': isSwahili ? 'Inaendelea' : 'In Progress', 'value': '${progress['in_progress'] ?? 0}'},
      {'label': isSwahili ? 'Pending' : 'Pending', 'value': '${progress['pending'] ?? 0}'},
      {'label': isSwahili ? 'Imechelewa' : 'Overdue', 'value': '${progress['overdue'] ?? 0}'},
      {'label': isSwahili ? 'Asilimia' : 'Percent', 'value': '${_toDouble(progress['percentage']).toStringAsFixed(1)}%'},
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
                  Text(item['label']!, style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
                  const SizedBox(height: 4),
                  Text(item['value']!, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700)),
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
            TextSpan(text: '$label: ', style: const TextStyle(fontWeight: FontWeight.w700)),
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
      padding: const EdgeInsets.all(32),
      children: [
        const SizedBox(height: 100),
        const Icon(Icons.error_outline, size: 64, color: AppColors.error),
        const SizedBox(height: 16),
        Text(isSwahili ? 'Hitilafu imetokea' : 'Something went wrong', textAlign: TextAlign.center),
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
        Text(label, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: AppColors.info)),
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
  if (status == null || status.trim().isEmpty) {
    return '-';
  }

  return status
      .replaceAll('_', ' ')
      .split(' ')
      .map((word) => word.isEmpty ? word : '${word[0].toUpperCase()}${word.substring(1).toLowerCase()}')
      .join(' ');
}

String _formatDate(String? value) {
  if (value == null || value.isEmpty) {
    return '-';
  }

  try {
    return DateFormat('dd MMM yyyy').format(DateTime.parse(value));
  } catch (_) {
    return value;
  }
}

double _toDouble(dynamic value) {
  if (value is num) {
    return value.toDouble();
  }

  return double.tryParse(value?.toString() ?? '') ?? 0;
}

int _toInt(dynamic value) {
  if (value is int) {
    return value;
  }

  return int.tryParse(value?.toString() ?? '') ?? 0;
}
