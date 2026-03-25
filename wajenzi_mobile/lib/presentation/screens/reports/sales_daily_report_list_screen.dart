import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _salesDailyReportStatusProvider =
    StateProvider.autoDispose<String?>((ref) => null);

final _salesDailyReportsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final status = ref.watch(_salesDailyReportStatusProvider);
  final response = await api.get(
    '/sales-daily-reports',
    queryParameters: {
      if (status != null && status.isNotEmpty) 'status': status,
    },
  );

  return {
    'items': (response.data['data'] as List? ?? const [])
        .cast<Map<String, dynamic>>(),
    'meta': response.data['meta'] as Map<String, dynamic>? ?? const {},
  };
});

class SalesDailyReportListScreen extends ConsumerWidget {
  const SalesDailyReportListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final reportsAsync = ref.watch(_salesDailyReportsProvider);
    final selectedStatus = ref.watch(_salesDailyReportStatusProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          isSwahili ? 'Ripoti za Mauzo za Kila Siku' : 'Sales Daily Reports',
        ),
      ),
      body: Column(
        children: [
          _SalesDailyReportFilterBar(
            isSwahili: isSwahili,
            isDarkMode: isDarkMode,
            selectedStatus: selectedStatus,
            onChanged: (value) =>
                ref.read(_salesDailyReportStatusProvider.notifier).state = value,
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.refresh(_salesDailyReportsProvider.future),
              child: reportsAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (_, __) => _SalesDailyReportErrorView(
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_salesDailyReportsProvider),
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
                          Icons.trending_up_outlined,
                          size: 56,
                          color: isDarkMode ? Colors.white24 : Colors.grey[300],
                        ),
                        const SizedBox(height: 12),
                        Text(
                          isSwahili
                              ? 'Hakuna ripoti za mauzo zilizopatikana'
                              : 'No sales daily reports found',
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
                      return _SalesDailyReportCard(
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

class _SalesDailyReportFilterBar extends StatelessWidget {
  final bool isSwahili;
  final bool isDarkMode;
  final String? selectedStatus;
  final ValueChanged<String?> onChanged;

  const _SalesDailyReportFilterBar({
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

class _SalesDailyReportCard extends StatelessWidget {
  final Map<String, dynamic> report;
  final bool isSwahili;
  final bool isDarkMode;

  const _SalesDailyReportCard({
    required this.report,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    final date = report['report_date'] as String? ?? '-';
    final status = (report['status'] as String? ?? 'draft').toLowerCase();
    final preparedBy =
        (report['prepared_by'] as Map<String, dynamic>?)?['name'] as String? ??
            '-';
    final summary = report['daily_summary'] as String? ??
        report['notes'] as String? ??
        report['notes_recommendations'] as String? ??
        '-';

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    date,
                    style: TextStyle(
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: isDarkMode ? Colors.white : AppColors.textPrimary,
                    ),
                  ),
                ),
                _StatusBadge(status: status),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              summary,
              maxLines: 3,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                const Icon(
                  Icons.person_outline,
                  size: 16,
                  color: AppColors.primary,
                ),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    preparedBy,
                    style: TextStyle(
                      fontSize: 12,
                      color:
                          isDarkMode ? Colors.white70 : AppColors.textPrimary,
                    ),
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

class _StatusBadge extends StatelessWidget {
  final String status;

  const _StatusBadge({required this.status});

  @override
  Widget build(BuildContext context) {
    final color = switch (status) {
      'approved' => AppColors.success,
      'pending' => AppColors.warning,
      'rejected' => AppColors.error,
      _ => AppColors.textSecondary,
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        status.toUpperCase(),
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: color,
        ),
      ),
    );
  }
}

class _SalesDailyReportErrorView extends StatelessWidget {
  final bool isSwahili;
  final VoidCallback onRetry;

  const _SalesDailyReportErrorView({
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
