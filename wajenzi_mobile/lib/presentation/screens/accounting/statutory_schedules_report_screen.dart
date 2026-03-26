import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _statutorySchedulesReportYearProvider = StateProvider.autoDispose<int>(
  (ref) => DateTime.now().year,
);

final _statutorySchedulesReportProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final year = ref.watch(_statutorySchedulesReportYearProvider);
  final response = await api.get(
    '/reports/statutory-schedules-report',
    queryParameters: {'year': year},
  );
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

class StatutorySchedulesReportScreen extends ConsumerWidget {
  const StatutorySchedulesReportScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final selectedYear = ref.watch(_statutorySchedulesReportYearProvider);
    final reportAsync = ref.watch(_statutorySchedulesReportProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          isSwahili
              ? 'Ratiba za Malipo ya Kisheria'
              : 'Statutory Schedules Report',
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_statutorySchedulesReportProvider),
        child: reportAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _ReportErrorView(
            isSwahili: isSwahili,
            message: vatErrorMessage(error, isSwahili: isSwahili),
            onRetry: () => ref.invalidate(_statutorySchedulesReportProvider),
          ),
          data: (report) {
            final items = (report['items'] as List? ?? const [])
                .whereType<Map>()
                .map((item) => Map<String, dynamic>.from(item))
                .toList();
            final years = (report['available_years'] as List? ?? const [])
                .map((item) => int.tryParse(item.toString()) ?? DateTime.now().year)
                .toList();

            return ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          isSwahili ? 'Vichujio' : 'Filters',
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 12),
                        DropdownButtonFormField<int>(
                          value: years.contains(selectedYear) ? selectedYear : null,
                          isExpanded: true,
                          decoration: InputDecoration(
                            labelText: isSwahili ? 'Mwaka' : 'Year',
                            filled: true,
                            fillColor: isDarkMode
                                ? Colors.white.withValues(alpha: 0.05)
                                : Colors.grey.withValues(alpha: 0.08),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(14),
                              borderSide: BorderSide.none,
                            ),
                          ),
                          items: years
                              .map(
                                (year) => DropdownMenuItem<int>(
                                  value: year,
                                  child: Text(year.toString()),
                                ),
                              )
                              .toList(),
                          onChanged: (value) {
                            if (value == null) return;
                            ref.read(_statutorySchedulesReportYearProvider.notifier).state =
                                value;
                          },
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                if (items.isEmpty)
                  _EmptyState(isDarkMode: isDarkMode, isSwahili: isSwahili)
                else
                  ...items.map(
                    (item) => Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              item['name']?.toString() ?? '-',
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              item['sub_category_name']?.toString() ?? '-',
                              style: const TextStyle(
                                color: AppColors.textSecondary,
                              ),
                            ),
                            const SizedBox(height: 12),
                            Wrap(
                              spacing: 12,
                              runSpacing: 8,
                              children: [
                                _MiniMetric(
                                  label: 'Annual',
                                  value: vatMoney(item['per_annually']),
                                ),
                                _MiniMetric(
                                  label: 'Monthly',
                                  value: vatMoney(item['per_monthly']),
                                ),
                                _MiniMetric(
                                  label: 'Per Bill',
                                  value: vatMoney(item['per_bill']),
                                ),
                                _MiniMetric(
                                  label: 'Cycle',
                                  value: item['billing_cycle_name']?.toString() ?? '-',
                                ),
                                _MiniMetric(
                                  label: 'Total',
                                  value: vatMoney(item['total']),
                                ),
                              ],
                            ),
                            const SizedBox(height: 12),
                            Text(
                              'Monthly Schedule',
                              style: const TextStyle(
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const SizedBox(height: 8),
                            ...((item['monthly'] as List? ?? const [])
                                .whereType<Map>()
                                .map((entry) => Map<String, dynamic>.from(entry))
                                .map(
                                  (entry) => Container(
                                    margin: const EdgeInsets.only(bottom: 8),
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 12,
                                      vertical: 10,
                                    ),
                                    decoration: BoxDecoration(
                                      color: (entry['is_paid'] == true
                                              ? AppColors.success
                                              : AppColors.error)
                                          .withValues(alpha: 0.08),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Row(
                                      children: [
                                        Icon(
                                          entry['is_paid'] == true
                                              ? Icons.check_circle
                                              : Icons.cancel,
                                          size: 18,
                                          color: entry['is_paid'] == true
                                              ? AppColors.success
                                              : AppColors.error,
                                        ),
                                        const SizedBox(width: 10),
                                        Expanded(
                                          child: Text(
                                            entry['label']?.toString() ?? '-',
                                            style: const TextStyle(
                                              fontWeight: FontWeight.w600,
                                            ),
                                          ),
                                        ),
                                        Text(
                                          vatMoney(entry['amount']),
                                          style: const TextStyle(
                                            fontWeight: FontWeight.w700,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                )),
                          ],
                        ),
                      ),
                    ),
                  ),
                const SizedBox(height: 80),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _MiniMetric extends StatelessWidget {
  final String label;
  final String value;

  const _MiniMetric({
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return RichText(
      text: TextSpan(
        style: DefaultTextStyle.of(context).style,
        children: [
          TextSpan(
            text: '$label: ',
            style: const TextStyle(
              color: AppColors.textSecondary,
              fontWeight: FontWeight.w600,
            ),
          ),
          TextSpan(
            text: value,
            style: const TextStyle(fontWeight: FontWeight.w700),
          ),
        ],
      ),
    );
  }
}

class _ReportErrorView extends StatelessWidget {
  final bool isSwahili;
  final String message;
  final VoidCallback onRetry;

  const _ReportErrorView({
    required this.isSwahili,
    required this.message,
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
            const Icon(Icons.error_outline, size: 52, color: AppColors.error),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
            ),
          ],
        ),
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  final bool isDarkMode;
  final bool isSwahili;

  const _EmptyState({
    required this.isDarkMode,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            Icon(
              Icons.event_note_outlined,
              size: 56,
              color: isDarkMode ? Colors.white24 : Colors.black12,
            ),
            const SizedBox(height: 12),
            Text(
              isSwahili ? 'Hakuna data ya ratiba' : 'No schedules found',
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}
