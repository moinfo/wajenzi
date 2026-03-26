import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _leaveManagementStatusProvider =
    StateProvider.autoDispose<String?>((ref) => null);

final _leaveManagementSearchProvider =
    StateProvider.autoDispose<String>((ref) => '');

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
      : const <String, dynamic>{};
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

class LeaveManagementsScreen extends ConsumerWidget {
  const LeaveManagementsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final itemsAsync = ref.watch(_leaveManagementsProvider);
    final status = ref.watch(_leaveManagementStatusProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Usimamizi wa Likizo' : 'Leave Management'),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
            child: TextField(
              onChanged: (value) =>
                  ref.read(_leaveManagementSearchProvider.notifier).state =
                      value,
              decoration: InputDecoration(
                hintText: isSwahili
                    ? 'Tafuta mfanyakazi, aina au sababu'
                    : 'Search employee, type or reason',
                prefixIcon: const Icon(Icons.search),
                filled: true,
                fillColor: isDarkMode ? const Color(0xFF1A2332) : Colors.white,
              ),
            ),
          ),
          _ManagementFilterBar(
            isSwahili: isSwahili,
            isDarkMode: isDarkMode,
            selectedStatus: status,
            onChanged: (value) => ref
                .read(_leaveManagementStatusProvider.notifier)
                .state = value,
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async => ref.invalidate(_leaveManagementsProvider),
              child: itemsAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => _ManagementErrorView(
                  isSwahili: isSwahili,
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  onRetry: () => ref.invalidate(_leaveManagementsProvider),
                ),
                data: (payload) {
                  final items =
                      (payload['items'] as List).cast<Map<String, dynamic>>();
                  final meta =
                      payload['meta'] as Map<String, dynamic>? ?? const {};

                  if (items.isEmpty) {
                    return ListView(
                      padding: const EdgeInsets.all(32),
                      children: [
                        const SizedBox(height: 100),
                        Icon(
                          Icons.event_note_outlined,
                          size: 56,
                          color: isDarkMode ? Colors.white24 : Colors.grey[300],
                        ),
                        const SizedBox(height: 12),
                        Text(
                          isSwahili
                              ? 'Hakuna maombi ya likizo'
                              : 'No leave requests found',
                          textAlign: TextAlign.center,
                        ),
                      ],
                    );
                  }

                  return ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: items.length + 1,
                    itemBuilder: (context, index) {
                      if (index == items.length) return const SizedBox(height: 90);
                      final item = items[index];
                      final user = item['user'] as Map<String, dynamic>? ?? const {};
                      final leaveType =
                          item['leave_type'] as Map<String, dynamic>? ?? const {};
                      final statusValue =
                          (item['status']?.toString() ?? 'pending').toLowerCase();

                      return Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  CircleAvatar(
                                    backgroundColor: _statusColor(statusValue)
                                        .withValues(alpha: 0.12),
                                    child: Icon(
                                      Icons.assignment_turned_in_outlined,
                                      color: _statusColor(statusValue),
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          user['name']?.toString() ?? '-',
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                          style: const TextStyle(
                                            fontWeight: FontWeight.w700,
                                          ),
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          leaveType['name']?.toString() ?? '-',
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                          style: const TextStyle(
                                            color: AppColors.textSecondary,
                                          ),
                                        ),
                                        const SizedBox(height: 2),
                                        Text(
                                          _dateText(item),
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                          style: const TextStyle(
                                            color: AppColors.textSecondary,
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
                                crossAxisAlignment: WrapCrossAlignment.center,
                                children: [
                                  _ManagementStatusBadge(
                                    status: statusValue,
                                    isSwahili: isSwahili,
                                  ),
                                  Text(
                                    '${item['total_days'] ?? 0} ${isSwahili ? 'siku' : 'days'}',
                                    style: const TextStyle(
                                      color: AppColors.textSecondary,
                                      fontSize: 12,
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 12),
                              Wrap(
                                spacing: 8,
                                runSpacing: 8,
                                children: [
                                  OutlinedButton.icon(
                                    onPressed: () => _showManagementSheet(
                                      context,
                                      ref,
                                      _toInt(item['id']),
                                      isSwahili,
                                    ),
                                    icon: const Icon(Icons.visibility_outlined),
                                    label: Text(isSwahili ? 'Tazama' : 'View'),
                                  ),
                                    if (statusValue == 'pending')
                                      ElevatedButton.icon(
                                        onPressed: () => _openReviewSheet(
                                          context,
                                          ref,
                                          item,
                                          isSwahili,
                                        ),
                                      icon: const Icon(Icons.edit_outlined),
                                      label: Text(
                                        isSwahili ? 'Sasisha' : 'Update',
                                      ),
                                    ),
                                ],
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
          ),
        ],
      ),
      bottomNavigationBar: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
          child: Text(
            isSwahili
                ? 'Jumla ya maombi: ${itemsAsync.valueOrNull?['meta']?['total'] ?? 0}'
                : 'Total requests: ${itemsAsync.valueOrNull?['meta']?['total'] ?? 0}',
            textAlign: TextAlign.center,
            style: const TextStyle(color: AppColors.textSecondary),
          ),
        ),
      ),
    );
  }
}

class _ManagementFilterBar extends StatelessWidget {
  final bool isSwahili;
  final bool isDarkMode;
  final String? selectedStatus;
  final ValueChanged<String?> onChanged;

  const _ManagementFilterBar({
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
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
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
              ),
            );
          }).toList(),
        ),
      ),
    );
  }
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
      heightFactor: 0.58,
      child: Consumer(
        builder: (context, ref, _) {
          final detailAsync = ref.watch(_leaveManagementActionProvider(item));
          final isDarkMode = ref.watch(isDarkModeProvider);
          return _ReviewBottomSheetShell(
            isDarkMode: isDarkMode,
            child: detailAsync.when(
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (error, _) => _ManagementErrorView(
              isSwahili: isSwahili,
              message: vatErrorMessage(error, isSwahili: isSwahili),
              onRetry: () => ref.invalidate(_leaveManagementActionProvider(item)),
            ),
            data: (resolvedItem) => ListView(
              padding: EdgeInsets.fromLTRB(
                20,
                20,
                20,
                MediaQuery.of(context).viewInsets.bottom + 24,
              ),
              children: [
                Text(
                  isSwahili ? 'Sasisha Ombi la Likizo' : 'Update Leave Request',
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 16),
                _DetailRow(
                  isSwahili ? 'Mfanyakazi' : 'Employee',
                  (resolvedItem['user'] as Map<String, dynamic>?)?['name']
                          ?.toString() ??
                      '-',
                ),
                _DetailRow(
                  isSwahili ? 'Aina ya Likizo' : 'Leave Type',
                  ((resolvedItem['leave_type'] as Map<String, dynamic>?)?['name']
                          ?.toString()) ??
                      '-',
                ),
                _DetailRow(
                  isSwahili ? 'Tarehe' : 'Date Range',
                  _dateText(resolvedItem),
                ),
                const SizedBox(height: 12),
                _ReviewForm(
                  itemId: _toInt(resolvedItem['id']),
                  isSwahili: isSwahili,
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
      heightFactor: 0.84,
      child: Consumer(
        builder: (context, ref, _) {
          final detailAsync = ref.watch(_leaveManagementDetailProvider(id));
          final isDarkMode = ref.watch(isDarkModeProvider);
          return _ReviewBottomSheetShell(
            isDarkMode: isDarkMode,
            child: detailAsync.when(
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (error, _) => _ManagementErrorView(
              isSwahili: isSwahili,
              message: vatErrorMessage(error, isSwahili: isSwahili),
              onRetry: () => ref.invalidate(_leaveManagementDetailProvider(id)),
            ),
            data: (item) => ListView(
              padding: EdgeInsets.fromLTRB(
                20,
                16,
                20,
                MediaQuery.of(context).viewInsets.bottom + 24,
              ),
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
                const SizedBox(height: 12),
                _ManagementStatusBadge(
                  status: item['status']?.toString() ?? 'pending',
                  isSwahili: isSwahili,
                ),
                const SizedBox(height: 18),
                _DetailRow(
                  isSwahili ? 'Aina ya Likizo' : 'Leave Type',
                  ((item['leave_type'] as Map<String, dynamic>?)?['name']
                          ?.toString()) ??
                      '-',
                ),
                _DetailRow(
                  isSwahili ? 'Tarehe' : 'Date Range',
                  _dateText(item),
                ),
                _DetailRow(
                  isSwahili ? 'Jumla ya Siku' : 'Total Days',
                  '${item['total_days'] ?? 0}',
                ),
                _DetailRow(
                  isSwahili ? 'Sababu' : 'Reason',
                  item['reason']?.toString() ?? '-',
                ),
                _DetailRow(
                  isSwahili ? 'Maoni ya Admin' : 'Admin Remarks',
                  item['admin_remarks']?.toString().trim().isNotEmpty == true
                      ? item['admin_remarks'].toString()
                      : '-',
                ),
                if ((item['status']?.toString().toLowerCase() ?? 'pending') ==
                    'pending') ...[
                  const SizedBox(height: 18),
                  ElevatedButton.icon(
                    onPressed: () {
                      Navigator.pop(context);
                      _openReviewSheet(context, ref, item, isSwahili);
                    },
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

class _ReviewBottomSheetShell extends StatelessWidget {
  final bool isDarkMode;
  final Widget child;

  const _ReviewBottomSheetShell({
    required this.isDarkMode,
    required this.child,
  });

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

  const _ReviewForm({
    required this.itemId,
    required this.isSwahili,
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
    final isDarkMode = ref.watch(isDarkModeProvider);
    return Form(
      key: _formKey,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            widget.isSwahili ? 'Kagua Ombi' : 'Review Request',
            style: const TextStyle(fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<String>(
            value: _status,
            decoration: InputDecoration(
              labelText: widget.isSwahili ? 'Uamuzi *' : 'Decision *',
              filled: true,
              fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
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
          const SizedBox(height: 12),
          TextFormField(
            controller: _remarksController,
            maxLines: 4,
            validator: (value) => value == null || value.trim().isEmpty
                ? (widget.isSwahili ? 'Maoni yanahitajika' : 'Remarks are required')
                : null,
            decoration: InputDecoration(
              labelText: widget.isSwahili ? 'Maoni *' : 'Remarks *',
              alignLabelWithHint: true,
              filled: true,
              fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
            ),
          ),
          const SizedBox(height: 16),
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
                : Text(widget.isSwahili ? 'Hifadhi Mapitio' : 'Save Review'),
          ),
        ],
      ),
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      await ref.read(apiClientProvider).put(
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
              widget.isSwahili
                  ? 'Mapitio yamehifadhiwa'
                  : 'Leave review saved',
            ),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content:
                Text(vatErrorMessage(error, isSwahili: widget.isSwahili)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _DetailRow(this.label, this.value);

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
          Expanded(child: Text(value.isEmpty ? '-' : value)),
        ],
      ),
    );
  }
}

class _ManagementStatusBadge extends StatelessWidget {
  final String status;
  final bool isSwahili;

  const _ManagementStatusBadge({
    required this.status,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    final color = _statusColor(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        _statusLabel(status, isSwahili),
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: TextStyle(color: color, fontWeight: FontWeight.w600),
      ),
    );
  }
}

class _ManagementErrorView extends StatelessWidget {
  final bool isSwahili;
  final String message;
  final VoidCallback onRetry;

  const _ManagementErrorView({
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
                  ? 'Imeshindikana kupakia usimamizi wa likizo'
                  : 'Failed to load leave management',
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            Text(message, textAlign: TextAlign.center),
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
      return AppColors.warning;
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
