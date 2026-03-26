import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _payrollAdminProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/payroll-administration');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];
  return items.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
});

final _payrollAdminRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/payroll-administration/reference-data');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

class PayrollAdministrationScreen extends ConsumerWidget {
  const PayrollAdministrationScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final payrollsAsync = ref.watch(_payrollAdminProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Usimamizi wa Payroll' : 'Payroll Administration'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_payrollAdminProvider),
        child: payrollsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _PayrollAdminErrorView(
            message: vatErrorMessage(error, isSwahili: isSwahili),
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_payrollAdminProvider),
          ),
          data: (payrolls) {
            if (payrolls.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(Icons.payments_outlined, size: 56, color: isDarkMode ? Colors.white24 : Colors.grey[300]),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hakuna payrolls' : 'No payrolls found',
                    textAlign: TextAlign.center,
                  ),
                ],
              );
            }

            return ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: payrolls.length + 1,
              itemBuilder: (context, index) {
                if (index == payrolls.length) return const SizedBox(height: 90);
                final payroll = payrolls[index];
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    onTap: () => _showPayrollSheet(context, payroll, isDarkMode),
                    contentPadding: const EdgeInsets.all(16),
                    leading: CircleAvatar(
                      backgroundColor: AppColors.primary.withValues(alpha: 0.1),
                      child: const Icon(Icons.receipt_long, color: AppColors.primary),
                    ),
                    title: Text(
                      '${payroll['month_name'] ?? '-'} ${payroll['year'] ?? ''}',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                    subtitle: Text(
                      '${payroll['document_number'] ?? '-'}\nTZS ${_formatMoney(payroll['payroll_amount'])}',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    trailing: PopupMenuButton<String>(
                      onSelected: (value) {
                        if (value == 'view') {
                          _showPayrollSheet(context, payroll, isDarkMode);
                        } else if (value == 'edit') {
                          _openForm(context, ref, payroll: payroll);
                        } else if (value == 'delete') {
                          _deletePayroll(context, ref, payroll);
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

  Future<void> _openForm(BuildContext context, WidgetRef ref, {Map<String, dynamic>? payroll}) async {
    final refs = await ref.read(_payrollAdminRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.9,
        child: _PayrollAdminFormSheet(refs: refs, payroll: payroll),
      ),
    );
    if (result == true) ref.invalidate(_payrollAdminProvider);
  }

  Future<void> _deletePayroll(BuildContext context, WidgetRef ref, Map<String, dynamic> payroll) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        scrollable: true,
        title: Text(isSwahili ? 'Futa Payroll' : 'Delete Payroll'),
        content: Text(
          isSwahili
              ? 'Je, unataka kufuta payroll ya ${payroll['month_name']} ${payroll['year']}?'
              : 'Delete payroll for ${payroll['month_name']} ${payroll['year']}?',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(dialogContext, false), child: Text(isSwahili ? 'Ghairi' : 'Cancel')),
          TextButton(onPressed: () => Navigator.pop(dialogContext, true), child: Text(isSwahili ? 'Futa' : 'Delete')),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).delete('/payroll-administration/${payroll['id']}');
      ref.invalidate(_payrollAdminProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Payroll imefutwa' : 'Payroll deleted'),
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

void _showPayrollSheet(BuildContext context, Map<String, dynamic> payroll, bool isDarkMode) {
  showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.64,
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
                      '${payroll['month_name'] ?? '-'} ${payroll['year'] ?? ''}',
                      style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 16),
                    _PayrollDetailRow('Document Number', payroll['document_number']?.toString() ?? '-'),
                    _PayrollDetailRow('Payroll Number', payroll['payroll_number']?.toString() ?? '-'),
                    _PayrollDetailRow('Status', payroll['status']?.toString() ?? '-'),
                    _PayrollDetailRow('Submitted Date', _formatDate(payroll['submitted_date']?.toString())),
                    _PayrollDetailRow('Payroll Amount', 'TZS ${_formatMoney(payroll['payroll_amount'])}'),
                    _PayrollDetailRow('Created By', payroll['created_by_name']?.toString() ?? '-'),
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

class _PayrollAdminFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? payroll;

  const _PayrollAdminFormSheet({
    required this.refs,
    this.payroll,
  });

  @override
  ConsumerState<_PayrollAdminFormSheet> createState() => _PayrollAdminFormSheetState();
}

class _PayrollAdminFormSheetState extends ConsumerState<_PayrollAdminFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _documentNumberController =
      TextEditingController(text: widget.payroll?['document_number']?.toString() ?? '');
  late final TextEditingController _payrollNumberController =
      TextEditingController(text: widget.payroll?['payroll_number']?.toString() ?? '');
  late final TextEditingController _yearController =
      TextEditingController(text: widget.payroll?['year']?.toString() ?? DateTime.now().year.toString());
  late final TextEditingController _submittedDateController =
      TextEditingController(text: _dateValue(widget.payroll?['submitted_date']?.toString()));

  int? _month;
  String _status = 'CREATED';
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _month = _toNullableInt(widget.payroll?['month']) ?? DateTime.now().month;
    _status = widget.payroll?['status']?.toString() ?? 'CREATED';
  }

  @override
  void dispose() {
    _documentNumberController.dispose();
    _payrollNumberController.dispose();
    _yearController.dispose();
    _submittedDateController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final statuses = _toNameList(widget.refs['statuses']);
    final months = _toMaps(widget.refs['months']);

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
                          widget.payroll == null
                              ? (isSwahili ? 'Payroll Mpya' : 'New Payroll')
                              : (isSwahili ? 'Hariri Payroll' : 'Edit Payroll'),
                          textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                        ),
                        const SizedBox(height: 20),
                        _input(_documentNumberController, 'Document Number *', isDarkMode),
                        const SizedBox(height: 12),
                        _input(_payrollNumberController, 'Payroll Number *', isDarkMode),
                        const SizedBox(height: 12),
                        _dropdownMonth(
                          isDarkMode: isDarkMode,
                          items: months,
                        ),
                        const SizedBox(height: 12),
                        _input(_yearController, 'Year *', isDarkMode, keyboardType: TextInputType.number),
                        const SizedBox(height: 12),
                        _dropdownStatus(
                          isDarkMode: isDarkMode,
                          items: statuses,
                        ),
                        const SizedBox(height: 12),
                        _dateInput(context, isDarkMode),
                        const SizedBox(height: 20),
                        ElevatedButton(
                          onPressed: _saving ? null : _submit,
                          child: _saving
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : Text(widget.payroll == null ? (isSwahili ? 'Hifadhi' : 'Save') : (isSwahili ? 'Sasisha' : 'Update')),
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
    TextInputType? keyboardType,
  }) {
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      validator: (value) => value == null || value.trim().isEmpty ? 'Required' : null,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
    );
  }

  Widget _dropdownMonth({
    required bool isDarkMode,
    required List<Map<String, dynamic>> items,
  }) {
    return DropdownButtonFormField<int>(
      isExpanded: true,
      value: items.any((item) => _toInt(item['id']) == _month) ? _month : null,
      validator: (selected) => selected == null ? 'Required' : null,
      decoration: InputDecoration(
        labelText: 'Month *',
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
      items: items
          .map((item) => DropdownMenuItem<int>(
                value: _toInt(item['id']),
                child: Text(item['name']?.toString() ?? '-', overflow: TextOverflow.ellipsis),
              ))
          .toList(),
      onChanged: (value) => setState(() => _month = value),
    );
  }

  Widget _dropdownStatus({
    required bool isDarkMode,
    required List<String> items,
  }) {
    return DropdownButtonFormField<String>(
      isExpanded: true,
      value: items.contains(_status) ? _status : (items.isNotEmpty ? items.first : null),
      decoration: InputDecoration(
        labelText: 'Status *',
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
      items: items
          .map((item) => DropdownMenuItem<String>(
                value: item,
                child: Text(item, overflow: TextOverflow.ellipsis),
              ))
          .toList(),
      onChanged: (value) => setState(() => _status = value ?? 'CREATED'),
    );
  }

  Widget _dateInput(BuildContext context, bool isDarkMode) {
    return TextFormField(
      controller: _submittedDateController,
      readOnly: true,
      validator: (value) => value == null || value.trim().isEmpty ? 'Required' : null,
      decoration: InputDecoration(
        labelText: 'Submitted Date *',
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
        suffixIcon: const Icon(Icons.calendar_today),
      ),
      onTap: () async {
        final initialDate = DateTime.tryParse(_submittedDateController.text) ?? DateTime.now();
        final picked = await showDatePicker(
          context: context,
          initialDate: initialDate,
          firstDate: DateTime(2000),
          lastDate: DateTime(2100),
        );
        if (picked != null) {
          _submittedDateController.text = DateFormat('yyyy-MM-dd').format(picked);
        }
      },
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final data = {
        'document_number': _documentNumberController.text.trim(),
        'payroll_number': _payrollNumberController.text.trim(),
        'year': int.tryParse(_yearController.text.trim()),
        'month': _month,
        'status': _status,
        'submitted_date': _submittedDateController.text.trim(),
      };

      if (widget.payroll == null) {
        await api.post('/payroll-administration', data: data);
      } else {
        await api.put('/payroll-administration/${widget.payroll!['id']}', data: data);
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

class _PayrollDetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _PayrollDetailRow(this.label, this.value);

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

class _PayrollAdminErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _PayrollAdminErrorView({
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
        ),
        const SizedBox(height: 8),
        Text(message, textAlign: TextAlign.center),
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

String _formatMoney(dynamic value) {
  final amount = value is num ? value.toDouble() : double.tryParse('$value') ?? 0;
  return NumberFormat('#,##0.00', 'en').format(amount);
}

String _formatDate(String? date) {
  if (date == null || date.isEmpty) return 'N/A';
  try {
    return DateFormat('dd MMM yyyy').format(DateTime.parse(date));
  } catch (_) {
    return date;
  }
}

String _dateValue(String? raw) {
  if (raw == null || raw.isEmpty) return DateFormat('yyyy-MM-dd').format(DateTime.now());
  try {
    return DateFormat('yyyy-MM-dd').format(DateTime.parse(raw));
  } catch (_) {
    return raw;
  }
}

List<Map<String, dynamic>> _toMaps(dynamic value) {
  final list = value as List? ?? const [];
  return list.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
}

List<String> _toNameList(dynamic value) {
  return _toMaps(value).map((item) => item['name']?.toString() ?? '').where((item) => item.isNotEmpty).toList();
}

int _toInt(dynamic value) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value) ?? 0;
  return 0;
}

int? _toNullableInt(dynamic value) {
  final parsed = int.tryParse(value?.toString() ?? '');
  return parsed == null || parsed == 0 ? null : parsed;
}
