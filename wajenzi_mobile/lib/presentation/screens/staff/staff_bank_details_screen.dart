import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _staffBankDetailsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/staff-bank-details');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];
  return items.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
});

final _staffBankRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/staff-bank-details/reference-data');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
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
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_staffBankDetailsProvider),
        child: detailsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _SimpleErrorView(
            message: vatErrorMessage(error, isSwahili: isSwahili),
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_staffBankDetailsProvider),
          ),
          data: (items) {
            if (items.isEmpty) {
              return _EmptyListView(
                icon: Icons.account_balance_outlined,
                label: isSwahili ? 'Hakuna taarifa za benki' : 'No bank details found',
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
                    onTap: () => _showBankDetailSheet(context, item, isDarkMode),
                    contentPadding: const EdgeInsets.all(16),
                    leading: CircleAvatar(
                      backgroundColor: AppColors.primary.withValues(alpha: 0.1),
                      child: const Icon(
                        Icons.account_balance_rounded,
                        color: AppColors.primary,
                      ),
                    ),
                    title: Text(
                      item['staff_name']?.toString() ?? '-',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        color: isDarkMode ? Colors.white : AppColors.textPrimary,
                      ),
                    ),
                    subtitle: Padding(
                      padding: const EdgeInsets.only(top: 6),
                      child: Text(
                        '${item['bank_name'] ?? '-'}\n${isSwahili ? 'Akaunti' : 'Account'}: ${item['account_number'] ?? '-'}',
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    trailing: PopupMenuButton<String>(
                      onSelected: (value) {
                        if (value == 'view') {
                          _showBankDetailSheet(context, item, isDarkMode);
                        } else if (value == 'edit') {
                          _openForm(context, ref, detail: item);
                        } else if (value == 'delete') {
                          _deleteDetail(context, ref, item);
                        }
                      },
                      itemBuilder: (_) => [
                        PopupMenuItem(value: 'view', child: Text(isSwahili ? 'Tazama' : 'View')),
                        PopupMenuItem(value: 'edit', child: Text(isSwahili ? 'Hariri' : 'Edit')),
                        PopupMenuItem(value: 'delete', child: Text(isSwahili ? 'Futa' : 'Delete')),
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

  Future<void> _openForm(BuildContext context, WidgetRef ref, {Map<String, dynamic>? detail}) async {
    final refs = await ref.read(_staffBankRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.84,
        child: _StaffBankDetailFormSheet(refs: refs, detail: detail),
      ),
    );
    if (result == true) ref.invalidate(_staffBankDetailsProvider);
  }

  Future<void> _deleteDetail(BuildContext context, WidgetRef ref, Map<String, dynamic> detail) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        scrollable: true,
        title: Text(isSwahili ? 'Futa Taarifa za Benki' : 'Delete Bank Detail'),
        content: Text(
          isSwahili
              ? 'Je, unataka kufuta taarifa za ${detail['staff_name']}?'
              : 'Delete bank detail for ${detail['staff_name']}?',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(dialogContext, false), child: Text(isSwahili ? 'Ghairi' : 'Cancel')),
          TextButton(onPressed: () => Navigator.pop(dialogContext, true), child: Text(isSwahili ? 'Futa' : 'Delete')),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).delete('/staff-bank-details/${detail['id']}');
      ref.invalidate(_staffBankDetailsProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Taarifa za benki zimefutwa' : 'Bank detail deleted'),
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
}

void _showBankDetailSheet(BuildContext context, Map<String, dynamic> detail, bool isDarkMode) {
  showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.58,
      child: Container(
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
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
                  children: [
                    Text(
                      detail['staff_name']?.toString() ?? 'Bank Detail',
                      style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 18),
                    _BankDetailRow('Bank', detail['bank_name']?.toString() ?? 'N/A'),
                    _BankDetailRow('Account Number', detail['account_number']?.toString() ?? 'N/A'),
                    _BankDetailRow('Branch', detail['branch']?.toString() ?? 'N/A'),
                    _BankDetailRow('Created', _formatDate(detail['created_at']?.toString())),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    ),
  );
}

class _StaffBankDetailFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? detail;

  const _StaffBankDetailFormSheet({
    required this.refs,
    this.detail,
  });

  @override
  ConsumerState<_StaffBankDetailFormSheet> createState() => _StaffBankDetailFormSheetState();
}

class _StaffBankDetailFormSheetState extends ConsumerState<_StaffBankDetailFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _accountNumberController =
      TextEditingController(text: widget.detail?['account_number']?.toString() ?? '');
  late final TextEditingController _branchController =
      TextEditingController(text: widget.detail?['branch']?.toString() ?? '');

  int? _staffId;
  int? _bankId;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _staffId = _toNullableInt(widget.detail?['staff_id']);
    _bankId = _toNullableInt(widget.detail?['bank_id']);
  }

  @override
  void dispose() {
    _accountNumberController.dispose();
    _branchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final staffs = _toMaps(widget.refs['staffs']);
    final banks = _toMaps(widget.refs['banks']);

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
                padding: EdgeInsets.fromLTRB(20, 16, 20, MediaQuery.of(context).viewInsets.bottom + 24),
                children: [
                  Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Text(
                          widget.detail == null
                              ? (isSwahili ? 'Taarifa Mpya za Benki' : 'New Bank Detail')
                              : (isSwahili ? 'Hariri Taarifa za Benki' : 'Edit Bank Detail'),
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 20),
                        _dropdown(
                          isDarkMode: isDarkMode,
                          label: isSwahili ? 'Staff *' : 'Staff *',
                          items: staffs,
                          value: _staffId,
                          onChanged: (value) => setState(() => _staffId = value),
                        ),
                        const SizedBox(height: 12),
                        _dropdown(
                          isDarkMode: isDarkMode,
                          label: isSwahili ? 'Bank *' : 'Bank *',
                          items: banks,
                          value: _bankId,
                          onChanged: (value) => setState(() => _bankId = value),
                        ),
                        const SizedBox(height: 12),
                        _input(_accountNumberController, isSwahili ? 'Namba ya Akaunti *' : 'Account Number *', isDarkMode),
                        const SizedBox(height: 12),
                        _input(_branchController, isSwahili ? 'Tawi *' : 'Branch *', isDarkMode, maxLines: 3),
                        const SizedBox(height: 20),
                        ElevatedButton(
                          onPressed: _saving ? null : _submit,
                          child: _saving
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : Text(widget.detail == null ? (isSwahili ? 'Hifadhi' : 'Save') : (isSwahili ? 'Sasisha' : 'Update')),
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

  Widget _input(
    TextEditingController controller,
    String label,
    bool isDarkMode, {
    int maxLines = 1,
  }) {
    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      validator: (value) => value == null || value.trim().isEmpty ? 'Required' : null,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
    );
  }

  Widget _dropdown({
    required bool isDarkMode,
    required String label,
    required List<Map<String, dynamic>> items,
    required int? value,
    required ValueChanged<int?> onChanged,
  }) {
    return DropdownButtonFormField<int>(
      isExpanded: true,
      value: items.any((item) => _toInt(item['id']) == value) ? value : null,
      validator: (selected) => selected == null ? 'Required' : null,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
      items: items
          .map((item) => DropdownMenuItem<int>(
                value: _toInt(item['id']),
                child: Text(item['name']?.toString() ?? '-', overflow: TextOverflow.ellipsis),
              ))
          .toList(),
      onChanged: onChanged,
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'staff_id': _staffId,
        'bank_id': _bankId,
        'account_number': _accountNumberController.text.trim(),
        'branch': _branchController.text.trim(),
      };

      if (widget.detail == null) {
        await api.post('/staff-bank-details', data: data);
      } else {
        await api.put('/staff-bank-details/${widget.detail!['id']}', data: data);
      }

      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(vatErrorMessage(error, isSwahili: ref.read(isSwahiliProvider))),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
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
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _SimpleErrorView({
    required this.message,
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
          message,
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

List<Map<String, dynamic>> _toMaps(dynamic value) {
  final list = value as List? ?? const [];
  return list.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
}

int _toInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

int? _toNullableInt(dynamic value) {
  final parsed = int.tryParse(value?.toString() ?? '');
  return parsed == null || parsed == 0 ? null : parsed;
}

String _formatDate(String? raw) {
  if (raw == null || raw.isEmpty) return '-';
  try {
    return DateFormat('dd MMM yyyy').format(DateTime.parse(raw));
  } catch (_) {
    return raw;
  }
}
