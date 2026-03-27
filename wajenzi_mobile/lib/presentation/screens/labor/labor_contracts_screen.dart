import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final laborContractsProjectFilterProvider = StateProvider.autoDispose<int?>(
  (ref) => null,
);

final laborContractsStatusFilterProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);

final _laborContractsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final projectId = ref.watch(laborContractsProjectFilterProvider);
      final status = ref.watch(laborContractsStatusFilterProvider);

      final response = await api.get(
        '/labor/contracts',
        queryParameters: {
          if (projectId != null) 'project_id': projectId,
          if (status != null) 'status': status,
        },
      );
      return response.data['data'] as Map<String, dynamic>? ?? const {};
    });

final _laborContractsReferenceProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/labor/contracts/reference-data');
      return response.data['data'] as Map<String, dynamic>? ?? const {};
    });

class LaborContractsScreen extends ConsumerWidget {
  const LaborContractsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final contractsAsync = ref.watch(_laborContractsProvider);
    final selectedProject = ref.watch(laborContractsProjectFilterProvider);
    final selectedStatus = ref.watch(laborContractsStatusFilterProvider);
    final referenceDataAsync = ref.watch(_laborContractsReferenceProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Mikataba ya Labor' : 'Labor Contracts'),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(_laborContractsProvider);
          ref.invalidate(_laborContractsReferenceProvider);
        },
        child: contractsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _ContractErrorView(
            error: error,
            isSwahili: isSwahili,
            onRetry: () {
              ref.invalidate(_laborContractsProvider);
              ref.invalidate(_laborContractsReferenceProvider);
            },
          ),
          data: (payload) {
            final contracts = (payload['data'] as List? ?? const [])
                .cast<dynamic>();
            final meta = payload['meta'] as Map<String, dynamic>? ?? const {};
            final projects =
                referenceDataAsync.valueOrNull?['projects'] as List? ??
                const [];
            final statuses =
                referenceDataAsync.valueOrNull?['statuses'] as List? ??
                const [];

            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                _SectionCard(
                  isDarkMode: isDarkMode,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        isSwahili ? 'Mradi' : 'Project',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      DropdownButtonFormField<int?>(
                        value: selectedProject,
                        isExpanded: true,
                        decoration: InputDecoration(
                          filled: true,
                          fillColor: isDarkMode
                              ? const Color(0xFF252540)
                              : Colors.grey[100],
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide.none,
                          ),
                        ),
                        items: [
                          DropdownMenuItem(
                            value: null,
                            child: Text(
                              isSwahili ? 'Miradi Yote' : 'All Projects',
                            ),
                          ),
                          ...projects.map(
                            (project) => DropdownMenuItem<int?>(
                              value: project['id'] as int?,
                              child: Text(
                                project['project_name'] as String? ?? '-',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ),
                        ],
                        onChanged: (value) {
                          ref
                                  .read(
                                    laborContractsProjectFilterProvider
                                        .notifier,
                                  )
                                  .state =
                              value;
                        },
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                _SectionCard(
                  isDarkMode: isDarkMode,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        isSwahili ? 'Hali' : 'Status',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 8),
                      DropdownButtonFormField<String?>(
                        value: selectedStatus,
                        isExpanded: true,
                        decoration: InputDecoration(
                          filled: true,
                          fillColor: isDarkMode
                              ? const Color(0xFF252540)
                              : Colors.grey[100],
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: BorderSide.none,
                          ),
                        ),
                        items: [
                          DropdownMenuItem(
                            value: null,
                            child: Text(
                              isSwahili ? 'Hali Zote' : 'All Statuses',
                            ),
                          ),
                          ...statuses.map(
                            (s) => DropdownMenuItem<String?>(
                              value: s['value'] as String?,
                              child: Text(s['label'] as String? ?? '-'),
                            ),
                          ),
                        ],
                        onChanged: (value) {
                          ref
                                  .read(
                                    laborContractsStatusFilterProvider.notifier,
                                  )
                                  .state =
                              value;
                        },
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                if (contracts.isEmpty)
                  _SectionCard(
                    isDarkMode: isDarkMode,
                    child: Center(
                      child: Padding(
                        padding: const EdgeInsets.all(32),
                        child: Column(
                          children: [
                            Icon(
                              Icons.description_outlined,
                              size: 64,
                              color: isDarkMode ? Colors.white30 : Colors.grey,
                            ),
                            const SizedBox(height: 16),
                            Text(
                              isSwahili
                                  ? 'Hakuna mikataba iliyopatikana'
                                  : 'No contracts found',
                              style: TextStyle(
                                fontSize: 16,
                                color: isDarkMode
                                    ? Colors.white54
                                    : Colors.grey,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  )
                else
                  ...contracts.map(
                    (item) => Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: _ContractCard(
                        item: Map<String, dynamic>.from(item as Map),
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                      ),
                    ),
                  ),
                const SizedBox(height: 90),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  final bool isDarkMode;
  final Widget child;

  const _SectionCard({required this.isDarkMode, required this.child});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
          ),
        ],
      ),
      child: child,
    );
  }
}

class _ContractCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final bool isSwahili;
  final bool isDarkMode;

  const _ContractCard({
    required this.item,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      item['contract_number'] as String? ?? '-',
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 16,
                        color: isDarkMode
                            ? Colors.white
                            : AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 4),
                    if (item['project'] != null)
                      Text(
                        (item['project'] as Map)['project_name'] as String? ??
                            '-',
                        style: TextStyle(
                          fontSize: 13,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: _badgeColor(
                    item['status_badge_class'] as String?,
                  ).withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  (item['status'] as String? ?? '-').toUpperCase().replaceAll(
                    '_',
                    ' ',
                  ),
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: _badgeColor(item['status_badge_class'] as String?),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (item['artisan'] != null) ...[
            Row(
              children: [
                Icon(
                  Icons.person_outline,
                  size: 16,
                  color: isDarkMode ? Colors.white54 : AppColors.textHint,
                ),
                const SizedBox(width: 4),
                Text(
                  (item['artisan'] as Map)['name'] as String? ?? '-',
                  style: TextStyle(
                    fontSize: 12,
                    color: isDarkMode ? Colors.white54 : AppColors.textHint,
                  ),
                ),
                if ((item['artisan'] as Map)['trade_skill'] != null) ...[
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 6,
                      vertical: 2,
                    ),
                    decoration: BoxDecoration(
                      color: const Color(0xFF0891B2).withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: Text(
                      (item['artisan'] as Map)['trade_skill'] as String? ?? '',
                      style: const TextStyle(
                        fontSize: 10,
                        color: Color(0xFF0891B2),
                      ),
                    ),
                  ),
                ],
              ],
            ),
            const SizedBox(height: 8),
          ],
          if (item['scope_of_work'] != null) ...[
            Text(
              item['scope_of_work'] as String? ?? '-',
              style: TextStyle(
                fontSize: 13,
                color: isDarkMode ? Colors.white70 : AppColors.textSecondary,
              ),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 8),
          ],
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: _InfoChip(
                  label: isSwahili ? 'Jumla' : 'Total',
                  value: _formatCurrency(_toDouble(item['total_amount'])),
                  isDarkMode: isDarkMode,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _InfoChip(
                  label: isSwahili ? 'Imelipwa' : 'Paid',
                  value: _formatCurrency(_toDouble(item['amount_paid'])),
                  isDarkMode: isDarkMode,
                  highlight: true,
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: _InfoChip(
                  label: isSwahili ? 'Bakia' : 'Balance',
                  value: _formatCurrency(_toDouble(item['balance_amount'])),
                  isDarkMode: isDarkMode,
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          if ((item['payment_progress'] as num?) != null) ...[
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      isSwahili ? 'Maendeleo ya malipo' : 'Payment Progress',
                      style: TextStyle(
                        fontSize: 11,
                        color: isDarkMode ? Colors.white54 : AppColors.textHint,
                      ),
                    ),
                    Text(
                      '${(item['payment_progress'] as num).toStringAsFixed(1)}%',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                        color: isDarkMode
                            ? Colors.white70
                            : AppColors.textSecondary,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                ClipRRect(
                  borderRadius: BorderRadius.circular(4),
                  child: LinearProgressIndicator(
                    value: (_toDouble(item['payment_progress']) / 100).clamp(
                      0.0,
                      1.0,
                    ),
                    backgroundColor: isDarkMode
                        ? const Color(0xFF252540)
                        : Colors.grey[200],
                    valueColor: AlwaysStoppedAnimation<Color>(
                      _badgeColor(item['status_badge_class'] as String?),
                    ),
                    minHeight: 6,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
          ],
          const SizedBox(height: 8),
          Row(
            children: [
              if (item['start_date'] != null) ...[
                Icon(
                  Icons.calendar_today_outlined,
                  size: 14,
                  color: isDarkMode ? Colors.white54 : AppColors.textHint,
                ),
                const SizedBox(width: 4),
                Text(
                  '${item['start_date']} - ${item['end_date'] ?? '-'}',
                  style: TextStyle(
                    fontSize: 11,
                    color: isDarkMode ? Colors.white54 : AppColors.textHint,
                  ),
                ),
              ],
              const Spacer(),
              if (item['days_remaining'] != null &&
                  (item['days_remaining'] as num) > 0) ...[
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0xFF16A34A).withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Text(
                    '${item['days_remaining']} ${isSwahili ? 'days kubaki' : 'days left'}',
                    style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: Color(0xFF16A34A),
                    ),
                  ),
                ),
              ],
              if (item['days_overdue'] != null &&
                  (item['days_overdue'] as num) > 0) ...[
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 4,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0xFFDC2626).withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Text(
                    '${item['days_overdue']} ${isSwahili ? 'days kuchelewa' : 'days overdue'}',
                    style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: Color(0xFFDC2626),
                    ),
                  ),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }
}

class _InfoChip extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;
  final bool highlight;

  const _InfoChip({
    required this.label,
    required this.value,
    required this.isDarkMode,
    this.highlight = false,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: highlight
            ? const Color(0xFF16A34A).withValues(alpha: 0.12)
            : (isDarkMode ? const Color(0xFF252540) : Colors.grey[100]),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 10,
              color: highlight
                  ? const Color(0xFF16A34A)
                  : (isDarkMode ? Colors.white54 : AppColors.textHint),
            ),
          ),
          Text(
            value,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: highlight
                  ? const Color(0xFF16A34A)
                  : (isDarkMode ? Colors.white : AppColors.textPrimary),
            ),
          ),
        ],
      ),
    );
  }
}

class _ContractErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ContractErrorView({
    required this.error,
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

double _toDouble(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse('$value') ?? 0;
}

String _formatCurrency(double amount) {
  return NumberFormat('#,##0.00', 'en_US').format(amount);
}

Color _badgeColor(String? badgeClass) {
  return switch (badgeClass) {
    'success' => const Color(0xFF16A34A),
    'warning' => const Color(0xFFF59E0B),
    'danger' => const Color(0xFFDC2626),
    'info' => const Color(0xFF0891B2),
    _ => const Color(0xFF6B7280),
  };
}
