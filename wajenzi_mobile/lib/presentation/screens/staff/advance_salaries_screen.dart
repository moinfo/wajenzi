import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _advanceSalariesProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/advance-salaries');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];
  return items.whereType<Map>().map((item) => Map<String, dynamic>.from(item)).toList();
});

final _advanceSalaryRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/advance-salaries/reference-data');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

class AdvanceSalariesScreen extends ConsumerWidget {
  const AdvanceSalariesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final itemsAsync = ref.watch(_advanceSalariesProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Mishahara ya Mapema' : 'Advance Salaries'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_advanceSalariesProvider),
        child: itemsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _AdvanceSalaryErrorView(
            message: vatErrorMessage(error, isSwahili: isSwahili),
            isSwahili: isSwahili,
            onRetry: () => ref.invalidate(_advanceSalariesProvider),
          ),
          data: (items) {
            if (items.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(Icons.payments_outlined, size: 56, color: isDarkMode ? Colors.white24 : Colors.grey[300]),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hakuna advance salaries' : 'No advance salaries found',
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
                final canMutate = !_isLockedStatus(item['status']?.toString());
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    onTap: () => _showAdvanceSalarySheet(context, item, isDarkMode),
                    contentPadding: const EdgeInsets.all(16),
                    leading: CircleAvatar(
                      backgroundColor: AppColors.primary.withValues(alpha: 0.1),
                      child: const Icon(Icons.request_quote, color: AppColors.primary),
                    ),
                    title: Text(
                      item['staff_name']?.toString() ?? '-',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                    subtitle: Text(
                      '${_formatDate(item['date']?.toString())} • ${item['status'] ?? '-'}\nTZS ${_formatMoney(item['amount'])}',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    trailing: PopupMenuButton<String>(
                      onSelected: (value) {
                        if (value == 'view') {
                          _showAdvanceSalarySheet(context, item, isDarkMode);
                        } else if (value == 'edit') {
                          _openForm(context, ref, item: item);
                        } else if (value == 'delete') {
                          _deleteItem(context, ref, item);
                        }
                      },
                      itemBuilder: (_) => [
                        PopupMenuItem(value: 'view', child: Text(isSwahili ? 'Tazama' : 'View')),
                        if (canMutate) PopupMenuItem(value: 'edit', child: Text(isSwahili ? 'Hariri' : 'Edit')),
                        if (canMutate) PopupMenuItem(value: 'delete', child: Text(isSwahili ? 'Futa' : 'Delete')),
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

  Future<void> _openForm(BuildContext context, WidgetRef ref, {Map<String, dynamic>? item}) async {
    final refs = await ref.read(_advanceSalaryRefsProvider.future);
    final result = await showModalBottomSheet<bool>(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (_) => FractionallySizedBox(
        heightFactor: 0.88,
        child: _AdvanceSalaryFormSheet(refs: refs, item: item),
      ),
    );
    if (result == true) ref.invalidate(_advanceSalariesProvider);
  }

  Future<void> _deleteItem(BuildContext context, WidgetRef ref, Map<String, dynamic> item) async {
    final isSwahili = ref.read(isSwahiliProvider);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        scrollable: true,
        title: Text(isSwahili ? 'Futa Advance Salary' : 'Delete Advance Salary'),
        content: Text(
          isSwahili
              ? 'Je, unataka kufuta advance salary ya ${item['staff_name']}?'
              : 'Delete advance salary for ${item['staff_name']}?',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(dialogContext, false), child: Text(isSwahili ? 'Ghairi' : 'Cancel')),
          TextButton(onPressed: () => Navigator.pop(dialogContext, true), child: Text(isSwahili ? 'Futa' : 'Delete')),
        ],
      ),
    );
    if (confirmed != true) return;

    try {
      await ref.read(apiClientProvider).delete('/advance-salaries/${item['id']}');
      ref.invalidate(_advanceSalariesProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Advance salary imefutwa' : 'Advance salary deleted'),
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

void _showAdvanceSalarySheet(BuildContext context, Map<String, dynamic> item, bool isDarkMode) {
  showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.62,
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
                      item['staff_name']?.toString() ?? 'Advance Salary',
                      style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(height: 16),
                    _AdvanceSalaryDetailRow('Document Number', item['document_number']?.toString() ?? '-'),
                    _AdvanceSalaryDetailRow('Date', _formatDate(item['date']?.toString())),
                    _AdvanceSalaryDetailRow('Amount', 'TZS ${_formatMoney(item['amount'])}'),
                    _AdvanceSalaryDetailRow('Status', item['status']?.toString() ?? '-'),
                    _AdvanceSalaryDetailRow(
                      'Description',
                      item['description']?.toString().trim().isEmpty == true ? '-' : item['description']?.toString() ?? '-',
                    ),
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

class _AdvanceSalaryFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? item;

  const _AdvanceSalaryFormSheet({
    required this.refs,
    this.item,
  });

  @override
  ConsumerState<_AdvanceSalaryFormSheet> createState() => _AdvanceSalaryFormSheetState();
}

class _AdvanceSalaryFormSheetState extends ConsumerState<_AdvanceSalaryFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _amountController =
      TextEditingController(text: widget.item?['amount']?.toString() ?? '');
  late final TextEditingController _descriptionController =
      TextEditingController(text: widget.item?['description']?.toString() ?? '');
  late final TextEditingController _dateController =
      TextEditingController(text: _dateValue(widget.item?['date']?.toString()));

  int? _staffId;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _staffId = _toNullableInt(widget.item?['staff_id']);
  }

  @override
  void dispose() {
    _amountController.dispose();
    _descriptionController.dispose();
    _dateController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final staffs = _toMaps(widget.refs['staffs']);

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
                          widget.item == null
                              ? (isSwahili ? 'Advance Salary Mpya' : 'New Advance Salary')
                              : (isSwahili ? 'Hariri Advance Salary' : 'Edit Advance Salary'),
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
                        _input(
                          _amountController,
                          isSwahili ? 'Kiasi *' : 'Amount *',
                          isDarkMode,
                          keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        ),
                        const SizedBox(height: 12),
                        _dateInput(context, isDarkMode),
                        const SizedBox(height: 12),
                        _input(
                          _descriptionController,
                          isSwahili ? 'Maelezo' : 'Description',
                          isDarkMode,
                          required: false,
                          maxLines: 4,
                        ),
                        const SizedBox(height: 20),
                        ElevatedButton(
                          onPressed: _saving ? null : _submit,
                          child: _saving
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                                )
                              : Text(widget.item == null ? (isSwahili ? 'Hifadhi' : 'Save') : (isSwahili ? 'Sasisha' : 'Update')),
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
    bool required = true,
    int maxLines = 1,
    TextInputType? keyboardType,
  }) {
    return TextFormField(
      controller: controller,
      maxLines: maxLines,
      keyboardType: keyboardType,
      validator: required ? (value) => value == null || value.trim().isEmpty ? 'Required' : null : null,
      decoration: InputDecoration(
        labelText: label,
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
      ),
    );
  }

  Widget _dateInput(BuildContext context, bool isDarkMode) {
    return TextFormField(
      controller: _dateController,
      readOnly: true,
      validator: (value) => value == null || value.trim().isEmpty ? 'Required' : null,
      decoration: InputDecoration(
        labelText: 'Date *',
        filled: true,
        fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
        suffixIcon: const Icon(Icons.calendar_today),
      ),
      onTap: () async {
        final initialDate = DateTime.tryParse(_dateController.text) ?? DateTime.now();
        final picked = await showDatePicker(
          context: context,
          initialDate: initialDate,
          firstDate: DateTime(2000),
          lastDate: DateTime(2100),
        );
        if (picked != null) {
          _dateController.text = DateFormat('yyyy-MM-dd').format(picked);
        }
      },
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
      final amount = double.tryParse(_amountController.text.replaceAll(',', '').trim());
      if (amount == null || amount <= 0) {
        throw Exception('Invalid amount');
      }

      final data = {
        'staff_id': _staffId,
        'amount': amount,
        'date': _dateController.text.trim(),
        'description': _descriptionController.text.trim().isEmpty ? null : _descriptionController.text.trim(),
      };

      if (widget.item == null) {
        await api.post('/advance-salaries', data: data);
      } else {
        await api.put('/advance-salaries/${widget.item!['id']}', data: data);
      }

      if (mounted) Navigator.pop(context, true);
    } catch (error) {
      final isSwahili = ref.read(isSwahiliProvider);
      final message = error is Exception && error.toString() == 'Exception: Invalid amount'
          ? (isSwahili ? 'Weka kiasi sahihi' : 'Enter a valid amount')
          : vatErrorMessage(error, isSwahili: isSwahili);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(message),
            backgroundColor: AppColors.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

class _AdvanceSalaryDetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _AdvanceSalaryDetailRow(this.label, this.value);

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

class _AdvanceSalaryErrorView extends StatelessWidget {
  final String message;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _AdvanceSalaryErrorView({
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
        Text(isSwahili ? 'Hitilafu imetokea' : 'Something went wrong', textAlign: TextAlign.center),
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

bool _isLockedStatus(String? status) {
  final normalized = status?.toUpperCase() ?? '';
  return normalized == 'APPROVED' || normalized == 'PAID' || normalized == 'COMPLETED';
}

String _dateValue(String? raw) {
  if (raw == null || raw.isEmpty) return DateFormat('yyyy-MM-dd').format(DateTime.now());
  if (raw.length >= 10) return raw.substring(0, 10);
  return raw;
}

String _formatDate(String? raw) {
  if (raw == null || raw.isEmpty) return '-';
  final date = DateTime.tryParse(raw);
  if (date == null) return raw;
  return DateFormat('dd MMM yyyy').format(date);
}

String _formatMoney(dynamic value) {
  final amount = value is num ? value.toDouble() : double.tryParse(value?.toString() ?? '') ?? 0;
  return NumberFormat('#,##0.##').format(amount);
}
