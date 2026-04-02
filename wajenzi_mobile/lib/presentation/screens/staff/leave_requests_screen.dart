import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _leaveRequestStatusProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);

final _searchQueryProvider = StateProvider.autoDispose<String>((ref) => '');

final _leaveRequestsProvider = FutureProvider.autoDispose<Map<String, dynamic>>(
  (ref) async {
    final api = ref.watch(apiClientProvider);
    final status = ref.watch(_leaveRequestStatusProvider);

    try {
      final responses = await Future.wait([
        api.get(
          '/leave-requests',
          queryParameters: {
            if (status != null && status.isNotEmpty) 'status': status,
          },
        ),
        api.get('/leave-requests/balance'),
      ]);

      final listData = responses[0].data is Map<String, dynamic>
          ? responses[0].data as Map<String, dynamic>
          : const <String, dynamic>{};
      final balanceData = responses[1].data is Map<String, dynamic>
          ? responses[1].data as Map<String, dynamic>
          : const <String, dynamic>{};

      return {
        'items': (listData['data'] as List? ?? const [])
            .whereType<Map>()
            .map((item) => Map<String, dynamic>.from(item))
            .toList(),
        'meta': listData['meta'] is Map
            ? Map<String, dynamic>.from(listData['meta'] as Map)
            : const <String, dynamic>{},
        'balance': balanceData['data'] is Map
            ? Map<String, dynamic>.from(balanceData['data'] as Map)
            : const <String, dynamic>{},
        'unavailable_on_live': false,
      };
    } on DioException catch (error) {
      if ((error.response?.statusCode ?? 0) == 404) {
        return {
          'items': const <Map<String, dynamic>>[],
          'meta': const <String, dynamic>{},
          'balance': const <String, dynamic>{},
          'unavailable_on_live': true,
        };
      }
      rethrow;
    }
  },
);

final _leaveRequestDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/leave-requests/$id');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      return data['data'] is Map
          ? Map<String, dynamic>.from(data['data'] as Map)
          : const <String, dynamic>{};
    });

final _leaveRequestTypesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/leave-requests/types');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final items = data['data'] as List? ?? const [];
      return items
          .whereType<Map>()
          .map((item) => Map<String, dynamic>.from(item))
          .toList();
    });

class LeaveRequestsScreen extends ConsumerStatefulWidget {
  const LeaveRequestsScreen({super.key});

  @override
  ConsumerState<LeaveRequestsScreen> createState() =>
      _LeaveRequestsScreenState();
}

class _LeaveRequestsScreenState extends ConsumerState<LeaveRequestsScreen> {
  final _searchController = TextEditingController();

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final leaveAsync = ref.watch(_leaveRequestsProvider);
    final selectedStatus = ref.watch(_leaveRequestStatusProvider);
    final searchQuery = ref.watch(_searchQueryProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Maombi ya Likizo' : 'Leave Requests'),
      ),
      body: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.05),
                  blurRadius: 4,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: Column(
              children: [
                TextField(
                  controller: _searchController,
                  onChanged: (value) =>
                      ref.read(_searchQueryProvider.notifier).state = value,
                  decoration: InputDecoration(
                    hintText: isSwahili
                        ? 'Tafuta maombi...'
                        : 'Search requests...',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: searchQuery.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () {
                              _searchController.clear();
                              ref.read(_searchQueryProvider.notifier).state =
                                  '';
                            },
                          )
                        : null,
                    filled: true,
                    fillColor: isDarkMode
                        ? const Color(0xFF2A2A3E)
                        : Colors.grey[100],
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                SizedBox(
                  height: 40,
                  child: ListView(
                    scrollDirection: Axis.horizontal,
                    children: [
                      _FilterChip(
                        label: isSwahili ? 'Zote' : 'All',
                        isSelected: selectedStatus == null,
                        onTap: () =>
                            ref
                                    .read(_leaveRequestStatusProvider.notifier)
                                    .state =
                                null,
                        isDarkMode: isDarkMode,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: isSwahili ? 'Inasubiri' : 'Pending',
                        isSelected: selectedStatus == 'pending',
                        onTap: () =>
                            ref
                                    .read(_leaveRequestStatusProvider.notifier)
                                    .state =
                                'pending',
                        isDarkMode: isDarkMode,
                        color: Colors.orange,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: isSwahili ? 'Imeidhinishwa' : 'Approved',
                        isSelected: selectedStatus == 'approved',
                        onTap: () =>
                            ref
                                    .read(_leaveRequestStatusProvider.notifier)
                                    .state =
                                'approved',
                        isDarkMode: isDarkMode,
                        color: AppColors.success,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: isSwahili ? 'Imekataliwa' : 'Rejected',
                        isSelected: selectedStatus == 'rejected',
                        onTap: () =>
                            ref
                                    .read(_leaveRequestStatusProvider.notifier)
                                    .state =
                                'rejected',
                        isDarkMode: isDarkMode,
                        color: AppColors.error,
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async {
                ref.invalidate(_leaveRequestsProvider);
                await ref.read(_leaveRequestsProvider.future);
              },
              child: leaveAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => _ErrorView(
                  isSwahili: isSwahili,
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  onRetry: () => ref.invalidate(_leaveRequestsProvider),
                ),
                data: (payload) {
                  if (payload['unavailable_on_live'] == true) {
                    return Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.event_note_outlined,
                              size: 64,
                              color: Colors.grey[400],
                            ),
                            const SizedBox(height: 16),
                            Text(
                              isSwahili
                                  ? 'Leave Requests haipatikani kwenye live API kwa sasa.'
                                  : 'Leave Requests is not available on the live API right now.',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: Colors.grey[700],
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  }

                  final items = payload['items'] as List? ?? [];
                  final meta =
                      payload['meta'] as Map<String, dynamic>? ?? const {};
                  final balance =
                      payload['balance'] as Map<String, dynamic>? ?? const {};

                  final filtered = items
                      .whereType<Map>()
                      .where((item) {
                        if (searchQuery.isEmpty) return true;
                        final query = searchQuery.toLowerCase();
                        final leaveTypeName = item['leave_type'] is Map
                            ? (item['leave_type'] as Map)['name']?.toString() ??
                                  ''
                            : '';
                        final reason = item['reason']?.toString() ?? '';
                        return leaveTypeName.toLowerCase().contains(query) ||
                            reason.toLowerCase().contains(query);
                      })
                      .map((item) => Map<String, dynamic>.from(item))
                      .toList();

                  return ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    children: [
                      _LeaveBalanceCard(
                        balance: balance,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                      ),
                      const SizedBox(height: 16),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            isSwahili
                                ? 'Maombi (${filtered.length})'
                                : 'Requests (${filtered.length})',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w600,
                              color: isDarkMode
                                  ? Colors.white
                                  : AppColors.textPrimary,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      if (filtered.isEmpty)
                        _EmptyView(isSwahili: isSwahili, isDarkMode: isDarkMode)
                      else
                        ...filtered.asMap().entries.map(
                          (entry) => _LeaveRequestCard(
                            item: entry.value,
                            index: entry.key + 1,
                            isSwahili: isSwahili,
                            isDarkMode: isDarkMode,
                            onTap: () => _showLeaveRequestSheet(
                              context,
                              ref,
                              entry.value,
                              isSwahili,
                            ),
                          ),
                        ),
                      const SizedBox(height: 100),
                    ],
                  );
                },
              ),
            ),
          ),
        ],
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 70),
        child: FloatingActionButton(
          onPressed: () => _openLeaveRequestForm(context, ref),
          backgroundColor: AppColors.primary,
          child: const Icon(Icons.add, color: Colors.white),
        ),
      ),
    );
  }

  void _showLeaveRequestSheet(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> request,
    bool isSwahili,
  ) {
    final id = _toInt(request['id']);
    if (id <= 0) return;

    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.78,
        child: Consumer(
          builder: (context, ref, _) {
            final detailAsync = ref.watch(_leaveRequestDetailProvider(id));
            return detailAsync.when(
              loading: () => const Center(child: CircularProgressIndicator()),
              error: (error, _) => Material(
                color: Colors.transparent,
                child: _ErrorView(
                  isSwahili: isSwahili,
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  onRetry: () =>
                      ref.invalidate(_leaveRequestDetailProvider(id)),
                ),
              ),
              data: (detail) {
                final status = (detail['status'] as String? ?? 'pending')
                    .toLowerCase();
                final isPending = status == 'pending';
                final leaveType =
                    (detail['leave_type'] as Map<String, dynamic>?)?['name']
                        as String? ??
                    (isSwahili ? 'Ombi la Likizo' : 'Leave Request');
                final isDarkMode = ref.watch(isDarkModeProvider);

                return Container(
                  decoration: BoxDecoration(
                    color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
                    borderRadius: const BorderRadius.vertical(
                      top: Radius.circular(24),
                    ),
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
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  CircleAvatar(
                                    radius: 24,
                                    backgroundColor: _leaveStatusColor(
                                      status,
                                    ).withValues(alpha: 0.1),
                                    child: Icon(
                                      Icons.event_note,
                                      color: _leaveStatusColor(status),
                                    ),
                                  ),
                                  const SizedBox(width: 16),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          leaveType,
                                          style: const TextStyle(
                                            fontSize: 20,
                                            fontWeight: FontWeight.w700,
                                          ),
                                        ),
                                        const SizedBox(height: 4),
                                        _StatusBadge(
                                          status: status,
                                          isSwahili: isSwahili,
                                        ),
                                      ],
                                    ),
                                  ),
                                  if (isPending)
                                    PopupMenuButton<String>(
                                      onSelected: (value) async {
                                        Navigator.pop(context);
                                        if (value == 'edit') {
                                          await _openLeaveRequestForm(
                                            context,
                                            ref,
                                            request: detail,
                                          );
                                        } else if (value == 'delete') {
                                          await _deleteLeaveRequest(
                                            context,
                                            ref,
                                            detail,
                                          );
                                        }
                                      },
                                      itemBuilder: (_) => [
                                        PopupMenuItem(
                                          value: 'edit',
                                          child: Row(
                                            children: [
                                              const Icon(
                                                Icons.edit_outlined,
                                                size: 20,
                                              ),
                                              const SizedBox(width: 10),
                                              Text(
                                                isSwahili ? 'Hariri' : 'Edit',
                                              ),
                                            ],
                                          ),
                                        ),
                                        PopupMenuItem(
                                          value: 'delete',
                                          child: Row(
                                            children: [
                                              const Icon(
                                                Icons.delete_outlined,
                                                size: 20,
                                                color: AppColors.error,
                                              ),
                                              const SizedBox(width: 10),
                                              Text(
                                                isSwahili ? 'Ghairi' : 'Cancel',
                                                style: const TextStyle(
                                                  color: AppColors.error,
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ],
                                    ),
                                ],
                              ),
                              const SizedBox(height: 20),
                              _DetailRow(
                                icon: Icons.calendar_today_outlined,
                                label: isSwahili
                                    ? 'Tarehe ya Kuanza'
                                    : 'Start Date',
                                value: _formatDate(
                                  detail['start_date'] as String?,
                                ),
                                isDarkMode: isDarkMode,
                              ),
                              _DetailRow(
                                icon: Icons.event_outlined,
                                label: isSwahili
                                    ? 'Tarehe ya Mwisho'
                                    : 'End Date',
                                value: _formatDate(
                                  detail['end_date'] as String?,
                                ),
                                isDarkMode: isDarkMode,
                              ),
                              _DetailRow(
                                icon: Icons.timelapse_outlined,
                                label: isSwahili
                                    ? 'Jumla ya Siku'
                                    : 'Total Days',
                                value:
                                    '${detail['total_days'] ?? 0} ${isSwahili ? 'siku' : 'days'}',
                                isDarkMode: isDarkMode,
                              ),
                              if ((detail['reason'] as String? ?? '')
                                  .isNotEmpty)
                                _DetailRow(
                                  icon: Icons.description_outlined,
                                  label: isSwahili ? 'Sababu' : 'Reason',
                                  value: detail['reason'] as String? ?? '-',
                                  isDarkMode: isDarkMode,
                                ),
                              if ((detail['rejected_reason'] as String? ?? '')
                                  .isNotEmpty)
                                _DetailRow(
                                  icon: Icons.cancel_outlined,
                                  label: isSwahili
                                      ? 'Sababu ya Kukataliwa'
                                      : 'Rejected Reason',
                                  value: detail['rejected_reason'] as String,
                                  isDarkMode: isDarkMode,
                                ),
                              if ((detail['approver']
                                      as Map<String, dynamic>?) !=
                                  null)
                                _DetailRow(
                                  icon: Icons.person_outlined,
                                  label: isSwahili
                                      ? 'Aliyeidhinisha'
                                      : 'Approver',
                                  value:
                                      (detail['approver']
                                              as Map<String, dynamic>)['name']
                                          as String? ??
                                      '-',
                                  isDarkMode: isDarkMode,
                                ),
                              _DetailRow(
                                icon: Icons.access_time_outlined,
                                label: isSwahili ? 'Iliundwa' : 'Created',
                                value: _formatDateTime(
                                  detail['created_at'] as String?,
                                ),
                                isDarkMode: isDarkMode,
                              ),
                            ],
                          ),
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

class _FilterChip extends StatelessWidget {
  final String label;
  final bool isSelected;
  final VoidCallback onTap;
  final bool isDarkMode;
  final Color? color;

  const _FilterChip({
    required this.label,
    required this.isSelected,
    required this.onTap,
    required this.isDarkMode,
    this.color,
  });

  @override
  Widget build(BuildContext context) {
    final chipColor = color ?? AppColors.primary;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected
              ? chipColor
              : (isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[200]),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: isSelected
                ? Colors.white
                : (isDarkMode ? Colors.white : Colors.grey[700]),
          ),
        ),
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  final String status;
  final bool isSwahili;

  const _StatusBadge({required this.status, required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    final color = _leaveStatusColor(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        _leaveStatusLabel(status, isSwahili),
        style: TextStyle(
          fontSize: 12,
          color: color,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

class _LeaveBalanceCard extends StatelessWidget {
  final Map<String, dynamic> balance;
  final bool isSwahili;
  final bool isDarkMode;

  const _LeaveBalanceCard({
    required this.balance,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    final balances = (balance['balances'] as List? ?? const [])
        .cast<Map<String, dynamic>>();

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(
          color: isDarkMode ? Colors.white12 : Colors.grey[200]!,
        ),
      ),
      color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(
                  Icons.account_balance_wallet_outlined,
                  color: AppColors.primary,
                  size: 20,
                ),
                const SizedBox(width: 8),
                Text(
                  isSwahili ? 'Mizania ya Likizo' : 'Leave Balance',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            if (balances.isEmpty)
              Text(
                isSwahili ? 'Hakuna mizania ya likizo' : 'No leave balances',
                style: TextStyle(
                  color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
                ),
              )
            else
              ...balances.map(
                (item) => Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          item['name'] as String? ?? '-',
                          style: TextStyle(
                            fontWeight: FontWeight.w500,
                            color: isDarkMode
                                ? Colors.white
                                : AppColors.textPrimary,
                          ),
                        ),
                      ),
                      Text(
                        '${item['days_used'] ?? 0}/${item['days_allowed'] ?? 0}',
                        style: TextStyle(
                          color: isDarkMode
                              ? Colors.white54
                              : AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 2,
                        ),
                        decoration: BoxDecoration(
                          color: AppColors.success.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          '${item['days_remaining'] ?? 0} ${isSwahili ? 'zimabo' : 'left'}',
                          style: const TextStyle(
                            color: AppColors.success,
                            fontWeight: FontWeight.w600,
                            fontSize: 12,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _LeaveRequestCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final int index;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onTap;

  const _LeaveRequestCard({
    required this.item,
    required this.index,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final leaveType =
        (item['leave_type'] as Map<String, dynamic>?)?['name'] as String? ??
        '-';
    final status = (item['status'] as String? ?? 'pending').toLowerCase();
    final statusColor = _leaveStatusColor(status);

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 0,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(
          color: isDarkMode ? Colors.white12 : Colors.grey[200]!,
        ),
      ),
      color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: statusColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Text(
                    '$index',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: statusColor,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      leaveType,
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                        color: isDarkMode
                            ? Colors.white
                            : AppColors.textPrimary,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${_formatDate(item['start_date'] as String?)} - ${_formatDate(item['end_date'] as String?)}',
                      style: TextStyle(
                        fontSize: 12,
                        color: isDarkMode
                            ? Colors.white54
                            : AppColors.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        Text(
                          '${item['total_days'] ?? 0} ${isSwahili ? 'siku' : 'days'}',
                          style: TextStyle(
                            fontSize: 12,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 2,
                          ),
                          decoration: BoxDecoration(
                            color: statusColor.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            _leaveStatusLabel(status, isSwahili),
                            style: TextStyle(
                              fontSize: 10,
                              color: statusColor,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              Icon(
                Icons.chevron_right,
                color: isDarkMode ? Colors.white54 : Colors.grey[400],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final bool isDarkMode;

  const _DetailRow({
    required this.icon,
    required this.label,
    required this.value,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 20, color: AppColors.primary),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(
                    fontSize: 12,
                    color: isDarkMode
                        ? Colors.white54
                        : AppColors.textSecondary,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  value.isEmpty ? '-' : value,
                  style: TextStyle(
                    fontSize: 15,
                    color: isDarkMode ? Colors.white : AppColors.textPrimary,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _EmptyView extends StatelessWidget {
  final bool isSwahili;
  final bool isDarkMode;

  const _EmptyView({required this.isSwahili, required this.isDarkMode});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 48, horizontal: 24),
      child: Column(
        children: [
          Icon(Icons.event_busy_outlined, size: 56, color: Colors.grey[400]),
          const SizedBox(height: 12),
          Text(
            isSwahili ? 'Hakuna maombi ya likizo' : 'No leave requests found',
            textAlign: TextAlign.center,
            style: TextStyle(
              color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  final bool isSwahili;
  final String message;
  final VoidCallback onRetry;

  const _ErrorView({
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
              isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
              textAlign: TextAlign.center,
              style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 16),
            ),
            const SizedBox(height: 8),
            Text(
              message,
              textAlign: TextAlign.center,
              style: TextStyle(
                color: isDarkMode(context)
                    ? Colors.white54
                    : AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: 12),
            ElevatedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: Text(isSwahili ? 'Jaribu tena' : 'Try again'),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
              ),
            ),
          ],
        ),
      ),
    );
  }

  bool isDarkMode(BuildContext context) {
    final isDark = context
        .findAncestorWidgetOfExactType<Scaffold>()
        ?.backgroundColor;
    return isDark == null || isDark == const Color(0xFF1A1A2E);
  }
}

Future<void> _openLeaveRequestForm(
  BuildContext context,
  WidgetRef ref, {
  Map<String, dynamic>? request,
}) async {
  final types = await ref.read(_leaveRequestTypesProvider.future);
  final balances = await ref.read(_leaveRequestsProvider.future);
  final result = await showModalBottomSheet<bool>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.9,
      child: _LeaveRequestFormSheet(
        request: request,
        leaveTypes: types,
        balance: balances['balance'] is Map<String, dynamic>
            ? balances['balance'] as Map<String, dynamic>
            : const <String, dynamic>{},
      ),
    ),
  );

  if (result == true) {
    ref.invalidate(_leaveRequestsProvider);
    ref.invalidate(_leaveRequestTypesProvider);
  }
}

Future<void> _deleteLeaveRequest(
  BuildContext context,
  WidgetRef ref,
  Map<String, dynamic> request,
) async {
  final isSwahili = ref.read(isSwahiliProvider);
  final isDarkMode = ref.read(isDarkModeProvider);
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (dialogContext) => AlertDialog(
      backgroundColor: isDarkMode ? const Color(0xFF1A1A2E) : null,
      title: Text(
        isSwahili ? 'Ghairi Ombi' : 'Cancel Request',
        style: TextStyle(color: isDarkMode ? Colors.white : null),
      ),
      content: Text(
        isSwahili
            ? 'Je, unataka kughairi ombi hili la likizo?'
            : 'Do you want to cancel this leave request?',
        style: TextStyle(color: isDarkMode ? Colors.white70 : null),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, false),
          child: Text(isSwahili ? 'Hapana' : 'No'),
        ),
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, true),
          child: Text(
            isSwahili ? 'Ndiyo, ghairi' : 'Yes, cancel',
            style: const TextStyle(color: AppColors.error),
          ),
        ),
      ],
    ),
  );

  if (confirmed != true) return;

  try {
    await ref
        .read(apiClientProvider)
        .delete('/leave-requests/${request['id']}');
    ref.invalidate(_leaveRequestsProvider);
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            isSwahili
                ? 'Ombi la likizo limeghairiwa'
                : 'Leave request cancelled',
          ),
          backgroundColor: AppColors.success,
        ),
      );
    }
  } catch (error) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(vatErrorMessage(error, isSwahili: isSwahili)),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }
}

class _LeaveRequestFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic>? request;
  final List<Map<String, dynamic>> leaveTypes;
  final Map<String, dynamic> balance;

  const _LeaveRequestFormSheet({
    required this.leaveTypes,
    required this.balance,
    this.request,
  });

  @override
  ConsumerState<_LeaveRequestFormSheet> createState() =>
      _LeaveRequestFormSheetState();
}

class _LeaveRequestFormSheetState
    extends ConsumerState<_LeaveRequestFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _startDateController = TextEditingController(
    text: _dateValue(widget.request?['start_date']?.toString()),
  );
  late final TextEditingController _endDateController = TextEditingController(
    text: _dateValue(widget.request?['end_date']?.toString()),
  );
  late final TextEditingController _reasonController = TextEditingController(
    text: widget.request?['reason']?.toString() ?? '',
  );

  int? _leaveTypeId;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _leaveTypeId = _toNullableInt(widget.request?['leave_type_id']);
  }

  @override
  void dispose() {
    _startDateController.dispose();
    _endDateController.dispose();
    _reasonController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    Map<String, dynamic>? selectedType;
    for (final item in widget.leaveTypes) {
      if (_toNullableInt(item['id']) == _leaveTypeId) {
        selectedType = item;
        break;
      }
    }
    final availableDays = _availableBalanceFor(_leaveTypeId, widget.balance);
    final requestedDays = _requestedDays();

    return Container(
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
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
                padding: EdgeInsets.fromLTRB(
                  20,
                  16,
                  20,
                  MediaQuery.of(context).viewInsets.bottom + 24,
                ),
                children: [
                  Row(
                    children: [
                      CircleAvatar(
                        radius: 24,
                        backgroundColor: AppColors.primary.withValues(
                          alpha: 0.1,
                        ),
                        child: Icon(
                          widget.request == null ? Icons.add : Icons.edit,
                          color: AppColors.primary,
                        ),
                      ),
                      const SizedBox(width: 16),
                      Text(
                        widget.request == null
                            ? (isSwahili
                                  ? 'Ombi Jipya la Likizo'
                                  : 'New Leave Request')
                            : (isSwahili
                                  ? 'Hariri Ombi la Likizo'
                                  : 'Edit Leave Request'),
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 18),
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: AppColors.info.withValues(alpha: 0.08),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(
                        color: AppColors.info.withValues(alpha: 0.15),
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          isSwahili ? 'Muhtasari wa Ombi' : 'Request Summary',
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          isSwahili
                              ? 'Siku zinazopatikana: $availableDays'
                              : 'Available days: $availableDays',
                        ),
                        Text(
                          isSwahili
                              ? 'Siku zinazoombwa: $requestedDays'
                              : 'Requested days: $requestedDays',
                        ),
                        if (selectedType != null &&
                            ((_toNullableInt(selectedType['notice_days']) ??
                                    0) >
                                0))
                          Text(
                            isSwahili
                                ? 'Notisi ya chini: ${selectedType['notice_days']} siku'
                                : 'Minimum notice: ${selectedType['notice_days']} days',
                          ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 18),
                  Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        _typeDropdown(isDarkMode, isSwahili),
                        const SizedBox(height: 16),
                        _dateField(
                          context,
                          controller: _startDateController,
                          label: isSwahili
                              ? 'Tarehe ya Kuanza *'
                              : 'Start Date *',
                          isDarkMode: isDarkMode,
                          firstDate: DateTime.now(),
                        ),
                        const SizedBox(height: 16),
                        _dateField(
                          context,
                          controller: _endDateController,
                          label: isSwahili
                              ? 'Tarehe ya Mwisho *'
                              : 'End Date *',
                          isDarkMode: isDarkMode,
                          firstDate: _selectedStartDate() ?? DateTime.now(),
                        ),
                        const SizedBox(height: 16),
                        _buildInput(
                          controller: _reasonController,
                          label: isSwahili ? 'Sababu *' : 'Reason *',
                          isDarkMode: isDarkMode,
                          maxLines: 4,
                        ),
                        const SizedBox(height: 24),
                        ElevatedButton(
                          onPressed: _saving ? null : _submit,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            foregroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: _saving
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : Text(
                                  widget.request == null
                                      ? (isSwahili ? 'Wasilisha' : 'Submit')
                                      : (isSwahili ? 'Sasisha' : 'Update'),
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInput({
    required TextEditingController controller,
    required String label,
    required bool isDarkMode,
    int maxLines = 1,
  }) {
    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      validator: (value) =>
          value == null || value.trim().isEmpty ? 'Required' : null,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.primary, width: 2),
        ),
      ),
    );
  }

  bool isDarkMode(BuildContext context) {
    final isDark = context
        .findAncestorWidgetOfExactType<Scaffold>()
        ?.backgroundColor;
    return isDark == null || isDark == const Color(0xFF1A1A2E);
  }

  Widget _typeDropdown(bool isDarkMode, bool isSwahili) {
    return DropdownButtonFormField<int>(
      isExpanded: true,
      value:
          widget.leaveTypes.any(
            (item) => _toNullableInt(item['id']) == _leaveTypeId,
          )
          ? _leaveTypeId
          : null,
      validator: (value) => value == null
          ? (isSwahili ? 'Chagua aina ya likizo' : 'Select leave type')
          : null,
      decoration: InputDecoration(
        labelText: isSwahili ? 'Aina ya Likizo *' : 'Leave Type *',
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.primary, width: 2),
        ),
      ),
      items: widget.leaveTypes
          .map(
            (item) => DropdownMenuItem<int>(
              value: _toNullableInt(item['id']),
              child: Text(
                item['name']?.toString() ?? '-',
                overflow: TextOverflow.ellipsis,
              ),
            ),
          )
          .toList(),
      onChanged: (value) => setState(() => _leaveTypeId = value),
    );
  }

  Widget _dateField(
    BuildContext context, {
    required TextEditingController controller,
    required String label,
    required bool isDarkMode,
    required DateTime firstDate,
  }) {
    return TextFormField(
      controller: controller,
      readOnly: true,
      validator: (value) =>
          value == null || value.trim().isEmpty ? 'Required' : null,
      onTap: () async {
        final picked = await showDatePicker(
          context: context,
          initialDate:
              _parseDate(controller.text) ??
              (firstDate.isAfter(DateTime.now()) ? firstDate : DateTime.now()),
          firstDate: firstDate,
          lastDate: DateTime(DateTime.now().year + 3),
        );
        if (picked == null) return;
        controller.text = DateFormat('yyyy-MM-dd').format(picked);
        if (controller == _startDateController) {
          final endDate = _selectedEndDate();
          if (endDate != null && endDate.isBefore(picked)) {
            _endDateController.text = controller.text;
          }
        }
        setState(() {});
      },
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: const Icon(Icons.calendar_today_outlined, size: 20),
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.primary, width: 2),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    final isSwahili = ref.read(isSwahiliProvider);
    if (!_formKey.currentState!.validate()) return;

    final startDate = _selectedStartDate();
    final endDate = _selectedEndDate();
    if (startDate == null || endDate == null) return;

    if (endDate.isBefore(startDate)) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            isSwahili
                ? 'Tarehe ya mwisho haiwezi kuwa kabla ya tarehe ya kuanza'
                : 'End date cannot be before start date',
          ),
          backgroundColor: AppColors.error,
        ),
      );
      return;
    }

    setState(() => _saving = true);

    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'leave_type_id': _leaveTypeId,
        'start_date': _startDateController.text.trim(),
        'end_date': _endDateController.text.trim(),
        'reason': _reasonController.text.trim(),
      };

      if (widget.request == null) {
        await api.post('/leave-requests', data: data);
      } else {
        await api.put('/leave-requests/${widget.request!['id']}', data: data);
      }

      if (mounted) Navigator.pop(context, true);
    } on DioException catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(vatErrorMessage(error, isSwahili: isSwahili)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(vatErrorMessage(error, isSwahili: isSwahili)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  DateTime? _selectedStartDate() => _parseDate(_startDateController.text);
  DateTime? _selectedEndDate() => _parseDate(_endDateController.text);

  int _requestedDays() {
    final startDate = _selectedStartDate();
    final endDate = _selectedEndDate();
    if (startDate == null || endDate == null || endDate.isBefore(startDate))
      return 0;
    return endDate.difference(startDate).inDays + 1;
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
    return DateFormat(
      'dd MMM yyyy, HH:mm',
    ).format(DateTime.parse(date).toLocal());
  } catch (_) {
    return date;
  }
}

String _dateValue(String? value) {
  if (value == null || value.isEmpty) return '';
  try {
    return DateFormat('yyyy-MM-dd').format(DateTime.parse(value));
  } catch (_) {
    return value;
  }
}

DateTime? _parseDate(String? value) {
  if (value == null || value.trim().isEmpty) return null;
  try {
    return DateTime.parse(value.trim());
  } catch (_) {
    return null;
  }
}

int _toInt(dynamic value) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value) ?? 0;
  return 0;
}

int? _toNullableInt(dynamic value) {
  if (value == null) return null;
  final parsed = _toInt(value);
  return parsed > 0 ? parsed : null;
}

int _availableBalanceFor(int? leaveTypeId, Map<String, dynamic> balance) {
  if (leaveTypeId == null) return 0;
  final balances = (balance['balances'] as List? ?? const [])
      .cast<Map<String, dynamic>>();
  for (final item in balances) {
    if (_toNullableInt(item['id']) == leaveTypeId) {
      return _toInt(item['days_remaining']);
    }
  }
  return 0;
}
