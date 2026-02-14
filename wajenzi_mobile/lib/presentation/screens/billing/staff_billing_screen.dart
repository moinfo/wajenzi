import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _billingProvider = FutureProvider.autoDispose<List<dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/billing/documents');
  return response.data['data'] as List? ?? [];
});

class StaffBillingScreen extends ConsumerWidget {
  const StaffBillingScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final billingAsync = ref.watch(_billingProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Ankara' : 'Billing'),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_billingProvider.future),
        child: billingAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => _ErrorView(
            error: e,
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_billingProvider),
          ),
          data: (docs) {
            if (docs.isEmpty) {
              return ListView(
                children: [
                  const SizedBox(height: 120),
                  Icon(Icons.receipt_long_outlined,
                      size: 56, color: Colors.grey[300]),
                  const SizedBox(height: 12),
                  Center(
                    child: Text(
                      isSwahili
                          ? 'Hakuna ankara kwa sasa'
                          : 'No billing documents',
                      style: const TextStyle(color: AppColors.textSecondary),
                    ),
                  ),
                ],
              );
            }
            return ListView.builder(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: docs.length + 1,
              itemBuilder: (context, index) {
                if (index == docs.length) return const SizedBox(height: 80);
                final doc = docs[index] as Map<String, dynamic>;
                return _BillingCard(doc: doc, isDarkMode: isDarkMode);
              },
            );
          },
        ),
      ),
    );
  }
}

class _BillingCard extends StatelessWidget {
  final Map<String, dynamic> doc;
  final bool isDarkMode;

  const _BillingCard({required this.doc, required this.isDarkMode});

  String _formatAmount(dynamic amount) {
    if (amount == null) return 'TZS 0';
    final val = amount is num ? amount.toDouble() : double.tryParse('$amount') ?? 0;
    final formatter = NumberFormat('#,##0', 'en');
    return 'TZS ${formatter.format(val)}';
  }

  @override
  Widget build(BuildContext context) {
    final docNumber = doc['document_number'] as String? ?? '';
    final clientName = doc['client_name'] as String? ?? doc['client']?['name'] as String? ?? '';
    final status = doc['status'] as String? ?? '';
    final totalAmount = doc['total_amount'];
    final dueDate = doc['due_date'] as String?;

    Color statusColor;
    switch (status) {
      case 'paid':
        statusColor = const Color(0xFF27AE60);
        break;
      case 'partial_paid':
        statusColor = const Color(0xFFF59E0B);
        break;
      case 'overdue':
        statusColor = const Color(0xFFEF4444);
        break;
      default:
        statusColor = const Color(0xFF3B82F6);
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1E1E30) : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isDarkMode
              ? Colors.white.withValues(alpha: 0.08)
              : Colors.grey.withValues(alpha: 0.12),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: isDarkMode ? 0.2 : 0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: BoxDecoration(
              color: statusColor.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child:
                Icon(Icons.receipt_long_rounded, color: statusColor, size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  docNumber,
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  clientName,
                  style: TextStyle(
                    fontSize: 12,
                    color: isDarkMode
                        ? Colors.white54
                        : AppColors.textSecondary,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 4),
                Row(
                  children: [
                    Icon(Icons.monetization_on_rounded,
                        size: 12, color: const Color(0xFFE67E22)),
                    const SizedBox(width: 4),
                    Text(
                      _formatAmount(totalAmount),
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFFE67E22),
                      ),
                    ),
                    if (dueDate != null) ...[
                      const SizedBox(width: 8),
                      Text(
                        dueDate,
                        style: TextStyle(
                          fontSize: 11,
                          color: isDarkMode
                              ? Colors.white38
                              : AppColors.textHint,
                        ),
                      ),
                    ],
                  ],
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: statusColor.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              status.replaceAll('_', ' '),
              style: TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.w600,
                color: statusColor,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ErrorView({
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
