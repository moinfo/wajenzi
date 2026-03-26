import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/services/external_launcher_service.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _statutoryPaymentsProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/statutory-payments');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];
  return items
      .whereType<Map>()
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
});

final _statutoryPaymentRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/statutory-payments/reference-data');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

final _statutoryPaymentDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/statutory-payments/$id');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

class StatutoryPaymentsScreen extends ConsumerWidget {
  const StatutoryPaymentsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final paymentsAsync = ref.watch(_statutoryPaymentsProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Malipo ya Kisheria' : 'Statutory Payments'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _openForm(context, ref),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_statutoryPaymentsProvider),
        child: paymentsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _ErrorState(
            isSwahili: isSwahili,
            message: vatErrorMessage(error, isSwahili: isSwahili),
            onRetry: () => ref.invalidate(_statutoryPaymentsProvider),
          ),
          data: (payments) {
            if (payments.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(32),
                children: [
                  const SizedBox(height: 100),
                  Icon(
                    Icons.account_balance_wallet_outlined,
                    size: 56,
                    color: isDarkMode ? Colors.white24 : Colors.grey[300],
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili
                        ? 'Hakuna malipo ya kisheria'
                        : 'No statutory payments found',
                    textAlign: TextAlign.center,
                  ),
                ],
              );
            }

            return ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: payments.length + 1,
              itemBuilder: (context, index) {
                if (index == payments.length) return const SizedBox(height: 80);
                final payment = payments[index];
                final id = _toInt(payment['id']);
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: ListTile(
                    isThreeLine: true,
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 10,
                    ),
                    leading: CircleAvatar(
                      backgroundColor: AppColors.primary.withValues(alpha: 0.12),
                      child: const Icon(
                        Icons.account_balance_wallet_outlined,
                        color: AppColors.primary,
                      ),
                    ),
                    title: Text(
                      payment['sub_category_name']?.toString() ?? '-',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                    subtitle: Text(
                      '${payment['document_number'] ?? '-'}\n${vatMoney(payment['amount'])} | ${payment['due_date'] ?? '-'} | ${payment['status'] ?? '-'}',
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    trailing: PopupMenuButton<String>(
                      onSelected: (value) {
                        if (value == 'view') {
                          _showDetails(context, ref, id);
                        } else if (value == 'edit') {
                          _openForm(context, ref, payment: payment);
                        } else if (value == 'delete') {
                          _deletePayment(context, ref, payment);
                        }
                      },
                      itemBuilder: (_) => [
                        PopupMenuItem(
                          value: 'view',
                          child: Text(isSwahili ? 'Tazama' : 'View'),
                        ),
                        PopupMenuItem(
                          value: 'edit',
                          child: Text(isSwahili ? 'Hariri' : 'Edit'),
                        ),
                        PopupMenuItem(
                          value: 'delete',
                          child: Text(isSwahili ? 'Futa' : 'Delete'),
                        ),
                      ],
                    ),
                    onTap: () => _showDetails(context, ref, id),
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

Future<void> _openForm(
  BuildContext context,
  WidgetRef ref, {
  Map<String, dynamic>? payment,
}) async {
  final refs = await ref.read(_statutoryPaymentRefsProvider.future);
  var initialPayment = payment;
  final id = _toInt(payment?['id']);
  if (id > 0 && payment?['description'] == null) {
    initialPayment = await ref.read(_statutoryPaymentDetailProvider(id).future);
  }
  if (!context.mounted) return;

  final result = await showModalBottomSheet<bool>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.9,
      child: _FormSheet(refs: refs, payment: initialPayment),
    ),
  );

  if (result == true) {
    ref.invalidate(_statutoryPaymentsProvider);
    ref.invalidate(_statutoryPaymentRefsProvider);
    if (id > 0) {
      ref.invalidate(_statutoryPaymentDetailProvider(id));
    }
  }
}

Future<void> _deletePayment(
  BuildContext context,
  WidgetRef ref,
  Map<String, dynamic> payment,
) async {
  final isSwahili = ref.read(isSwahiliProvider);
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (dialogContext) => AlertDialog(
      scrollable: true,
      title: Text(
        isSwahili ? 'Futa Malipo ya Kisheria' : 'Delete Statutory Payment',
      ),
      content: Text(
        isSwahili ? 'Je, unataka kufuta rekodi hii?' : 'Delete this record?',
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, false),
          child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
        ),
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, true),
          child: Text(isSwahili ? 'Futa' : 'Delete'),
        ),
      ],
    ),
  );
  if (confirmed != true) return;

  try {
    await ref
        .read(apiClientProvider)
        .delete('/statutory-payments/${payment['id']}');
    ref.invalidate(_statutoryPaymentsProvider);
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            isSwahili
                ? 'Malipo ya kisheria yamefutwa'
                : 'Statutory payment deleted',
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

void _showDetails(BuildContext context, WidgetRef ref, int id) {
  showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.72,
      child: Consumer(
        builder: (context, ref, _) {
          final itemAsync = ref.watch(_statutoryPaymentDetailProvider(id));
          final isDarkMode = ref.watch(isDarkModeProvider);
          final isSwahili = ref.watch(isSwahiliProvider);
          return Container(
            decoration: BoxDecoration(
              color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
              borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
            ),
            child: SafeArea(
              top: false,
              child: itemAsync.when(
                loading: () => const _BottomLoading(),
                error: (error, _) => _ErrorState(
                  isSwahili: isSwahili,
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  onRetry: () => ref.invalidate(_statutoryPaymentDetailProvider(id)),
                ),
                data: (payment) => Column(
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
                            payment['document_number']?.toString() ?? '-',
                            style: const TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                          const SizedBox(height: 16),
                          _detailLine('Sub Category', payment['sub_category_name']),
                          _detailLine('Amount', vatMoney(payment['amount'])),
                          _detailLine('Issue Date', payment['issue_date']),
                          _detailLine('Due Date', payment['due_date']),
                          _detailLine('Billing Cycle', payment['billing_cycle_name']),
                          _detailLine('Control Number', payment['control_number']),
                          _detailLine('Status', payment['status']),
                          _detailLine('Description', payment['description']),
                          if ((payment['file_url']?.toString() ?? '').isNotEmpty) ...[
                            const SizedBox(height: 8),
                            OutlinedButton.icon(
                              onPressed: () async {
                                final uri =
                                    Uri.tryParse(payment['file_url']!.toString());
                                final opened = uri != null &&
                                    await ExternalLauncherService.openUri(uri);
                                if (!context.mounted || opened) return;
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(
                                    content: Text('Unable to open attachment'),
                                    backgroundColor: AppColors.error,
                                  ),
                                );
                              },
                              icon: const Icon(Icons.attach_file),
                              label: Text(
                                isSwahili ? 'Fungua Faili' : 'Open Attachment',
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    ),
  );
}

class _FormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? payment;

  const _FormSheet({required this.refs, this.payment});

  @override
  ConsumerState<_FormSheet> createState() => _FormSheetState();
}

class _FormSheetState extends ConsumerState<_FormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _issueDateController;
  late final TextEditingController _dueDateController;
  late final TextEditingController _amountController;
  late final TextEditingController _descriptionController;
  late final TextEditingController _controlNumberController;

  int? _subCategoryId;
  String? _billingCycleLabel;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    final payment = widget.payment;
    _subCategoryId = _toNullableInt(payment?['sub_category_id']);
    _billingCycleLabel = payment?['billing_cycle_name']?.toString();
    _issueDateController =
        TextEditingController(text: _dateText(payment?['issue_date']));
    _dueDateController = TextEditingController(text: _dateText(payment?['due_date']));
    _amountController =
        TextEditingController(text: _numberText(payment?['amount']));
    _descriptionController =
        TextEditingController(text: payment?['description']?.toString() ?? '');
    _controlNumberController = TextEditingController(
      text: payment?['control_number']?.toString() ?? '',
    );
  }

  @override
  void dispose() {
    _issueDateController.dispose();
    _dueDateController.dispose();
    _amountController.dispose();
    _descriptionController.dispose();
    _controlNumberController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isDarkMode = ref.watch(isDarkModeProvider);
    final subCategories = (widget.refs['sub_categories'] as List? ?? const [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();

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
              child: Form(
                key: _formKey,
                child: ListView(
                  padding: EdgeInsets.fromLTRB(
                    20,
                    16,
                    20,
                    MediaQuery.of(context).viewInsets.bottom + 28,
                  ),
                  children: [
                    Text(
                      widget.payment == null
                          ? 'New Statutory Payment'
                          : 'Edit Statutory Payment',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 18),
                    _dropdownField(
                      label: 'Sub Category *',
                      isDarkMode: isDarkMode,
                      value: subCategories.any(
                        (item) => _toNullableInt(item['id']) == _subCategoryId,
                      )
                          ? _subCategoryId
                          : null,
                      items: subCategories
                          .map(
                            (item) => DropdownMenuItem<int?>(
                              value: _toNullableInt(item['id']),
                              child: Text(
                                item['name']?.toString() ?? '-',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: (value) {
                        setState(() => _subCategoryId = value);
                        final selected = subCategories.cast<Map<String, dynamic>?>().firstWhere(
                              (item) => _toNullableInt(item?['id']) == value,
                              orElse: () => null,
                            );
                        if (selected != null) {
                          _amountController.text = _numberText(selected['price']);
                          _billingCycleLabel =
                              selected['billing_cycle_name']?.toString() ?? '-';
                          _recalculateDueDate(_toInt(selected['billing_cycle']));
                        }
                      },
                    ),
                    if ((_billingCycleLabel ?? '').isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Text(
                        'Billing Cycle: ${_billingCycleLabel ?? '-'}',
                        style: const TextStyle(
                          color: AppColors.textSecondary,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                    const SizedBox(height: 12),
                    _dateField(
                      context,
                      controller: _issueDateController,
                      label: 'Issue Date *',
                      isDarkMode: isDarkMode,
                      onChanged: () {
                        final selected = subCategories.cast<Map<String, dynamic>?>().firstWhere(
                              (item) =>
                                  _toNullableInt(item?['id']) == _subCategoryId,
                              orElse: () => null,
                            );
                        if (selected != null) {
                          _recalculateDueDate(_toInt(selected['billing_cycle']));
                        }
                      },
                    ),
                    const SizedBox(height: 12),
                    _dateField(
                      context,
                      controller: _dueDateController,
                      label: 'Due Date *',
                      isDarkMode: isDarkMode,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _amountController,
                      label: 'Amount *',
                      isDarkMode: isDarkMode,
                      keyboardType:
                          const TextInputType.numberWithOptions(decimal: true),
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _descriptionController,
                      label: 'Description *',
                      isDarkMode: isDarkMode,
                      maxLines: 4,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _controlNumberController,
                      label: 'Control Number',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                      keyboardType: TextInputType.number,
                    ),
                    const SizedBox(height: 18),
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
                          : Text(widget.payment == null ? 'Save' : 'Update'),
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

  void _recalculateDueDate(int billingCycle) {
    final issueDate = DateTime.tryParse(_issueDateController.text);
    if (issueDate == null) return;
    final dueDate = DateTime(
      issueDate.year,
      issueDate.month + billingCycle,
      issueDate.day,
    );
    _dueDateController.text = dueDate.toIso8601String().split('T').first;
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate() || _subCategoryId == null) return;

    setState(() => _saving = true);
    final payload = <String, dynamic>{
      'sub_category_id': _subCategoryId,
      'issue_date': _issueDateController.text.trim(),
      'due_date': _dueDateController.text.trim(),
      'amount': _toDouble(_amountController.text),
      'description': _descriptionController.text.trim(),
      'control_number': _blankToNull(_controlNumberController.text),
    };

    try {
      final api = ref.read(apiClientProvider);
      final id = _toInt(widget.payment?['id']);
      if (id > 0) {
        await api.put('/statutory-payments/$id', data: payload);
      } else {
        await api.post('/statutory-payments', data: payload);
      }
      if (!mounted) return;
      Navigator.of(context).pop(true);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            id > 0
                ? 'Statutory payment updated'
                : 'Statutory payment created',
          ),
          backgroundColor: AppColors.success,
        ),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(vatErrorMessage(error, isSwahili: false)),
          backgroundColor: AppColors.error,
        ),
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}

class _ErrorState extends StatelessWidget {
  final bool isSwahili;
  final String message;
  final VoidCallback onRetry;

  const _ErrorState({
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
            const Icon(Icons.error_outline, size: 52, color: AppColors.error),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            OutlinedButton.icon(
              onPressed: onRetry,
              icon: const Icon(Icons.refresh),
              label: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
            ),
          ],
        ),
      ),
    );
  }
}

class _BottomLoading extends StatelessWidget {
  const _BottomLoading();

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        const SizedBox(height: 12),
        Container(
          width: 44,
          height: 5,
          decoration: BoxDecoration(
            color: Colors.white24,
            borderRadius: BorderRadius.circular(999),
          ),
        ),
        const Expanded(child: Center(child: CircularProgressIndicator())),
      ],
    );
  }
}

Widget _textField({
  required TextEditingController controller,
  required String label,
  required bool isDarkMode,
  bool isRequired = true,
  int maxLines = 1,
  TextInputType? keyboardType,
}) {
  return TextFormField(
    controller: controller,
    maxLines: maxLines,
    keyboardType: keyboardType,
    decoration: InputDecoration(
      labelText: label,
      alignLabelWithHint: maxLines > 1,
      filled: true,
      fillColor: isDarkMode
          ? Colors.white.withValues(alpha: 0.05)
          : Colors.grey.withValues(alpha: 0.08),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide.none,
      ),
    ),
    validator: (value) {
      if (!isRequired) return null;
      if ((value ?? '').trim().isEmpty) return 'Required';
      return null;
    },
  );
}

Widget _dropdownField({
  required String label,
  required bool isDarkMode,
  required int? value,
  required List<DropdownMenuItem<int?>> items,
  required ValueChanged<int?> onChanged,
}) {
  return DropdownButtonFormField<int?>(
    value: value,
    items: items,
    isExpanded: true,
    decoration: InputDecoration(
      labelText: label,
      filled: true,
      fillColor: isDarkMode
          ? Colors.white.withValues(alpha: 0.05)
          : Colors.grey.withValues(alpha: 0.08),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide.none,
      ),
    ),
    validator: (value) => value == null ? 'Required' : null,
    onChanged: onChanged,
  );
}

Widget _dateField(
  BuildContext context, {
  required TextEditingController controller,
  required String label,
  required bool isDarkMode,
  VoidCallback? onChanged,
}) {
  return TextFormField(
    controller: controller,
    readOnly: true,
    decoration: InputDecoration(
      labelText: label,
      suffixIcon: const Icon(Icons.calendar_today_outlined),
      filled: true,
      fillColor: isDarkMode
          ? Colors.white.withValues(alpha: 0.05)
          : Colors.grey.withValues(alpha: 0.08),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide.none,
      ),
    ),
    validator: (value) => (value ?? '').trim().isEmpty ? 'Required' : null,
    onTap: () async {
      final initialDate = DateTime.tryParse(controller.text) ?? DateTime.now();
      final picked = await showDatePicker(
        context: context,
        initialDate: initialDate,
        firstDate: DateTime(2020),
        lastDate: DateTime(2100),
      );
      if (picked != null) {
        controller.text = picked.toIso8601String().split('T').first;
        onChanged?.call();
      }
    },
  );
}

Widget _detailLine(String label, dynamic value) {
  final text = value?.toString().trim() ?? '';
  return Padding(
    padding: const EdgeInsets.only(bottom: 10),
    child: RichText(
      text: TextSpan(
        style: const TextStyle(fontSize: 13, color: AppColors.textPrimary),
        children: [
          TextSpan(
            text: '$label: ',
            style: const TextStyle(fontWeight: FontWeight.w700),
          ),
          TextSpan(text: text.isEmpty ? '-' : text),
        ],
      ),
    ),
  );
}

String _dateText(dynamic value) {
  final text = value?.toString() ?? '';
  return text.isEmpty ? '' : text.split('T').first;
}

String _numberText(dynamic value) {
  if (value == null) return '';
  final amount = _toDouble(value);
  if (amount == amount.truncateToDouble()) {
    return amount.toInt().toString();
  }
  return amount.toStringAsFixed(2);
}

double _toDouble(dynamic value) {
  if (value == null) return 0;
  if (value is num) return value.toDouble();
  return double.tryParse(value.toString().replaceAll(',', '')) ?? 0;
}

int _toInt(dynamic value) {
  if (value == null) return 0;
  if (value is int) return value;
  return int.tryParse(value.toString()) ?? 0;
}

int? _toNullableInt(dynamic value) {
  final parsed = _toInt(value);
  return parsed <= 0 ? null : parsed;
}

String? _blankToNull(String? value) {
  final text = value?.trim() ?? '';
  return text.isEmpty ? null : text;
}
