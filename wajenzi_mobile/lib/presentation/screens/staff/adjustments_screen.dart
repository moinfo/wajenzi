import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _adjustmentsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/adjustments');
  return (response.data['data'] as List).cast<Map<String, dynamic>>();
});

final _adjustmentProvider =
    FutureProvider.autoDispose.family<Map<String, dynamic>, int>((ref, id) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/adjustments/$id');
  return response.data['data'] as Map<String, dynamic>? ?? const {};
});

class AdjustmentsScreen extends ConsumerWidget {
  const AdjustmentsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final adjustmentsAsync = ref.watch(_adjustmentsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Marekebisho' : 'Adjustments'),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_adjustmentsProvider.future),
        child: adjustmentsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _AdjustmentErrorView(
            error: error,
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_adjustmentsProvider),
          ),
          data: (items) {
            if (items.isEmpty) {
              return _AdjustmentEmptyView(
                label: isSwahili
                    ? 'Hakuna marekebisho yaliyopatikana'
                    : 'No adjustments found',
              );
            }

            return ListView.builder(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: items.length + 1,
              itemBuilder: (context, index) {
                if (index == items.length) return const SizedBox(height: 90);
                final item = items[index];
                  return Card(
                    margin: const EdgeInsets.only(bottom: 12),
                    child: ListTile(
                      onTap: () =>
                          _showAdjustmentSheet(context, ref, item['id'] as int?),
                      contentPadding: const EdgeInsets.all(16),
                    leading: CircleAvatar(
                      backgroundColor: AppColors.warning.withValues(alpha: 0.12),
                      child: const Icon(
                        Icons.tune_rounded,
                        color: AppColors.warning,
                      ),
                    ),
                    title: Text(
                      item['staff_name'] as String? ?? '-',
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        color:
                            isDarkMode ? Colors.white : AppColors.textPrimary,
                      ),
                    ),
                    subtitle: Padding(
                      padding: const EdgeInsets.only(top: 6),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            '${isSwahili ? 'Aina' : 'Type'}: ${item['adjustment_type'] ?? '-'}',
                          ),
                          Text(
                            '${isSwahili ? 'Maelezo' : 'Description'}: ${item['description'] ?? '-'}',
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),
                          Text(
                            _formatDate(item['created_at'] as String?),
                          ),
                        ],
                      ),
                    ),
                    trailing: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          'TZS ${_formatMoney(item['amount'])}',
                          style: const TextStyle(
                            fontWeight: FontWeight.w700,
                            color: AppColors.warning,
                          ),
                        ),
                        const SizedBox(height: 8),
                        const Icon(
                          Icons.arrow_forward_ios_rounded,
                          size: 14,
                          color: AppColors.textHint,
                        ),
                      ],
                    ),
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }
}

void _showAdjustmentSheet(BuildContext context, WidgetRef ref, int? id) {
  if (id == null) return;

  showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    showDragHandle: true,
    builder: (context) => Consumer(
      builder: (context, ref, _) {
        final adjustmentAsync = ref.watch(_adjustmentProvider(id));
        return SafeArea(
          child: SizedBox(
            height: MediaQuery.of(context).size.height * 0.6,
            child: adjustmentAsync.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (error, _) => _AdjustmentErrorView(
                error: error,
                isSwahili: false,
                onRetry: () => ref.invalidate(_adjustmentProvider(id)),
              ),
              data: (detail) => ListView(
                padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
                children: [
                  Text(
                    detail['staff_name'] as String? ?? 'Adjustment',
                    style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 18),
                  _AdjustmentDetailRow(
                    'Type',
                    detail['adjustment_type'] as String? ?? 'N/A',
                  ),
                  _AdjustmentDetailRow(
                    'Amount',
                    'TZS ${_formatMoney(detail['amount'])}',
                  ),
                  _AdjustmentDetailRow(
                    'Description',
                    detail['description'] as String? ?? 'N/A',
                  ),
                  _AdjustmentDetailRow(
                    'Payroll ID',
                    detail['payroll_id']?.toString() ?? 'N/A',
                  ),
                  _AdjustmentDetailRow(
                    'Created',
                    _formatDate(detail['created_at'] as String?),
                  ),
                ],
              ),
            ),
          ),
        );
      },
    ),
  );
}

class _AdjustmentDetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _AdjustmentDetailRow(this.label, this.value);

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
          Expanded(
            child: Text(
              value.isEmpty ? 'N/A' : value,
              style: const TextStyle(fontSize: 14),
            ),
          ),
        ],
      ),
    );
  }
}

class _AdjustmentErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _AdjustmentErrorView({
    required this.error,
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

class _AdjustmentEmptyView extends StatelessWidget {
  final String label;

  const _AdjustmentEmptyView({required this.label});

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(32),
      children: [
        const SizedBox(height: 100),
        Icon(Icons.tune_rounded, size: 56, color: Colors.grey[300]),
        const SizedBox(height: 12),
        Text(
          label,
          textAlign: TextAlign.center,
          style: const TextStyle(color: AppColors.textSecondary),
        ),
      ],
    );
  }
}

String _formatMoney(dynamic value) {
  final amount = value is num ? value.toDouble() : double.tryParse('$value') ?? 0;
  return NumberFormat('#,##0.00', 'en_US').format(amount);
}

String _formatDate(String? raw) {
  if (raw == null || raw.isEmpty) return '-';
  try {
    return DateFormat('dd MMM yyyy').format(DateTime.parse(raw));
  } catch (_) {
    return raw;
  }
}
