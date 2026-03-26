import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _statutoryCategoryReportYearProvider = StateProvider.autoDispose<int>(
  (ref) => DateTime.now().year,
);

final _statutoryCategoryReportProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final year = ref.watch(_statutoryCategoryReportYearProvider);
  final response = await api.get(
    '/reports/statutory-category-report',
    queryParameters: {'year': year},
  );
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

class StatutoryCategoryReportScreen extends ConsumerWidget {
  const StatutoryCategoryReportScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final selectedYear = ref.watch(_statutoryCategoryReportYearProvider);
    final reportAsync = ref.watch(_statutoryCategoryReportProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(
          isSwahili
              ? 'Ripoti ya Kategoria za Kisheria'
              : 'Statutory Category Report',
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_statutoryCategoryReportProvider),
        child: reportAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _ReportErrorView(
            isSwahili: isSwahili,
            message: vatErrorMessage(error, isSwahili: isSwahili),
            onRetry: () => ref.invalidate(_statutoryCategoryReportProvider),
          ),
          data: (report) {
            final categories = (report['categories'] as List? ?? const [])
                .whereType<Map>()
                .map((item) => Map<String, dynamic>.from(item))
                .toList();
            final rows = (report['rows'] as List? ?? const [])
                .whereType<Map>()
                .map((item) => Map<String, dynamic>.from(item))
                .toList();
            final footer = (report['footer'] as List? ?? const [])
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
                            ref.read(_statutoryCategoryReportYearProvider.notifier).state =
                                value;
                          },
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          isSwahili ? 'Jumla ya Mwaka' : 'Year Summary',
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 12),
                        Text(
                          '${isSwahili ? 'Jumla' : 'Total'}: ${vatMoney(report['year_total'])}',
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w800,
                            color: AppColors.primary,
                          ),
                        ),
                        const SizedBox(height: 12),
                        ...footer.map(
                          (item) => Padding(
                            padding: const EdgeInsets.only(bottom: 8),
                            child: _SummaryRow(
                              label: item['category_name']?.toString() ?? '-',
                              value: vatMoney(item['amount']),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                if (rows.isEmpty)
                  _EmptyState(isDarkMode: isDarkMode, isSwahili: isSwahili)
                else
                  ...rows.map(
                    (row) => Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              row['label']?.toString() ?? '-',
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              '${isSwahili ? 'Jumla' : 'Total'}: ${vatMoney(row['total'])}',
                              style: const TextStyle(
                                color: AppColors.primary,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const SizedBox(height: 12),
                            ...((row['categories'] as List? ?? const [])
                                .whereType<Map>()
                                .map((item) => Map<String, dynamic>.from(item))
                                .map(
                                  (item) => Padding(
                                    padding: const EdgeInsets.only(bottom: 8),
                                    child: _SummaryRow(
                                      label:
                                          item['category_name']?.toString() ?? '-',
                                      value: vatMoney(item['amount']),
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

class _SummaryRow extends StatelessWidget {
  final String label;
  final String value;

  const _SummaryRow({
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          child: Text(
            label,
            style: const TextStyle(
              color: AppColors.textSecondary,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
        const SizedBox(width: 12),
        Text(
          value,
          style: const TextStyle(fontWeight: FontWeight.w700),
        ),
      ],
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
              Icons.bar_chart_outlined,
              size: 56,
              color: isDarkMode ? Colors.white24 : Colors.black12,
            ),
            const SizedBox(height: 12),
            Text(
              isSwahili ? 'Hakuna data ya ripoti' : 'No report data found',
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}
