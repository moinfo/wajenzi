import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _siteDailyReportStatusProvider =
    StateProvider.autoDispose<String?>((ref) => null);

final _siteDailyReportsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final status = ref.watch(_siteDailyReportStatusProvider);
  final response = await api.get(
    '/site-daily-reports',
    queryParameters: {
      if (status != null && status.isNotEmpty) 'status': status,
    },
  );

  final data = response.data['data'];
  final reports = data is List
      ? data
      : (data is Map<String, dynamic> ? (data['data'] as List? ?? const []) : const []);

  return {
    'items': reports.cast<Map<String, dynamic>>(),
    'meta': response.data['meta'] as Map<String, dynamic>? ?? const {},
  };
});

final _siteDailyReportDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, reportId) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/site-daily-reports/$reportId');
  return response.data['data'] as Map<String, dynamic>? ?? const {};
});

class SiteDailyReportListScreen extends ConsumerWidget {
  const SiteDailyReportListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final reportsAsync = ref.watch(_siteDailyReportsProvider);
    final selectedStatus = ref.watch(_siteDailyReportStatusProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Ripoti za Kila Siku za Eneo' : 'Site Daily Reports'),
      ),
      body: Column(
        children: [
          _SiteDailyReportFilterBar(
            isSwahili: isSwahili,
            isDarkMode: isDarkMode,
            selectedStatus: selectedStatus,
            onChanged: (value) =>
                ref.read(_siteDailyReportStatusProvider.notifier).state = value,
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.refresh(_siteDailyReportsProvider.future),
              child: reportsAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => _SiteDailyReportErrorView(
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_siteDailyReportsProvider),
                ),
                data: (payload) {
                  final reports =
                      (payload['items'] as List).cast<Map<String, dynamic>>();
                  final meta =
                      payload['meta'] as Map<String, dynamic>? ?? const {};

                  if (reports.isEmpty) {
                    return ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: const EdgeInsets.all(32),
                      children: [
                        const SizedBox(height: 100),
                        Icon(
                          Icons.assignment_outlined,
                          size: 56,
                          color: isDarkMode ? Colors.white24 : Colors.grey[300],
                        ),
                        const SizedBox(height: 12),
                        Text(
                          isSwahili
                              ? 'Hakuna ripoti za eneo zilizopatikana'
                              : 'No site daily reports found',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                      ],
                    );
                  }

                  return ListView.builder(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    itemCount: reports.length + 2,
                    itemBuilder: (context, index) {
                      if (index == 0) {
                        final total = meta['total'] ?? reports.length;
                        return Padding(
                          padding: const EdgeInsets.only(bottom: 12),
                          child: Text(
                            isSwahili
                                ? 'Jumla ya ripoti: $total'
                                : 'Total reports: $total',
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                              color: isDarkMode
                                  ? Colors.white70
                                  : AppColors.textSecondary,
                            ),
                          ),
                        );
                      }

                      if (index == reports.length + 1) {
                        return const SizedBox(height: 90);
                      }

                      final report = reports[index - 1];
                      return _SiteDailyReportCard(
                        report: report,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                      );
                    },
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SiteDailyReportFilterBar extends StatelessWidget {
  final bool isSwahili;
  final bool isDarkMode;
  final String? selectedStatus;
  final ValueChanged<String?> onChanged;

  const _SiteDailyReportFilterBar({
    required this.isSwahili,
    required this.isDarkMode,
    required this.selectedStatus,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    final options = <String?, String>{
      null: isSwahili ? 'Zote' : 'All',
      'draft': isSwahili ? 'Rasimu' : 'Draft',
      'submitted': isSwahili ? 'Imewasilishwa' : 'Submitted',
      'pending': isSwahili ? 'Inasubiri' : 'Pending',
      'approved': isSwahili ? 'Imeidhinishwa' : 'Approved',
      'rejected': isSwahili ? 'Imekataliwa' : 'Rejected',
    };

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
      color: isDarkMode ? const Color(0xFF0F1923) : Colors.white,
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(
          children: options.entries.map((entry) {
            final selected = selectedStatus == entry.key;
            return Padding(
              padding: const EdgeInsets.only(right: 8),
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
                backgroundColor:
                    isDarkMode ? const Color(0xFF1A2332) : Colors.white,
              ),
            );
          }).toList(),
        ),
      ),
    );
  }
}

class _SiteDailyReportCard extends StatelessWidget {
  final Map<String, dynamic> report;
  final bool isSwahili;
  final bool isDarkMode;

  const _SiteDailyReportCard({
    required this.report,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    final site = report['site'] as Map<String, dynamic>?;
    final siteName = site?['name'] as String? ?? '-';
    final reportDate = report['report_date'] as String?;
    final status = (report['status'] as String? ?? 'draft').toLowerCase();
    final progress = _toInt(report['progress_percentage']);
    final preparedBy =
        (report['prepared_by_user'] as Map<String, dynamic>?)?['name'] as String? ??
            '-';
    final supervisor =
        (report['supervisor'] as Map<String, dynamic>?)?['name'] as String?;
    final nextSteps = report['next_steps'] as String?;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: () => _showSiteDailyReportDetails(
          context,
          reportId: _toInt(report['id']),
          isSwahili: isSwahili,
          isDarkMode: isDarkMode,
        ),
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          siteName,
                          style: Theme.of(context).textTheme.titleSmall?.copyWith(
                                fontWeight: FontWeight.bold,
                              ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          _formatDate(reportDate),
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                color: AppColors.textSecondary,
                              ),
                        ),
                      ],
                    ),
                  ),
                  _StatusBadge(status: status, isSwahili: isSwahili),
                ],
              ),
              const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  _InfoChip(
                    icon: Icons.trending_up_rounded,
                    label: isSwahili ? '$progress% maendeleo' : '$progress% progress',
                  ),
                  _InfoChip(
                    icon: Icons.person_outline_rounded,
                    label: preparedBy,
                  ),
                  if (supervisor != null && supervisor.isNotEmpty)
                    _InfoChip(
                      icon: Icons.badge_outlined,
                      label: supervisor,
                    ),
                ],
              ),
              if (nextSteps != null && nextSteps.isNotEmpty) ...[
                const SizedBox(height: 12),
                Text(
                  nextSteps,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: AppColors.textSecondary,
                      ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

void _showSiteDailyReportDetails(
  BuildContext context, {
  required int reportId,
  required bool isSwahili,
  required bool isDarkMode,
}) {
  showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    showDragHandle: true,
    builder: (context) => Consumer(
      builder: (context, ref, _) {
        final detailAsync = ref.watch(_siteDailyReportDetailProvider(reportId));

        return SafeArea(
          child: SizedBox(
            height: MediaQuery.of(context).size.height * 0.82,
            child: detailAsync.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (error, _) => _SiteDailyReportErrorView(
                isSwahili: isSwahili,
                onRetry: () => ref.invalidate(_siteDailyReportDetailProvider(reportId)),
              ),
              data: (report) {
                final site = report['site'] as Map<String, dynamic>?;
                final siteName = site?['name'] as String? ?? '-';
                final status = (report['status'] as String? ?? 'draft').toLowerCase();
                final activities =
                    (report['work_activities'] as List? ?? const [])
                        .cast<Map<String, dynamic>>();
                final materials =
                    (report['materials_used'] as List? ?? const [])
                        .cast<Map<String, dynamic>>();
                final payments =
                    (report['payments'] as List? ?? const [])
                        .cast<Map<String, dynamic>>();
                final laborNeeded =
                    (report['labor_needed'] as List? ?? const [])
                        .cast<Map<String, dynamic>>();

                return ListView(
                  padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
                  children: [
                    Text(
                      siteName,
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        _StatusBadge(status: status, isSwahili: isSwahili),
                        _InfoChip(
                          icon: Icons.calendar_today_rounded,
                          label: _formatDate(report['report_date'] as String?),
                        ),
                        _InfoChip(
                          icon: Icons.trending_up_rounded,
                          label:
                              '${_toInt(report['progress_percentage'])}% ${isSwahili ? 'maendeleo' : 'progress'}',
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),
                    _DetailSection(
                      title: isSwahili ? 'Muhtasari' : 'Overview',
                      children: [
                        _DetailRow(
                          isSwahili ? 'Aliyeandaa' : 'Prepared By',
                          (report['prepared_by_user']
                                      as Map<String, dynamic>?)?['name']
                                  as String? ??
                              '-',
                        ),
                        _DetailRow(
                          isSwahili ? 'Msimamizi' : 'Supervisor',
                          (report['supervisor'] as Map<String, dynamic>?)?['name']
                                  as String? ??
                              '-',
                        ),
                        _DetailRow(
                          isSwahili ? 'Hatua Zifuatazo' : 'Next Steps',
                          report['next_steps'] as String? ?? '-',
                        ),
                        _DetailRow(
                          isSwahili ? 'Changamoto' : 'Challenges',
                          report['challenges'] as String? ?? '-',
                        ),
                      ],
                    ),
                    if (activities.isNotEmpty) ...[
                      const SizedBox(height: 16),
                      _DetailSection(
                        title: isSwahili ? 'Shughuli za Kazi' : 'Work Activities',
                        children: activities
                            .map(
                              (activity) => _DetailRow(
                                activity['activity_name'] as String? ?? '-',
                                [
                                  activity['description'] as String?,
                                  if (_toInt(activity['workers_count']) > 0)
                                    '${_toInt(activity['workers_count'])} ${isSwahili ? 'wafanyakazi' : 'workers'}',
                                ].whereType<String>().where((value) => value.isNotEmpty).join(' - '),
                              ),
                            )
                            .toList(),
                      ),
                    ],
                    if (materials.isNotEmpty) ...[
                      const SizedBox(height: 16),
                      _DetailSection(
                        title: isSwahili ? 'Vifaa Vilivyotumika' : 'Materials Used',
                        children: materials
                            .map(
                              (material) => _DetailRow(
                                material['material_name'] as String? ?? '-',
                                [
                                  '${_toDouble(material['quantity']).toStringAsFixed(0)} ${material['unit'] as String? ?? ''}'.trim(),
                                  if (_toDouble(material['total_cost']) > 0)
                                    _formatCurrency(_toDouble(material['total_cost'])),
                                ].where((value) => value.isNotEmpty).join(' - '),
                              ),
                            )
                            .toList(),
                      ),
                    ],
                    if (payments.isNotEmpty) ...[
                      const SizedBox(height: 16),
                      _DetailSection(
                        title: isSwahili ? 'Malipo' : 'Payments',
                        children: payments
                            .map(
                              (payment) => _DetailRow(
                                payment['recipient_name'] as String? ?? '-',
                                [
                                  _formatCurrency(_toDouble(payment['amount'])),
                                  payment['payment_type'] as String?,
                                  payment['payment_method'] as String?,
                                ].whereType<String>().where((value) => value.isNotEmpty).join(' - '),
                              ),
                            )
                            .toList(),
                      ),
                    ],
                    if (laborNeeded.isNotEmpty) ...[
                      const SizedBox(height: 16),
                      _DetailSection(
                        title: isSwahili ? 'Kazi Inayohitajika' : 'Labor Needed',
                        children: laborNeeded
                            .map(
                              (labor) => _DetailRow(
                                labor['labor_type'] as String? ?? '-',
                                [
                                  '${_toInt(labor['quantity'])} ${isSwahili ? 'watu' : 'people'}',
                                  if (_toDouble(labor['total_cost']) > 0)
                                    _formatCurrency(_toDouble(labor['total_cost'])),
                                ].where((value) => value.isNotEmpty).join(' - '),
                              ),
                            )
                            .toList(),
                      ),
                    ],
                  ],
                );
              },
            ),
          ),
        );
      },
    ),
  );
}

class _StatusBadge extends StatelessWidget {
  final String status;
  final bool isSwahili;

  const _StatusBadge({
    required this.status,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    final color = _statusColor(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        _statusLabel(status, isSwahili),
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }
}

class _InfoChip extends StatelessWidget {
  final IconData icon;
  final String label;

  const _InfoChip({
    required this.icon,
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: AppColors.primary.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: AppColors.primary),
          const SizedBox(width: 6),
          Text(
            label,
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }
}

class _DetailSection extends StatelessWidget {
  final String title;
  final List<Widget> children;

  const _DetailSection({
    required this.title,
    required this.children,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.grey.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 12),
          ...children,
        ],
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _DetailRow(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            flex: 4,
            child: Text(
              label,
              style: const TextStyle(
                color: AppColors.textSecondary,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            flex: 6,
            child: Text(
              value.isEmpty ? '-' : value,
              style: const TextStyle(fontSize: 14),
              textAlign: TextAlign.right,
            ),
          ),
        ],
      ),
    );
  }
}

class _SiteDailyReportErrorView extends StatelessWidget {
  final bool isSwahili;
  final VoidCallback onRetry;

  const _SiteDailyReportErrorView({
    required this.isSwahili,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.error_outline, size: 64, color: AppColors.error),
            const SizedBox(height: 12),
            Text(
              isSwahili
                  ? 'Imeshindikana kupakia ripoti za eneo'
                  : 'Failed to load site daily reports',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
            ),
            const SizedBox(height: 12),
            ElevatedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
            ),
          ],
        ),
      ),
    );
  }
}

String _formatDate(String? date) {
  if (date == null || date.isEmpty) return '-';
  try {
    return DateFormat('dd MMM yyyy').format(DateTime.parse(date));
  } catch (_) {
    return date;
  }
}

String _formatCurrency(double amount) {
  final formatter = NumberFormat('#,##0.00', 'en');
  return 'TZS ${formatter.format(amount)}';
}

int _toInt(dynamic value) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value) ?? 0;
  return 0;
}

double _toDouble(dynamic value) {
  if (value is double) return value;
  if (value is num) return value.toDouble();
  if (value is String) return double.tryParse(value) ?? 0;
  return 0;
}

Color _statusColor(String status) {
  switch (status.toLowerCase()) {
    case 'approved':
      return AppColors.success;
    case 'submitted':
    case 'pending':
      return AppColors.warning;
    case 'rejected':
      return AppColors.error;
    default:
      return AppColors.textSecondary;
  }
}

String _statusLabel(String status, bool isSwahili) {
  switch (status.toLowerCase()) {
    case 'draft':
      return isSwahili ? 'Rasimu' : 'Draft';
    case 'submitted':
      return isSwahili ? 'Imewasilishwa' : 'Submitted';
    case 'pending':
      return isSwahili ? 'Inasubiri' : 'Pending';
    case 'approved':
      return isSwahili ? 'Imeidhinishwa' : 'Approved';
    case 'rejected':
      return isSwahili ? 'Imekataliwa' : 'Rejected';
    default:
      return status.isEmpty ? '-' : status;
  }
}
