import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _crdbStartDateProvider = StateProvider.autoDispose<DateTime>(
  (ref) => DateTime(DateTime.now().year, DateTime.now().month, 1),
);

final _crdbEndDateProvider = StateProvider.autoDispose<DateTime>(
  (ref) => DateTime(DateTime.now().year, DateTime.now().month + 1, 0),
);

final _crdbBankFileProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final start = ref.watch(_crdbStartDateProvider);
  final end = ref.watch(_crdbEndDateProvider);
  final response = await api.get(
    '/payroll/crdb-bank-file',
    queryParameters: {
      'start_date': DateFormat('yyyy-MM-dd').format(start),
      'end_date': DateFormat('yyyy-MM-dd').format(end),
    },
  );
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

class CrdbBankFileScreen extends ConsumerWidget {
  const CrdbBankFileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final reportAsync = ref.watch(_crdbBankFileProvider);
    final startDate = ref.watch(_crdbStartDateProvider);
    final endDate = ref.watch(_crdbEndDateProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Faili ya Benki CRDB' : 'CRDB Bank File'),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Expanded(
                  child: _DateButton(
                    label: isSwahili ? 'Mwanzo' : 'Start Date',
                    value: startDate,
                    isDarkMode: isDarkMode,
                    onTap: () async {
                      final picked = await showDatePicker(
                        context: context,
                        initialDate: startDate,
                        firstDate: DateTime(2020),
                        lastDate: DateTime(2100),
                      );
                      if (picked != null) {
                        ref.read(_crdbStartDateProvider.notifier).state = picked;
                      }
                    },
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _DateButton(
                    label: isSwahili ? 'Mwisho' : 'End Date',
                    value: endDate,
                    isDarkMode: isDarkMode,
                    onTap: () async {
                      final picked = await showDatePicker(
                        context: context,
                        initialDate: endDate,
                        firstDate: DateTime(2020),
                        lastDate: DateTime(2100),
                      );
                      if (picked != null) {
                        ref.read(_crdbEndDateProvider.notifier).state = picked;
                      }
                    },
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async => ref.invalidate(_crdbBankFileProvider),
              child: reportAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => _CrdbErrorView(
                  isSwahili: isSwahili,
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  onRetry: () => ref.invalidate(_crdbBankFileProvider),
                ),
                data: (data) {
                  final rows =
                      (data['rows'] as List? ?? const []).cast<Map<String, dynamic>>();
                  final totalAmount = (data['total_amount'] as num?)?.toDouble() ?? 0;

                  return ListView(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                    children: [
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: AppColors.primary.withValues(alpha: 0.08),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: AppColors.primary.withValues(alpha: 0.14),
                          ),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              isSwahili ? 'Muhtasari' : 'Summary',
                              style: const TextStyle(
                                fontWeight: FontWeight.w700,
                                fontSize: 16,
                              ),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              isSwahili
                                  ? 'Jumla ya wafanyakazi: ${rows.length}'
                                  : 'Total staff: ${rows.length}',
                            ),
                            Text(
                              isSwahili
                                  ? 'Jumla ya malipo: ${_money(totalAmount)}'
                                  : 'Total amount: ${_money(totalAmount)}',
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),
                      if (rows.isEmpty)
                        Padding(
                          padding: const EdgeInsets.symmetric(vertical: 80),
                          child: Column(
                            children: [
                              Icon(
                                Icons.account_balance_outlined,
                                size: 56,
                                color: isDarkMode ? Colors.white24 : Colors.grey[300],
                              ),
                              const SizedBox(height: 12),
                              Text(
                                isSwahili
                                    ? 'Hakuna data ya faili ya CRDB'
                                    : 'No CRDB bank file data found',
                                textAlign: TextAlign.center,
                              ),
                            ],
                          ),
                        )
                      else
                        ...rows.map(
                          (row) => Card(
                            margin: const EdgeInsets.only(bottom: 12),
                            child: ListTile(
                              contentPadding: const EdgeInsets.all(16),
                              leading: CircleAvatar(
                                backgroundColor:
                                    AppColors.primary.withValues(alpha: 0.1),
                                child: const Icon(
                                  Icons.account_balance,
                                  color: AppColors.primary,
                                ),
                              ),
                              title: Text(
                                row['staff_name']?.toString() ?? '-',
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: const TextStyle(fontWeight: FontWeight.w700),
                              ),
                              subtitle: Text(
                                '${row['account_number'] ?? '-'}\n${row['details'] ?? 'SALARY'}',
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                              trailing: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Text(
                                    _money((row['amount'] as num?)?.toDouble() ?? 0),
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w700,
                                      color: AppColors.primary,
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    'B:${row['bank_code'] ?? '3'} • Br:${row['branch_code'] ?? '3'}',
                                    style: const TextStyle(
                                      color: AppColors.textSecondary,
                                      fontSize: 12,
                                    ),
                                  ),
                                ],
                              ),
                              onTap: () => _showRowDetail(context, row, isDarkMode),
                            ),
                          ),
                        ),
                    ],
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

class _DateButton extends StatelessWidget {
  final String label;
  final DateTime value;
  final bool isDarkMode;
  final VoidCallback onTap;

  const _DateButton({
    required this.label,
    required this.value,
    required this.isDarkMode,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      borderRadius: BorderRadius.circular(12),
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: isDarkMode ? const Color(0xFF1A2332) : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.grey.withValues(alpha: 0.18)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              label,
              style: const TextStyle(
                color: AppColors.textSecondary,
                fontSize: 12,
              ),
            ),
            const SizedBox(height: 6),
            Text(DateFormat('dd MMM yyyy').format(value)),
          ],
        ),
      ),
    );
  }
}

void _showRowDetail(
  BuildContext context,
  Map<String, dynamic> row,
  bool isDarkMode,
) {
  showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.56,
      child: Container(
        decoration: BoxDecoration(
          color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: SafeArea(
          top: false,
          child: Column(
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
                      row['staff_name']?.toString() ?? '-',
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 18),
                    _CrdbDetailRow('Account', row['account_number']?.toString() ?? '-'),
                    _CrdbDetailRow('Amount', _money((row['amount'] as num?)?.toDouble() ?? 0)),
                    _CrdbDetailRow('Bank', row['bank_name']?.toString() ?? 'CRDB'),
                    _CrdbDetailRow('Bank Code', row['bank_code']?.toString() ?? '3'),
                    _CrdbDetailRow('Branch', row['branch']?.toString().trim().isNotEmpty == true ? row['branch'].toString() : '-'),
                    _CrdbDetailRow('Branch Code', row['branch_code']?.toString() ?? '3'),
                    _CrdbDetailRow('Details', row['details']?.toString() ?? 'SALARY'),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    ),
  );
}

class _CrdbDetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _CrdbDetailRow(this.label, this.value);

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
                color: AppColors.textSecondary,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          Expanded(child: Text(value)),
        ],
      ),
    );
  }
}

class _CrdbErrorView extends StatelessWidget {
  final bool isSwahili;
  final String message;
  final VoidCallback onRetry;

  const _CrdbErrorView({
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
            const Icon(Icons.error_outline, size: 64, color: AppColors.error),
            const SizedBox(height: 12),
            Text(
              isSwahili
                  ? 'Imeshindikana kupakia faili ya CRDB'
                  : 'Failed to load CRDB bank file',
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            Text(message, textAlign: TextAlign.center),
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

String _money(double value) => NumberFormat('#,##0.00').format(value);
