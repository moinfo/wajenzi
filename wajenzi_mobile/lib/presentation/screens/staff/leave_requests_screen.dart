import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

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
  };
});

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
        actions: [
          IconButton(
            onPressed: () => _openLeaveRequestForm(context, ref),
            icon: const Icon(Icons.add),
            tooltip: isSwahili ? 'Omba likizo' : 'New leave request',
          ),
        ],
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
              onRefresh: () async {
                ref.invalidate(_leaveRequestsProvider);
                ref.invalidate(_leaveRequestTypesProvider);
                await ref.read(_leaveRequestsProvider.future);
              },
              child: leaveAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => _LeaveErrorView(
                  isSwahili: isSwahili,
                  message: vatErrorMessage(error, isSwahili: isSwahili),
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
                      Row(
                        children: [
                          Expanded(
                            child: Text(
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
                          ),
                          TextButton.icon(
                            onPressed: () => _openLeaveRequestForm(context, ref),
                            icon: const Icon(Icons.add_circle_outline),
                            label: Text(isSwahili ? 'Omba' : 'Request'),
                          ),
                        ],
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
                              item,
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
                  crossAxisAlignment: CrossAxisAlignment.start,
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
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
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
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (dialogContext) => AlertDialog(
      scrollable: true,
      title: Text(isSwahili ? 'Ghairi Ombi' : 'Cancel Request'),
      content: Text(
        isSwahili
            ? 'Je, unataka kughairi ombi hili la likizo?'
            : 'Do you want to cancel this leave request?',
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, false),
          child: Text(isSwahili ? 'Hapana' : 'No'),
        ),
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, true),
          child: Text(isSwahili ? 'Ndiyo, ghairi' : 'Yes, cancel'),
        ),
      ],
    ),
  );

  if (confirmed != true) return;

  try {
    await ref.read(apiClientProvider).delete('/leave-requests/${request['id']}');
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
              child: _LeaveErrorView(
                isSwahili: isSwahili,
                message: vatErrorMessage(error, isSwahili: isSwahili),
                onRetry: () => ref.invalidate(_leaveRequestDetailProvider(id)),
              ),
            ),
            data: (detail) {
              final status =
                  (detail['status'] as String? ?? 'pending').toLowerCase();
              final isPending = status == 'pending';
              final leaveType =
                  (detail['leave_type'] as Map<String, dynamic>?)?['name']
                          as String? ??
                      (isSwahili ? 'Ombi la Likizo' : 'Leave Request');
              final isDarkMode = ref.watch(isDarkModeProvider);

              return Container(
                decoration: BoxDecoration(
                  color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
                  borderRadius:
                      const BorderRadius.vertical(top: Radius.circular(24)),
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
                                Expanded(
                                  child: Text(
                                    leaveType,
                                    style: const TextStyle(
                                      fontSize: 20,
                                      fontWeight: FontWeight.w700,
                                    ),
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
                                        child: Text(
                                          isSwahili ? 'Hariri' : 'Edit',
                                        ),
                                      ),
                                      PopupMenuItem(
                                        value: 'delete',
                                        child: Text(
                                          isSwahili ? 'Ghairi' : 'Cancel',
                                        ),
                                      ),
                                    ],
                                  ),
                              ],
                            ),
                            const SizedBox(height: 12),
                            Wrap(
                              spacing: 8,
                              runSpacing: 8,
                              children: [
                                _LeaveStatusBadge(
                                  status: status,
                                  isSwahili: isSwahili,
                                ),
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 10,
                                    vertical: 5,
                                  ),
                                  decoration: BoxDecoration(
                                    color: AppColors.primary.withValues(
                                      alpha: 0.08,
                                    ),
                                    borderRadius: BorderRadius.circular(999),
                                  ),
                                  child: Text(
                                    '${detail['total_days'] ?? 0} ${isSwahili ? 'siku' : 'days'}',
                                    style: const TextStyle(
                                      color: AppColors.primary,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ),
                              ],
                            ),
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
                            if ((detail['rejected_reason'] as String? ?? '')
                                .isNotEmpty)
                              _LeaveDetailRow(
                                isSwahili
                                    ? 'Sababu ya Kukataliwa'
                                    : 'Rejected Reason',
                                detail['rejected_reason'] as String,
                              ),
                            if ((detail['approver'] as Map<String, dynamic>?) !=
                                null)
                              _LeaveDetailRow(
                                isSwahili ? 'Aliyeidhinisha' : 'Approver',
                                (detail['approver']
                                            as Map<String, dynamic>)['name']
                                        as String? ??
                                    '-',
                              ),
                            _LeaveDetailRow(
                              isSwahili ? 'Iliundwa' : 'Created',
                              _formatDateTime(detail['created_at'] as String?),
                            ),
                            if (isPending) ...[
                              const SizedBox(height: 20),
                              OutlinedButton.icon(
                                onPressed: () async {
                                  Navigator.pop(context);
                                  await _openLeaveRequestForm(
                                    context,
                                    ref,
                                    request: detail,
                                  );
                                },
                                icon: const Icon(Icons.edit_outlined),
                                label: Text(
                                  isSwahili ? 'Hariri Ombi' : 'Edit Request',
                                ),
                              ),
                            ],
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
                padding: EdgeInsets.fromLTRB(
                  20,
                  16,
                  20,
                  MediaQuery.of(context).viewInsets.bottom + 24,
                ),
                children: [
                  Text(
                    widget.request == null
                        ? (isSwahili ? 'Ombi Jipya la Likizo' : 'New Leave Request')
                        : (isSwahili ? 'Hariri Ombi la Likizo' : 'Edit Leave Request'),
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                    ),
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
                            ((_toNullableInt(selectedType['notice_days']) ?? 0) >
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
                        const SizedBox(height: 12),
                        _dateField(
                          context,
                          controller: _startDateController,
                          label:
                              isSwahili ? 'Tarehe ya Kuanza *' : 'Start Date *',
                          isDarkMode: isDarkMode,
                          firstDate: DateTime.now(),
                        ),
                        const SizedBox(height: 12),
                        _dateField(
                          context,
                          controller: _endDateController,
                          label:
                              isSwahili ? 'Tarehe ya Mwisho *' : 'End Date *',
                          isDarkMode: isDarkMode,
                          firstDate: _selectedStartDate() ?? DateTime.now(),
                        ),
                        const SizedBox(height: 12),
                        TextFormField(
                          controller: _reasonController,
                          maxLines: 5,
                          validator: (value) =>
                              value == null || value.trim().isEmpty
                                  ? (isSwahili
                                      ? 'Sababu inahitajika'
                                      : 'Reason is required')
                                  : null,
                          decoration: InputDecoration(
                            labelText: isSwahili ? 'Sababu *' : 'Reason *',
                            alignLabelWithHint: true,
                            filled: true,
                            fillColor: isDarkMode
                                ? const Color(0xFF2A2A3E)
                                : Colors.grey[100],
                          ),
                        ),
                        const SizedBox(height: 20),
                        ElevatedButton(
                          onPressed: _saving ? null : _submit,
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

  Widget _typeDropdown(bool isDarkMode, bool isSwahili) {
    return DropdownButtonFormField<int>(
      isExpanded: true,
      value: widget.leaveTypes.any((item) => _toNullableInt(item['id']) == _leaveTypeId)
          ? _leaveTypeId
          : null,
      validator: (value) => value == null
          ? (isSwahili ? 'Chagua aina ya likizo' : 'Select leave type')
          : null,
      decoration: InputDecoration(
        labelText: isSwahili ? 'Aina ya Likizo *' : 'Leave Type *',
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
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
          initialDate: _parseDate(controller.text) ??
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
        suffixIcon: const Icon(Icons.calendar_today_outlined),
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
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
    if (startDate == null || endDate == null || endDate.isBefore(startDate)) {
      return 0;
    }
    return endDate.difference(startDate).inDays + 1;
  }
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
  final String message;
  final VoidCallback onRetry;

  const _LeaveErrorView({
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
                  ? 'Imeshindikana kupakia maombi ya likizo'
                  : 'Failed to load leave requests',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
            ),
            const SizedBox(height: 8),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(color: AppColors.textSecondary),
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
    return DateFormat('dd MMM yyyy, HH:mm')
        .format(DateTime.parse(date).toLocal());
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
  final balances =
      (balance['balances'] as List? ?? const []).cast<Map<String, dynamic>>();
  for (final item in balances) {
    if (_toNullableInt(item['id']) == leaveTypeId) {
      return _toInt(item['days_remaining']);
    }
  }
  return 0;
}
