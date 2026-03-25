import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _approvalsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/approvals');
  return {
    'items': response.data['data'] as List? ?? const [],
    'meta': response.data['meta'] as Map<String, dynamic>? ?? const {},
  };
});

class ApprovalsScreen extends ConsumerWidget {
  const ApprovalsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final approvalsAsync = ref.watch(_approvalsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Idhini' : 'Approvals'),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_approvalsProvider.future),
        child: approvalsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _ApprovalErrorView(
            error: error,
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_approvalsProvider),
          ),
          data: (payload) {
            final items =
                (payload['items'] as List).cast<Map<String, dynamic>>();
            final meta =
                payload['meta'] as Map<String, dynamic>? ?? const {};

            if (items.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(
                    Icons.check_circle_outline,
                    size: 64,
                    color: Colors.grey[300],
                  ),
                  const SizedBox(height: 16),
                  Text(
                    isSwahili
                        ? 'Hakuna idhini zinazosubiri'
                        : 'No pending approvals',
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: AppColors.textSecondary),
                  ),
                ],
              );
            }

            return ListView.builder(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: items.length + 2,
              itemBuilder: (context, index) {
                if (index == 0) {
                  final total = meta['total'] ?? items.length;
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: Text(
                      isSwahili
                          ? 'Jumla zinazongoja: $total'
                          : 'Total pending: $total',
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: AppColors.textSecondary,
                      ),
                    ),
                  );
                }

                if (index == items.length + 1) return const SizedBox(height: 90);

                final item = items[index - 1];
                return _ApprovalCard(item: item, isSwahili: isSwahili);
              },
            );
          },
        ),
      ),
    );
  }
}

class _ApprovalCard extends ConsumerWidget {
  final Map<String, dynamic> item;
  final bool isSwahili;

  const _ApprovalCard({
    required this.item,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final type = item['type'] as String? ?? 'unknown';
    final title = item['title'] as String? ?? '-';
    final description = item['description'] as String? ?? '';
    final submittedBy = item['submitted_by'] as String? ?? '-';
    final submittedAt = item['submitted_at'] as String?;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: _typeColor(type).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Icon(_typeIcon(type), color: _typeColor(type), size: 24),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        title,
                        style: Theme.of(context).textTheme.titleSmall?.copyWith(
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                      if (description.isNotEmpty)
                        Text(
                          description,
                          style: Theme.of(context).textTheme.bodySmall?.copyWith(
                                color: AppColors.textSecondary,
                              ),
                        ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                const Icon(
                  Icons.person_outline,
                  size: 16,
                  color: AppColors.textHint,
                ),
                const SizedBox(width: 4),
                Expanded(
                  child: Text(
                    submittedBy,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: AppColors.textSecondary,
                        ),
                  ),
                ),
                Text(
                  _formatDate(submittedAt),
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: AppColors.textSecondary,
                      ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    onPressed: () => _handleApprovalAction(
                      context,
                      ref,
                      type: type,
                      id: item['id'] as int,
                      action: 'reject',
                      isSwahili: isSwahili,
                    ),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppColors.error,
                      side: const BorderSide(color: AppColors.error),
                    ),
                    child: Text(isSwahili ? 'Kataa' : 'Reject'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    onPressed: () => _handleApprovalAction(
                      context,
                      ref,
                      type: type,
                      id: item['id'] as int,
                      action: 'approve',
                      isSwahili: isSwahili,
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.success,
                      foregroundColor: Colors.white,
                    ),
                    child: Text(isSwahili ? 'Idhinisha' : 'Approve'),
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

Future<void> _handleApprovalAction(
  BuildContext context,
  WidgetRef ref, {
  required String type,
  required int id,
  required String action,
  required bool isSwahili,
}) async {
  final api = ref.read(apiClientProvider);
  final messenger = ScaffoldMessenger.of(context);

  try {
    if (action == 'reject') {
      await api.post('/approvals/$type/$id/reject', data: {
        'reason': isSwahili ? 'Imefutwa kupitia app' : 'Rejected from mobile app',
      });
    } else {
      await api.post('/approvals/$type/$id/approve');
    }

    ref.invalidate(_approvalsProvider);
    messenger.showSnackBar(
      SnackBar(
        content: Text(
          action == 'approve'
              ? (isSwahili ? 'Imeidhinishwa' : 'Approved successfully')
              : (isSwahili ? 'Imekataliwa' : 'Rejected successfully'),
        ),
      ),
    );
  } catch (error) {
    messenger.showSnackBar(
      SnackBar(
        content: Text('$error'),
        backgroundColor: AppColors.error,
      ),
    );
  }
}

class _ApprovalErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ApprovalErrorView({
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

IconData _typeIcon(String type) {
  switch (type) {
    case 'site_daily_report':
      return Icons.description_rounded;
    case 'expense':
      return Icons.receipt_long_rounded;
    case 'material_request':
      return Icons.inventory_2_rounded;
    case 'site_visit':
      return Icons.fact_check_rounded;
    default:
      return Icons.article_rounded;
  }
}

Color _typeColor(String type) {
  switch (type) {
    case 'site_daily_report':
      return AppColors.info;
    case 'expense':
      return AppColors.secondary;
    case 'material_request':
      return AppColors.primary;
    case 'site_visit':
      return AppColors.warning;
    default:
      return AppColors.textSecondary;
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
