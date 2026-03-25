import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';

final _leaveRequestStatusProvider =
    StateProvider.autoDispose<String?>((ref) => null);

final _leaveRequestsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final status = ref.watch(_leaveRequestStatusProvider);

  final responses = await Future.wait([
    api.get(
      '/leave-requests',
      queryParameters: {
        if (status != null && status.isNotEmpty) 'status': status,
      },
    ),
    api.get('/leave-requests/balance'),
  ]);

  return {
    'items': (responses[0].data['data'] as List? ?? const [])
        .cast<Map<String, dynamic>>(),
    'meta': responses[0].data['meta'] as Map<String, dynamic>? ?? const {},
    'balance': responses[1].data['data'] as Map<String, dynamic>? ?? const {},
  };
});

final _leaveRequestDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/leave-requests/$id');
  return response.data['data'] as Map<String, dynamic>? ?? const {};
});

class LeaveRequestsScreen extends ConsumerWidget {
  const LeaveRequestsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final leaveAsync = ref.watch(_leaveRequestsProvider);
    final selectedStatus = ref.watch(_leaveRequestStatusProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Maombi ya Likizo' : 'Leave Requests'),
      ),
      body: Column(
        children: [
          _LeaveFilterBar(
            isSwahili: isSwahili,
            isDarkMode: isDarkMode,
            selectedStatus: selectedStatus,
            onChanged: (value) =>
                ref.read(_leaveRequestStatusProvider.notifier).state = value,
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.refresh(_leaveRequestsProvider.future),
              child: leaveAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => _LeaveErrorView(
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_leaveRequestsProvider),
                ),
                data: (payload) {
                  final items =
                      (payload['items'] as List).cast<Map<String, dynamic>>();
                  final meta =
                      payload['meta'] as Map<String, dynamic>? ?? const {};
                  final balance =
                      payload['balance'] as Map<String, dynamic>? ?? const {};

                  return ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    children: [
                      _LeaveBalanceCard(
                        balance: balance,
                        isSwahili: isSwahili,
                      ),
                      const SizedBox(height: 16),
                      Text(
                        isSwahili
                            ? 'Maombi yaliyopo: ${meta['total'] ?? items.length}'
                            : 'Requests available: ${meta['total'] ?? items.length}',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: isDarkMode
                              ? Colors.white70
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 12),
                      if (items.isEmpty)
                        _LeaveEmptyView(
                          label: isSwahili
                              ? 'Hakuna maombi ya likizo'
                              : 'No leave requests found',
                        )
                      else
                        ...items.map(
                          (item) => _LeaveRequestCard(
                            item: item,
                            isSwahili: isSwahili,
                            onTap: () => _showLeaveRequestSheet(
                              context,
                              ref,
                              _toInt(item['id']),
                              isSwahili,
                            ),
                          ),
                        ),
                      const SizedBox(height: 90),
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

class _LeaveFilterBar extends StatelessWidget {
  final bool isSwahili;
  final bool isDarkMode;
  final String? selectedStatus;
  final ValueChanged<String?> onChanged;

  const _LeaveFilterBar({
    required this.isSwahili,
    required this.isDarkMode,
    required this.selectedStatus,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    final options = <String?, String>{
      null: isSwahili ? 'Zote' : 'All',
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

class _LeaveBalanceCard extends StatelessWidget {
  final Map<String, dynamic> balance;
  final bool isSwahili;

  const _LeaveBalanceCard({
    required this.balance,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    final balances =
        (balance['balances'] as List? ?? const []).cast<Map<String, dynamic>>();

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.info.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.info.withValues(alpha: 0.15)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            isSwahili ? 'Mizania ya Likizo' : 'Leave Balance',
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 12),
          if (balances.isEmpty)
            Text(
              isSwahili ? 'Hakuna mizania ya likizo' : 'No leave balances',
              style: const TextStyle(color: AppColors.textSecondary),
            )
          else
            ...balances.map(
              (item) => Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Row(
                  children: [
                    Expanded(
                      child: Text(
                        item['name'] as String? ?? '-',
                        style: const TextStyle(fontWeight: FontWeight.w600),
                      ),
                    ),
                    Text(
                      '${item['days_used'] ?? 0}/${item['days_allowed'] ?? 0} ${isSwahili ? 'zimetumika' : 'used'}',
                      style: const TextStyle(color: AppColors.textSecondary),
                    ),
                    const SizedBox(width: 12),
                    Text(
                      '${item['days_remaining'] ?? 0} ${isSwahili ? 'zimebaki' : 'left'}',
                      style: const TextStyle(
                        color: AppColors.info,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _LeaveRequestCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final bool isSwahili;
  final VoidCallback onTap;

  const _LeaveRequestCard({
    required this.item,
    required this.isSwahili,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final leaveType =
        (item['leave_type'] as Map<String, dynamic>?)?['name'] as String? ?? '-';
    final status = (item['status'] as String? ?? 'pending').toLowerCase();

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        onTap: onTap,
        contentPadding: const EdgeInsets.all(16),
        leading: CircleAvatar(
          backgroundColor: _leaveStatusColor(status).withValues(alpha: 0.12),
          child: Icon(
            Icons.event_note_rounded,
            color: _leaveStatusColor(status),
          ),
        ),
        title: Text(
          leaveType,
          style: const TextStyle(fontWeight: FontWeight.w700),
        ),
        subtitle: Padding(
          padding: const EdgeInsets.only(top: 6),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                '${_formatDate(item['start_date'] as String?)} - ${_formatDate(item['end_date'] as String?)}',
              ),
              Text(
                '${item['total_days'] ?? 0} ${isSwahili ? 'siku' : 'days'}',
              ),
            ],
          ),
        ),
        trailing: _LeaveStatusBadge(status: status, isSwahili: isSwahili),
      ),
    );
  }
}

void _showLeaveRequestSheet(
  BuildContext context,
  WidgetRef ref,
  int id,
  bool isSwahili,
) {
  if (id <= 0) return;

  showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    showDragHandle: true,
    builder: (context) => Consumer(
      builder: (context, ref, _) {
        final detailAsync = ref.watch(_leaveRequestDetailProvider(id));
        return SafeArea(
          child: SizedBox(
            height: MediaQuery.of(context).size.height * 0.65,
            child: detailAsync.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (error, _) => _LeaveErrorView(
                isSwahili: isSwahili,
                onRetry: () => ref.invalidate(_leaveRequestDetailProvider(id)),
              ),
              data: (detail) {
                final leaveType =
                    (detail['leave_type'] as Map<String, dynamic>?)?['name']
                            as String? ??
                        'Leave Request';
                final status =
                    (detail['status'] as String? ?? 'pending').toLowerCase();

                return ListView(
                  padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
                  children: [
                    Text(
                      leaveType,
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 12),
                    _LeaveStatusBadge(status: status, isSwahili: isSwahili),
                    const SizedBox(height: 18),
                    _LeaveDetailRow(
                      isSwahili ? 'Tarehe ya Kuanza' : 'Start Date',
                      _formatDate(detail['start_date'] as String?),
                    ),
                    _LeaveDetailRow(
                      isSwahili ? 'Tarehe ya Mwisho' : 'End Date',
                      _formatDate(detail['end_date'] as String?),
                    ),
                    _LeaveDetailRow(
                      isSwahili ? 'Jumla ya Siku' : 'Total Days',
                      '${detail['total_days'] ?? 0}',
                    ),
                    _LeaveDetailRow(
                      isSwahili ? 'Sababu' : 'Reason',
                      detail['reason'] as String? ?? '-',
                    ),
                    if ((detail['rejected_reason'] as String? ?? '').isNotEmpty)
                      _LeaveDetailRow(
                        isSwahili ? 'Sababu ya Kukataliwa' : 'Rejected Reason',
                        detail['rejected_reason'] as String,
                      ),
                    if ((detail['approver'] as Map<String, dynamic>?) != null)
                      _LeaveDetailRow(
                        isSwahili ? 'Aliyeidhinisha' : 'Approver',
                        (detail['approver'] as Map<String, dynamic>)['name']
                                as String? ??
                            '-',
                      ),
                    _LeaveDetailRow(
                      isSwahili ? 'Iliundwa' : 'Created',
                      _formatDateTime(detail['created_at'] as String?),
                    ),
                  ],
                );
              },
            ),
          ),
        );
      },
    ),
  );
}

class _LeaveStatusBadge extends StatelessWidget {
  final String status;
  final bool isSwahili;

  const _LeaveStatusBadge({
    required this.status,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    final color = _leaveStatusColor(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        _leaveStatusLabel(status, isSwahili),
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: color,
        ),
      ),
    );
  }
}

class _LeaveDetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _LeaveDetailRow(this.label, this.value);

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
              value.isEmpty ? '-' : value,
              style: const TextStyle(fontSize: 14),
            ),
          ),
        ],
      ),
    );
  }
}

class _LeaveEmptyView extends StatelessWidget {
  final String label;

  const _LeaveEmptyView({
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 48, horizontal: 24),
      child: Column(
        children: [
          Icon(
            Icons.event_busy_outlined,
            size: 56,
            color: Colors.grey[300],
          ),
          const SizedBox(height: 12),
          Text(
            label,
            textAlign: TextAlign.center,
            style: const TextStyle(color: AppColors.textSecondary),
          ),
        ],
      ),
    );
  }
}

class _LeaveErrorView extends StatelessWidget {
  final bool isSwahili;
  final VoidCallback onRetry;

  const _LeaveErrorView({
    required this.isSwahili,
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
                  ? 'Imeshindikana kupakia maombi ya likizo'
                  : 'Failed to load leave requests',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
            ),
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

Color _leaveStatusColor(String status) {
  switch (status.toLowerCase()) {
    case 'approved':
      return AppColors.success;
    case 'rejected':
      return AppColors.error;
    default:
      return AppColors.warning;
  }
}

String _leaveStatusLabel(String status, bool isSwahili) {
  switch (status.toLowerCase()) {
    case 'approved':
      return isSwahili ? 'Imeidhinishwa' : 'Approved';
    case 'rejected':
      return isSwahili ? 'Imekataliwa' : 'Rejected';
    default:
      return isSwahili ? 'Inasubiri' : 'Pending';
  }
}

String _formatDate(String? date) {
  if (date == null || date.isEmpty) return '-';
  try {
    return DateFormat('dd MMM yyyy').format(DateTime.parse(date));
  } catch (_) {
    return date;
  }
}

String _formatDateTime(String? date) {
  if (date == null || date.isEmpty) return '-';
  try {
    return DateFormat('dd MMM yyyy, HH:mm').format(DateTime.parse(date).toLocal());
  } catch (_) {
    return date;
  }
}

int _toInt(dynamic value) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value) ?? 0;
  return 0;
}
