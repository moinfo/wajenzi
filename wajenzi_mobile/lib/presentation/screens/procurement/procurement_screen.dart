import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _materialRequestsProvider =
    FutureProvider.autoDispose<List<dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/material-requests');
  return response.data['data'] as List? ?? [];
});

class ProcurementScreen extends ConsumerWidget {
  const ProcurementScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final requestsAsync = ref.watch(_materialRequestsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Ununuzi' : 'Procurement'),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_materialRequestsProvider.future),
        child: requestsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => _ErrorView(
            error: e,
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_materialRequestsProvider),
          ),
          data: (requests) {
            if (requests.isEmpty) {
              return ListView(
                children: [
                  const SizedBox(height: 120),
                  Icon(Icons.inventory_2_outlined,
                      size: 56, color: Colors.grey[300]),
                  const SizedBox(height: 12),
                  Center(
                    child: Text(
                      isSwahili
                          ? 'Hakuna maombi ya vifaa'
                          : 'No material requests',
                      style: const TextStyle(color: AppColors.textSecondary),
                    ),
                  ),
                ],
              );
            }
            return ListView.builder(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: requests.length + 1,
              itemBuilder: (context, index) {
                if (index == requests.length) return const SizedBox(height: 80);
                final req = requests[index] as Map<String, dynamic>;
                return _RequestCard(request: req, isDarkMode: isDarkMode);
              },
            );
          },
        ),
      ),
    );
  }
}

class _RequestCard extends StatelessWidget {
  final Map<String, dynamic> request;
  final bool isDarkMode;

  const _RequestCard({required this.request, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    final requestNumber = request['request_number'] as String? ?? '';
    final projectName = request['project_name'] as String? ??
        request['project']?['project_name'] as String? ??
        '';
    final status = request['status'] as String? ?? '';
    final createdAt = request['created_at'] as String?;
    final itemCount = request['items_count'] ?? request['items']?.length ?? 0;

    String? dateStr;
    if (createdAt != null) {
      try {
        dateStr = DateFormat('dd MMM yyyy').format(DateTime.parse(createdAt));
      } catch (_) {}
    }

    Color statusColor;
    switch (status.toLowerCase()) {
      case 'approved':
        statusColor = const Color(0xFF27AE60);
        break;
      case 'pending':
      case 'submitted':
        statusColor = const Color(0xFFF59E0B);
        break;
      case 'rejected':
        statusColor = const Color(0xFFEF4444);
        break;
      default:
        statusColor = const Color(0xFF95A5A6);
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
              color: const Color(0xFF7C3AED).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(Icons.inventory_2_rounded,
                color: Color(0xFF7C3AED), size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  requestNumber.isNotEmpty ? requestNumber : 'Material Request',
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 2),
                if (projectName.isNotEmpty)
                  Text(
                    projectName,
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
                    if (itemCount > 0) ...[
                      Icon(Icons.list_rounded,
                          size: 12,
                          color: isDarkMode
                              ? Colors.white38
                              : AppColors.textHint),
                      const SizedBox(width: 4),
                      Text(
                        '$itemCount items',
                        style: TextStyle(
                          fontSize: 11,
                          color: isDarkMode
                              ? Colors.white38
                              : AppColors.textHint,
                        ),
                      ),
                    ],
                    if (dateStr != null) ...[
                      if (itemCount > 0) const SizedBox(width: 10),
                      Icon(Icons.calendar_today_rounded,
                          size: 11,
                          color: isDarkMode
                              ? Colors.white38
                              : AppColors.textHint),
                      const SizedBox(width: 4),
                      Text(
                        dateStr,
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
              status,
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
