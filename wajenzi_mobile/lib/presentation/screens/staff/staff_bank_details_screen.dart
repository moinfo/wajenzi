import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _staffBankDetailsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/staff-bank-details');
  return (response.data['data'] as List).cast<Map<String, dynamic>>();
});

final _staffBankDetailProvider =
    FutureProvider.autoDispose.family<Map<String, dynamic>, int>((ref, id) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/staff-bank-details/$id');
  return response.data['data'] as Map<String, dynamic>? ?? const {};
});

class StaffBankDetailsScreen extends ConsumerWidget {
  const StaffBankDetailsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final detailsAsync = ref.watch(_staffBankDetailsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Akaunti za Benki' : 'Staff Bank Details'),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_staffBankDetailsProvider.future),
        child: detailsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _SimpleErrorView(
            error: error,
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_staffBankDetailsProvider),
          ),
          data: (items) {
            if (items.isEmpty) {
              return _EmptyListView(
                icon: Icons.account_balance_outlined,
                label: isSwahili
                    ? 'Hakuna taarifa za benki'
                    : 'No bank details found',
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
                          _showBankDetailSheet(context, ref, item['id'] as int?),
                      contentPadding: const EdgeInsets.all(16),
                    leading: CircleAvatar(
                      backgroundColor: AppColors.primary.withValues(alpha: 0.1),
                      child: const Icon(
                        Icons.account_balance_rounded,
                        color: AppColors.primary,
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
                          Text(item['bank_name'] as String? ?? '-'),
                          Text(
                            '${isSwahili ? 'Akaunti' : 'Account'}: ${item['account_number'] ?? '-'}',
                          ),
                          if ((item['branch'] as String?)?.isNotEmpty == true)
                            Text(
                              '${isSwahili ? 'Tawi' : 'Branch'}: ${item['branch']}',
                            ),
                        ],
                      ),
                    ),
                    trailing: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          _formatDate(item['created_at'] as String?),
                          style: const TextStyle(
                            fontSize: 11,
                            color: AppColors.textSecondary,
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

void _showBankDetailSheet(BuildContext context, WidgetRef ref, int? id) {
  if (id == null) return;

  showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    showDragHandle: true,
    builder: (context) => Consumer(
      builder: (context, ref, _) {
        final detailAsync = ref.watch(_staffBankDetailProvider(id));
        return SafeArea(
          child: SizedBox(
            height: MediaQuery.of(context).size.height * 0.55,
            child: detailAsync.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (error, _) => _SimpleErrorView(
                error: error,
                isSwahili: false,
                onRetry: () => ref.invalidate(_staffBankDetailProvider(id)),
              ),
              data: (detail) => ListView(
                padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
                children: [
                  Text(
                    detail['staff_name'] as String? ?? 'Bank Detail',
                    style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 18),
                  _BankDetailRow('Bank', detail['bank_name'] as String? ?? 'N/A'),
                  _BankDetailRow(
                    'Account Number',
                    detail['account_number'] as String? ?? 'N/A',
                  ),
                  _BankDetailRow('Branch', detail['branch'] as String? ?? 'N/A'),
                  _BankDetailRow(
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

class _BankDetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _BankDetailRow(this.label, this.value);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
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

class _SimpleErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _SimpleErrorView({
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

class _EmptyListView extends StatelessWidget {
  final IconData icon;
  final String label;

  const _EmptyListView({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(32),
      children: [
        const SizedBox(height: 100),
        Icon(icon, size: 56, color: Colors.grey[300]),
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

String _formatDate(String? raw) {
  if (raw == null || raw.isEmpty) return '-';
  try {
    return DateFormat('dd MMM yyyy').format(DateTime.parse(raw));
  } catch (_) {
    return raw;
  }
}
