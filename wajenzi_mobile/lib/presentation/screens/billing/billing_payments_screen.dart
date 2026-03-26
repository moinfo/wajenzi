import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _paymentListProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get(
    '/billing/payments',
    queryParameters: {'per_page': 100},
  );
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  final items = data['data'] as List? ?? const [];
  return items
      .whereType<Map>()
      .map((item) => Map<String, dynamic>.from(item))
      .toList();
});

final _paymentRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/billing/payments/reference-data');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

final _paymentDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/billing/payments/$id');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

class BillingPaymentsScreen extends ConsumerWidget {
  const BillingPaymentsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final itemsAsync = ref.watch(_paymentListProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Malipo ya Billing' : 'Billing Payments'),
        actions: [
          IconButton(
            tooltip: isSwahili ? 'Ongeza' : 'Add',
            onPressed: () => _openPaymentForm(context, ref),
            icon: const Icon(Icons.add),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_paymentListProvider),
        child: itemsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _PaymentErrorView(
            isSwahili: isSwahili,
            message: vatErrorMessage(error, isSwahili: isSwahili),
            onRetry: () => ref.invalidate(_paymentListProvider),
          ),
          data: (items) {
            if (items.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(24),
                children: [
                  const SizedBox(height: 100),
                  Icon(
                    Icons.payments_outlined,
                    size: 60,
                    color: isDarkMode ? Colors.white24 : Colors.black12,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili ? 'Hakuna malipo bado' : 'No payments yet',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              );
            }

            return ListView.builder(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
              itemCount: items.length,
              itemBuilder: (context, index) {
                final item = items[index];
                final id = _toInt(item['id']);
                final document =
                    item['document'] as Map<String, dynamic>? ?? const {};
                final client =
                    item['client'] as Map<String, dynamic>? ?? const {};
                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: InkWell(
                    borderRadius: BorderRadius.circular(12),
                    onTap: id > 0 ? () => _showPaymentSheet(context, ref, id) : null,
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          CircleAvatar(
                            backgroundColor:
                                AppColors.success.withValues(alpha: 0.12),
                            child: const Icon(
                              Icons.payments,
                              color: AppColors.success,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _text(item['payment_number']),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  '${_text(document['document_number'])}\n${client['full_name']?.toString() ?? client['name']?.toString() ?? '-'}',
                                  maxLines: 2,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    color: AppColors.textSecondary,
                                  ),
                                ),
                                const SizedBox(height: 10),
                                Wrap(
                                  spacing: 10,
                                  runSpacing: 6,
                                  children: [
                                    _MiniChip(
                                      icon: Icons.calendar_today_outlined,
                                      label: _text(item['payment_date']),
                                    ),
                                    _MiniChip(
                                      icon: Icons.credit_card_outlined,
                                      label: _text(item['payment_method']),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 8),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text(
                                _money(item['amount']),
                                style: const TextStyle(
                                  color: AppColors.success,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              PopupMenuButton<String>(
                                onSelected: (value) {
                                  if (value == 'view') {
                                    _showPaymentSheet(context, ref, id);
                                  } else if (value == 'edit') {
                                    _openPaymentForm(context, ref, payment: item);
                                  } else if (value == 'delete') {
                                    _deletePayment(context, ref, item);
                                  }
                                },
                                itemBuilder: (context) => const [
                                  PopupMenuItem<String>(
                                    value: 'view',
                                    child: Text('View'),
                                  ),
                                  PopupMenuItem<String>(
                                    value: 'edit',
                                    child: Text('Edit'),
                                  ),
                                  PopupMenuItem<String>(
                                    value: 'delete',
                                    child: Text('Delete'),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ],
                      ),
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

Future<void> _openPaymentForm(
  BuildContext context,
  WidgetRef ref, {
  Map<String, dynamic>? payment,
}) async {
  final refs = await ref.read(_paymentRefsProvider.future);
  var initialPayment = payment;
  final paymentId = _toInt(payment?['id']);
  if (payment != null && paymentId > 0 && payment['document_id'] == null) {
    initialPayment = await ref.read(_paymentDetailProvider(paymentId).future);
  }
  if (!context.mounted) return;

  final result = await showModalBottomSheet<bool>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.94,
      child: _PaymentFormSheet(refs: refs, payment: initialPayment),
    ),
  );
  if (result == true) {
    ref.invalidate(_paymentListProvider);
    if (paymentId > 0) {
      ref.invalidate(_paymentDetailProvider(paymentId));
    }
  }
}

Future<void> _deletePayment(
  BuildContext context,
  WidgetRef ref,
  Map<String, dynamic> payment,
) async {
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (dialogContext) => AlertDialog(
      scrollable: true,
      title: const Text('Delete Payment'),
      content: Text('Delete ${_text(payment['payment_number'])}?'),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, false),
          child: const Text('Cancel'),
        ),
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, true),
          child: const Text('Delete'),
        ),
      ],
    ),
  );
  if (confirmed != true) return;

  try {
    await ref.read(apiClientProvider).delete('/billing/payments/${payment['id']}');
    ref.invalidate(_paymentListProvider);
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          backgroundColor: AppColors.success,
          content: Text('Payment deleted'),
        ),
      );
    }
  } catch (error) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.error,
          content: Text(vatErrorMessage(error, isSwahili: false)),
        ),
      );
    }
  }
}

void _showPaymentSheet(BuildContext context, WidgetRef ref, int id) {
  if (id <= 0) return;
  showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.84,
      child: Consumer(
        builder: (context, ref, _) {
          final detailAsync = ref.watch(_paymentDetailProvider(id));
          final isDarkMode = ref.watch(isDarkModeProvider);
          return Container(
            decoration: BoxDecoration(
              color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
              borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
            ),
            child: SafeArea(
              top: false,
              child: detailAsync.when(
                loading: () => const _BottomLoading(),
                error: (error, _) => _PaymentErrorView(
                  isSwahili: false,
                  message: vatErrorMessage(error, isSwahili: false),
                  onRetry: () => ref.invalidate(_paymentDetailProvider(id)),
                ),
                data: (payment) {
                  final document =
                      payment['document'] as Map<String, dynamic>? ?? const {};
                  final client =
                      payment['client'] as Map<String, dynamic>? ?? const {};
                  return Column(
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
                              _text(payment['payment_number']),
                              style: const TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const SizedBox(height: 18),
                            _DetailRow('Amount', _money(payment['amount'])),
                            _DetailRow('Date', _text(payment['payment_date'])),
                            _DetailRow('Method', _text(payment['payment_method'])),
                            _DetailRow('Status', _text(payment['status'])),
                            _DetailRow('Document', _text(document['document_number'])),
                            _DetailRow(
                              'Client',
                              client['full_name']?.toString() ??
                                  client['name']?.toString() ??
                                  '-',
                            ),
                            _DetailRow('Reference', _text(payment['reference_number'])),
                            _DetailRow('Bank', _text(payment['bank_name'])),
                            _DetailRow('Cheque', _text(payment['cheque_number'])),
                            _DetailRow('Transaction', _text(payment['transaction_id'])),
                            _DetailRow('Notes', _text(payment['notes'])),
                          ],
                        ),
                      ),
                    ],
                  );
                },
              ),
            ),
          );
        },
      ),
    ),
  );
}

class _PaymentFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? payment;

  const _PaymentFormSheet({
    required this.refs,
    this.payment,
  });

  @override
  ConsumerState<_PaymentFormSheet> createState() => _PaymentFormSheetState();
}

class _PaymentFormSheetState extends ConsumerState<_PaymentFormSheet> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _dateController =
      TextEditingController(text: _dateValue(widget.payment?['payment_date']));
  late final TextEditingController _amountController =
      TextEditingController(text: _numberText(widget.payment?['amount']));
  late final TextEditingController _referenceController =
      TextEditingController(text: widget.payment?['reference_number']?.toString() ?? '');
  late final TextEditingController _bankController =
      TextEditingController(text: widget.payment?['bank_name']?.toString() ?? '');
  late final TextEditingController _chequeController =
      TextEditingController(text: widget.payment?['cheque_number']?.toString() ?? '');
  late final TextEditingController _transactionController =
      TextEditingController(text: widget.payment?['transaction_id']?.toString() ?? '');
  late final TextEditingController _notesController =
      TextEditingController(text: widget.payment?['notes']?.toString() ?? '');

  int? _documentId;
  String? _paymentMethod;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _documentId = _toNullableInt(widget.payment?['document_id']);
    _paymentMethod = widget.payment?['payment_method']?.toString();
  }

  @override
  void dispose() {
    _dateController.dispose();
    _amountController.dispose();
    _referenceController.dispose();
    _bankController.dispose();
    _chequeController.dispose();
    _transactionController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isDarkMode = ref.watch(isDarkModeProvider);
    final documents = (widget.refs['documents'] as List? ?? const [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
    final methods = (widget.refs['payment_methods'] as List? ?? const [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
    final selectedDocument = documents.firstWhere(
      (doc) => _toNullableInt(doc['id']) == _documentId,
      orElse: () => const <String, dynamic>{},
    );

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
                      widget.payment == null ? 'New Payment' : 'Edit Payment',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 18),
                    _dropdownField(
                      label: 'Document *',
                      isDarkMode: isDarkMode,
                      value: documents.any((item) => _toNullableInt(item['id']) == _documentId)
                          ? _documentId
                          : null,
                      items: documents
                          .map(
                            (item) => DropdownMenuItem<int?>(
                              value: _toNullableInt(item['id']),
                              child: Text(
                                '${_text(item['document_number'])} - ${_text(item['client_name'])}',
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: (value) => setState(() => _documentId = value),
                    ),
                    if (selectedDocument.isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Text(
                        'Balance: ${_money(selectedDocument['balance_amount'])}',
                        style: const TextStyle(
                          color: AppColors.textSecondary,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                    const SizedBox(height: 12),
                    _dateField(
                      context,
                      controller: _dateController,
                      label: 'Payment Date *',
                      isDarkMode: isDarkMode,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _amountController,
                      label: 'Amount *',
                      isDarkMode: isDarkMode,
                      keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    ),
                    const SizedBox(height: 12),
                    _dropdownMethodField(
                      label: 'Payment Method *',
                      isDarkMode: isDarkMode,
                      value: methods.any((item) => item['id']?.toString() == _paymentMethod)
                          ? _paymentMethod
                          : null,
                      items: methods
                          .map(
                            (item) => DropdownMenuItem<String>(
                              value: item['id']?.toString(),
                              child: Text(_text(item['name'])),
                            ),
                          )
                          .toList(),
                      onChanged: (value) => setState(() => _paymentMethod = value),
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _referenceController,
                      label: 'Reference Number',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _bankController,
                      label: 'Bank Name',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _chequeController,
                      label: 'Cheque Number',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _transactionController,
                      label: 'Transaction ID',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    const SizedBox(height: 12),
                    _textField(
                      controller: _notesController,
                      label: 'Notes',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                      maxLines: 4,
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

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate() || _documentId == null || _paymentMethod == null) {
      return;
    }
    setState(() => _saving = true);
    final payload = <String, dynamic>{
      'document_id': _documentId,
      'payment_date': _dateController.text.trim(),
      'amount': _toDouble(_amountController.text),
      'payment_method': _paymentMethod,
      'reference_number': _blankToNull(_referenceController.text),
      'bank_name': _blankToNull(_bankController.text),
      'cheque_number': _blankToNull(_chequeController.text),
      'transaction_id': _blankToNull(_transactionController.text),
      'notes': _blankToNull(_notesController.text),
    };
    try {
      final api = ref.read(apiClientProvider);
      final id = _toInt(widget.payment?['id']);
      if (id > 0) {
        await api.put('/billing/payments/$id', data: payload);
      } else {
        await api.post('/billing/payments', data: payload);
      }
      if (!mounted) return;
      Navigator.of(context).pop(true);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.success,
          content: Text(id > 0 ? 'Payment updated' : 'Payment created'),
        ),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.error,
          content: Text(vatErrorMessage(error, isSwahili: false)),
        ),
      );
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
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(
              fontSize: 12,
              color: AppColors.textSecondary,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 4),
          Text(value),
        ],
      ),
    );
  }
}

class _MiniChip extends StatelessWidget {
  final IconData icon;
  final String label;

  const _MiniChip({required this.icon, required this.label});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.grey.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: AppColors.textSecondary),
          const SizedBox(width: 6),
          Text(label, style: const TextStyle(fontSize: 12)),
        ],
      ),
    );
  }
}

class _PaymentErrorView extends StatelessWidget {
  final bool isSwahili;
  final String message;
  final VoidCallback onRetry;

  const _PaymentErrorView({
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
      filled: true,
      fillColor: isDarkMode
          ? Colors.white.withValues(alpha: 0.05)
          : Colors.grey.withValues(alpha: 0.08),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide.none,
      ),
      alignLabelWithHint: maxLines > 1,
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

Widget _dropdownMethodField({
  required String label,
  required bool isDarkMode,
  required String? value,
  required List<DropdownMenuItem<String>> items,
  required ValueChanged<String?> onChanged,
}) {
  return DropdownButtonFormField<String>(
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
    validator: (value) => value == null || value.isEmpty ? 'Required' : null,
    onChanged: onChanged,
  );
}

Widget _dateField(
  BuildContext context, {
  required TextEditingController controller,
  required String label,
  required bool isDarkMode,
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
    validator: (value) =>
        (value ?? '').trim().isEmpty ? 'Required' : null,
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
      }
    },
  );
}

String _text(dynamic value) {
  final text = value?.toString().trim() ?? '';
  return text.isEmpty ? '-' : text;
}

String _money(dynamic value) {
  final amount = _toDouble(value);
  return 'TZS ${amount.toStringAsFixed(2)}';
}

String _dateValue(dynamic value) {
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
  return double.tryParse(value.toString()) ?? 0;
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
