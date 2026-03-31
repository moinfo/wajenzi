import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _leaveManagementStatusProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);

final _leaveManagementSearchProvider = StateProvider.autoDispose<String>(
  (ref) => '',
);

final _leaveManagementsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final status = ref.watch(_leaveManagementStatusProvider);
      final search = ref.watch(_leaveManagementSearchProvider);
      final response = await api.get(
        '/leave-managements',
        queryParameters: {
          if (status != null && status.isNotEmpty) 'status': status,
          if (search.trim().isNotEmpty) 'search': search.trim(),
        },
      );
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      final items = data['data'] as List? ?? const [];
      return {
        'items': items
            .whereType<Map>()
            .map((item) => Map<String, dynamic>.from(item))
            .toList(),
        'meta': data['meta'] is Map
            ? Map<String, dynamic>.from(data['meta'] as Map)
            : const <String, dynamic>{},
      };
    });

final _leaveManagementDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/leave-managements/$id');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      return data['data'] is Map
          ? Map<String, dynamic>.from(data['data'] as Map)
          : <String, dynamic>{};
    });

final _leaveManagementActionProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, Map<String, dynamic>>((ref, item) async {
      final id = _toInt(item['id']);
      if (id <= 0) return item;

      final api = ref.watch(apiClientProvider);
      final response = await api.get('/leave-managements/$id');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      return data['data'] is Map
          ? Map<String, dynamic>.from(data['data'] as Map)
          : item;
    });

class LeaveManagementsScreen extends ConsumerStatefulWidget {
  const LeaveManagementsScreen({super.key});

  @override
  ConsumerState<LeaveManagementsScreen> createState() =>
      _LeaveManagementsScreenState();
}

class _LeaveManagementsScreenState
    extends ConsumerState<LeaveManagementsScreen> {
  final _searchController = TextEditingController();

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final itemsAsync = ref.watch(_leaveManagementsProvider);
    final status = ref.watch(_leaveManagementStatusProvider);
    final search = ref.watch(_leaveManagementSearchProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Usimamizi wa Likizo' : 'Leave Management'),
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
                      ref.read(_leaveManagementSearchProvider.notifier).state =
                          value,
                  decoration: InputDecoration(
                    hintText: isSwahili
                        ? 'Tafuta mfanyakazi, aina au sababu'
                        : 'Search employee, type or reason',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: search.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () {
                              _searchController.clear();
                              ref
                                      .read(
                                        _leaveManagementSearchProvider.notifier,
                                      )
                                      .state =
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
                        isSelected: status == null,
                        onTap: () =>
                            ref
                                    .read(
                                      _leaveManagementStatusProvider.notifier,
                                    )
                                    .state =
                                null,
                        isDarkMode: isDarkMode,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: isSwahili ? 'Inasubiri' : 'Pending',
                        isSelected: status == 'pending',
                        onTap: () =>
                            ref
                                    .read(
                                      _leaveManagementStatusProvider.notifier,
                                    )
                                    .state =
                                'pending',
                        isDarkMode: isDarkMode,
                        color: Colors.orange,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: isSwahili ? 'Imeidhinishwa' : 'Approved',
                        isSelected: status == 'approved',
                        onTap: () =>
                            ref
                                    .read(
                                      _leaveManagementStatusProvider.notifier,
                                    )
                                    .state =
                                'approved',
                        isDarkMode: isDarkMode,
                        color: AppColors.success,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: isSwahili ? 'Imekataliwa' : 'Rejected',
                        isSelected: status == 'rejected',
                        onTap: () =>
                            ref
                                    .read(
                                      _leaveManagementStatusProvider.notifier,
                                    )
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
              onRefresh: () async => ref.invalidate(_leaveManagementsProvider),
              child: itemsAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => _ErrorView(
                  isSwahili: isSwahili,
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  onRetry: () => ref.invalidate(_leaveManagementsProvider),
                  isDarkMode: isDarkMode,
                ),
                data: (payload) {
                  final items = payload['items'] as List? ?? [];
                  final meta =
                      payload['meta'] as Map<String, dynamic>? ?? const {};

                  if (items.isEmpty) {
                    return ListView(
                      children: [
                        SizedBox(
                          height: MediaQuery.of(context).size.height * 0.2,
                        ),
                        Icon(
                          Icons.event_note_outlined,
                          size: 64,
                          color: Colors.grey[400],
                        ),
                        const SizedBox(height: 16),
                        Text(
                          isSwahili
                              ? 'Hakuna maombi ya likizo'
                              : 'No leave requests found',
                          textAlign: TextAlign.center,
                          style: TextStyle(
                            fontSize: 16,
                            color: Colors.grey[600],
                          ),
                        ),
                      ],
                    );
                  }

                  return ListView.builder(
                    padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
                    itemCount: items.length,
                    itemBuilder: (context, index) {
                      final item = items[index] as Map<String, dynamic>;
                      final user = item['user'] as Map<String, dynamic>? ?? {};
                      final leaveType =
                          item['leave_type'] as Map<String, dynamic>? ?? {};
                      final statusValue =
                          (item['status']?.toString() ?? 'pending')
                              .toLowerCase();

                      return _LeaveManagementCard(
                        item: item,
                        user: user,
                        leaveType: leaveType,
                        statusValue: statusValue,
                        index: index + 1,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onView: () => _showManagementSheet(
                          context,
                          ref,
                          _toInt(item['id']),
                          isSwahili,
                        ),
                        onReview: statusValue == 'pending'
                            ? () => _openReviewSheet(
                                context,
                                ref,
                                item,
                                isSwahili,
                              )
                            : null,
                      );
                    },
                  );
                },
              ),
            ),
          ),
        ],
      ),
      bottomNavigationBar: SafeArea(
        top: false,
        child: Container(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
          decoration: BoxDecoration(
            color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.05),
                blurRadius: 4,
                offset: const Offset(0, -2),
              ),
            ],
          ),
          child: Text(
            isSwahili
                ? 'Jumla ya maombi: ${itemsAsync.valueOrNull?['meta']?['total'] ?? 0}'
                : 'Total requests: ${itemsAsync.valueOrNull?['meta']?['total'] ?? 0}',
            textAlign: TextAlign.center,
            style: TextStyle(
              color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
            ),
          ),
        ),
      ),
    );
  }

  void _openReviewSheet(
    BuildContext context,
    WidgetRef ref,
    Map<String, dynamic> item,
    bool isSwahili,
  ) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.6,
        child: Consumer(
          builder: (context, ref, _) {
            final detailAsync = ref.watch(_leaveManagementActionProvider(item));
            final isDarkMode = ref.watch(isDarkModeProvider);
            return _BottomSheetShell(
              isDarkMode: isDarkMode,
              child: detailAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => _ErrorView(
                  isSwahili: isSwahili,
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  onRetry: () =>
                      ref.invalidate(_leaveManagementActionProvider(item)),
                  isDarkMode: isDarkMode,
                ),
                data: (resolvedItem) => ListView(
                  padding: EdgeInsets.fromLTRB(
                    20,
                    20,
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
                          child: const Icon(
                            Icons.edit,
                            color: AppColors.primary,
                          ),
                        ),
                        const SizedBox(width: 16),
                        Text(
                          isSwahili
                              ? 'Sasisha Ombi la Likizo'
                              : 'Update Leave Request',
                          style: const TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    _DetailRow(
                      icon: Icons.person_outlined,
                      label: isSwahili ? 'Mfanyakazi' : 'Employee',
                      value:
                          (resolvedItem['user']
                                  as Map<String, dynamic>?)?['name']
                              ?.toString() ??
                          '-',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      icon: Icons.event_note_outlined,
                      label: isSwahili ? 'Aina ya Likizo' : 'Leave Type',
                      value:
                          (resolvedItem['leave_type']
                                  as Map<String, dynamic>?)?['name']
                              ?.toString() ??
                          '-',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      icon: Icons.calendar_today_outlined,
                      label: isSwahili ? 'Tarehe' : 'Date Range',
                      value: _dateText(resolvedItem),
                      isDarkMode: isDarkMode,
                    ),
                    const SizedBox(height: 12),
                    _ReviewForm(
                      itemId: _toInt(resolvedItem['id']),
                      isSwahili: isSwahili,
                      isDarkMode: isDarkMode,
                    ),
                  ],
                ),
              ),
            );
          },
        ),
      ),
    );
  }

  void _showManagementSheet(
    BuildContext context,
    WidgetRef ref,
    int id,
    bool isSwahili,
  ) {
    if (id <= 0) return;

    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.78,
        child: Consumer(
          builder: (context, ref, _) {
            final detailAsync = ref.watch(_leaveManagementDetailProvider(id));
            final isDarkMode = ref.watch(isDarkModeProvider);
            return _BottomSheetShell(
              isDarkMode: isDarkMode,
              child: detailAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => _ErrorView(
                  isSwahili: isSwahili,
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  onRetry: () =>
                      ref.invalidate(_leaveManagementDetailProvider(id)),
                  isDarkMode: isDarkMode,
                ),
                data: (item) => ListView(
                  padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
                  children: [
                    Row(
                      children: [
                        CircleAvatar(
                          radius: 28,
                          backgroundColor: AppColors.primary.withValues(
                            alpha: 0.1,
                          ),
                          child: const Icon(
                            Icons.event_note,
                            color: AppColors.primary,
                            size: 28,
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                (item['user'] as Map<String, dynamic>?)?['name']
                                        ?.toString() ??
                                    '-',
                                style: const TextStyle(
                                  fontSize: 20,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              const SizedBox(height: 4),
                              _StatusBadge(
                                status: item['status']?.toString() ?? 'pending',
                                isSwahili: isSwahili,
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 24),
                    _DetailRow(
                      icon: Icons.event_note_outlined,
                      label: isSwahili ? 'Aina ya Likizo' : 'Leave Type',
                      value:
                          (item['leave_type'] as Map<String, dynamic>?)?['name']
                              ?.toString() ??
                          '-',
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      icon: Icons.calendar_today_outlined,
                      label: isSwahili ? 'Tarehe' : 'Date Range',
                      value: _dateText(item),
                      isDarkMode: isDarkMode,
                    ),
                    _DetailRow(
                      icon: Icons.timelapse_outlined,
                      label: isSwahili ? 'Jumla ya Siku' : 'Total Days',
                      value: '${item['total_days'] ?? 0}',
                      isDarkMode: isDarkMode,
                    ),
                    if ((item['reason']?.toString().trim().isNotEmpty == true))
                      _DetailRow(
                        icon: Icons.description_outlined,
                        label: isSwahili ? 'Sababu' : 'Reason',
                        value: item['reason']?.toString() ?? '-',
                        isDarkMode: isDarkMode,
                      ),
                    _DetailRow(
                      icon: Icons.comment_outlined,
                      label: isSwahili ? 'Maoni ya Admin' : 'Admin Remarks',
                      value:
                          item['admin_remarks']?.toString().trim().isNotEmpty ==
                              true
                          ? item['admin_remarks'].toString()
                          : '-',
                      isDarkMode: isDarkMode,
                    ),
                    if ((item['status']?.toString() ?? 'pending')
                            .toLowerCase() ==
                        'pending') ...[
                      const SizedBox(height: 18),
                      ElevatedButton.icon(
                        onPressed: () {
                          Navigator.pop(context);
                          _openReviewSheet(context, ref, item, isSwahili);
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                        icon: const Icon(Icons.edit_outlined),
                        label: Text(
                          isSwahili ? 'Sasisha Ombi' : 'Update Request',
                        ),
                      ),
                    ],
                  ],
                ),
              ),
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
    final color = _statusColor(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Text(
        _statusLabel(status, isSwahili),
        style: TextStyle(
          fontSize: 12,
          color: color,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

class _LeaveManagementCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final Map<String, dynamic> user;
  final Map<String, dynamic> leaveType;
  final String statusValue;
  final int index;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback onView;
  final VoidCallback? onReview;

  const _LeaveManagementCard({
    required this.item,
    required this.user,
    required this.leaveType,
    required this.statusValue,
    required this.index,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onView,
    this.onReview,
  });

  @override
  Widget build(BuildContext context) {
    final statusColor = _statusColor(statusValue);

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
        onTap: onView,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
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
                          user['name']?.toString() ?? '-',
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
                          leaveType['name']?.toString() ?? '-',
                          style: TextStyle(
                            fontSize: 12,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          _dateText(item),
                          style: TextStyle(
                            fontSize: 12,
                            color: isDarkMode
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  _StatusBadge(status: statusValue, isSwahili: isSwahili),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: AppColors.primary.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      '${item['total_days'] ?? 0} ${isSwahili ? 'siku' : 'days'}',
                      style: const TextStyle(
                        fontSize: 11,
                        color: AppColors.primary,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ],
              ),
              if (statusValue.toLowerCase() == 'pending') ...[
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: onView,
                        icon: const Icon(Icons.visibility_outlined, size: 18),
                        label: Text(isSwahili ? 'Tazama' : 'View'),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: AppColors.primary,
                          side: const BorderSide(color: AppColors.primary),
                          padding: const EdgeInsets.symmetric(vertical: 10),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: ElevatedButton.icon(
                        onPressed: onReview,
                        icon: const Icon(Icons.edit_outlined, size: 18),
                        label: Text(isSwahili ? 'Review' : 'Review'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 10),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
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

class _BottomSheetShell extends StatelessWidget {
  final bool isDarkMode;
  final Widget child;

  const _BottomSheetShell({required this.isDarkMode, required this.child});

  @override
  Widget build(BuildContext context) {
    return Container(
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
            Expanded(child: child),
          ],
        ),
      ),
    );
  }
}

class _ReviewForm extends ConsumerStatefulWidget {
  final int itemId;
  final bool isSwahili;
  final bool isDarkMode;

  const _ReviewForm({
    required this.itemId,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  ConsumerState<_ReviewForm> createState() => _ReviewFormState();
}

class _ReviewFormState extends ConsumerState<_ReviewForm> {
  final _formKey = GlobalKey<FormState>();
  final _remarksController = TextEditingController();
  String _status = 'approved';
  bool _saving = false;

  @override
  void dispose() {
    _remarksController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            widget.isSwahili ? 'Kagua Ombi' : 'Review Request',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w700,
              color: widget.isDarkMode ? Colors.white : AppColors.textPrimary,
            ),
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<String>(
            value: _status,
            decoration: InputDecoration(
              labelText: widget.isSwahili ? 'Uamuzi *' : 'Decision *',
              filled: true,
              fillColor: widget.isDarkMode
                  ? const Color(0xFF2A2A3E)
                  : Colors.grey[100],
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide.none,
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(
                  color: AppColors.primary,
                  width: 2,
                ),
              ),
            ),
            items: [
              DropdownMenuItem(
                value: 'approved',
                child: Text(widget.isSwahili ? 'Idhinisha' : 'Approve'),
              ),
              DropdownMenuItem(
                value: 'rejected',
                child: Text(widget.isSwahili ? 'Kataa' : 'Reject'),
              ),
            ],
            onChanged: (value) => setState(() => _status = value ?? 'approved'),
          ),
          const SizedBox(height: 16),
          TextFormField(
            controller: _remarksController,
            maxLines: 4,
            validator: (value) => value == null || value.trim().isEmpty
                ? (widget.isSwahili
                      ? 'Maoni yanahitajika'
                      : 'Remarks are required')
                : null,
            decoration: InputDecoration(
              labelText: widget.isSwahili ? 'Maoni *' : 'Remarks *',
              alignLabelWithHint: true,
              filled: true,
              fillColor: widget.isDarkMode
                  ? const Color(0xFF2A2A3E)
                  : Colors.grey[100],
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide.none,
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(
                  color: AppColors.primary,
                  width: 2,
                ),
              ),
            ),
          ),
          const SizedBox(height: 20),
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
                    widget.isSwahili ? 'Hifadhi Mapitio' : 'Save Review',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
          ),
        ],
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      await ref
          .read(apiClientProvider)
          .put(
            '/leave-managements/${widget.itemId}',
            data: {
              'status': _status,
              'admin_remarks': _remarksController.text.trim(),
            },
          );
      ref.invalidate(_leaveManagementsProvider);
      ref.invalidate(_leaveManagementDetailProvider(widget.itemId));
      if (mounted) {
        Navigator.pop(context);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              widget.isSwahili ? 'Mapitio yamehifadhiwa' : 'Leave review saved',
            ),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(vatErrorMessage(error, isSwahili: widget.isSwahili)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

class _ErrorView extends StatelessWidget {
  final bool isSwahili;
  final String message;
  final VoidCallback onRetry;
  final bool isDarkMode;

  const _ErrorView({
    required this.isSwahili,
    required this.message,
    required this.onRetry,
    required this.isDarkMode,
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
            const SizedBox(height: 16),
            Text(
              isSwahili ? 'Hitilafu imetokea' : 'Something went wrong',
              textAlign: TextAlign.center,
              style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 16),
            ),
            const SizedBox(height: 8),
            Text(message, textAlign: TextAlign.center),
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
}

String _dateText(Map<String, dynamic> item) {
  final start = item['start_date']?.toString() ?? '';
  final end = item['end_date']?.toString() ?? '';
  return '${_fmtDate(start)} - ${_fmtDate(end)}';
}

String _fmtDate(String? value) {
  if (value == null || value.isEmpty) return '-';
  try {
    final date = DateTime.parse(value);
    return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
  } catch (_) {
    return value;
  }
}

Color _statusColor(String status) {
  switch (status.toLowerCase()) {
    case 'approved':
      return AppColors.success;
    case 'rejected':
      return AppColors.error;
    default:
      return Colors.orange;
  }
}

String _statusLabel(String status, bool isSwahili) {
  switch (status.toLowerCase()) {
    case 'approved':
      return isSwahili ? 'Imeidhinishwa' : 'Approved';
    case 'rejected':
      return isSwahili ? 'Imekataliwa' : 'Rejected';
    default:
      return isSwahili ? 'Inasubiri' : 'Pending';
  }
}

int _toInt(dynamic value) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value) ?? 0;
  return 0;
}
