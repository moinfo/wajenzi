import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _invoiceListProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get(
    '/billing/documents',
    queryParameters: {
      'document_type': 'invoice',
      'per_page': 100,
    },
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

final _invoiceRefsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/billing/reference-data');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

final _invoiceDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/billing/documents/$id');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

class BillingInvoicesScreen extends ConsumerWidget {
  const BillingInvoicesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final itemsAsync = ref.watch(_invoiceListProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        title: Text(isSwahili ? 'Invoices' : 'Billing Invoices'),
        actions: [
          IconButton(
            tooltip: isSwahili ? 'Ongeza' : 'Add',
            onPressed: () => _openInvoiceForm(context, ref),
            icon: const Icon(Icons.add),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: () async => ref.invalidate(_invoiceListProvider),
        child: itemsAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, _) => _QuotationErrorView(
            isSwahili: isSwahili,
            message: vatErrorMessage(error, isSwahili: isSwahili),
            onRetry: () => ref.invalidate(_invoiceListProvider),
          ),
          data: (items) {
            if (items.isEmpty) {
              return ListView(
                padding: const EdgeInsets.all(24),
                children: [
                  const SizedBox(height: 100),
                  Icon(
                    Icons.request_quote_outlined,
                    size: 60,
                    color: isDarkMode ? Colors.white24 : Colors.black12,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    isSwahili
                        ? 'Hakuna invoices bado'
                        : 'No invoices yet',
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    isSwahili
                        ? 'Bonyeza alama ya kuongeza kuunda invoice mpya.'
                        : 'Tap the add button to create an invoice.',
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: AppColors.textSecondary),
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
                final status = _text(item['status']);
                final isDraft = status.toLowerCase() == 'draft';
                final client =
                    item['client'] as Map<String, dynamic>? ?? const {};
                final project =
                    item['project'] as Map<String, dynamic>? ?? const {};

                return Card(
                  margin: const EdgeInsets.only(bottom: 12),
                  child: InkWell(
                    borderRadius: BorderRadius.circular(12),
                    onTap: id > 0
                        ? () => _showInvoiceSheet(context, ref, id)
                        : null,
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              CircleAvatar(
                                backgroundColor:
                                    AppColors.info.withValues(alpha: 0.12),
                                child: const Icon(
                                  Icons.request_quote,
                                  color: AppColors.info,
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      _text(item['document_number']),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(
                                        fontSize: 16,
                                        fontWeight: FontWeight.w700,
                                      ),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      client['full_name']?.toString() ??
                                          client['name']?.toString() ??
                                          '-',
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(
                                        color: AppColors.textSecondary,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              PopupMenuButton<String>(
                                onSelected: (value) {
                                  if (value == 'view') {
                                    _showInvoiceSheet(context, ref, id);
                                  } else if (value == 'edit') {
                                    _openInvoiceForm(
                                      context,
                                      ref,
                                      quotation: item,
                                    );
                                  } else if (value == 'delete') {
                                    _deleteInvoice(context, ref, item);
                                  }
                                },
                                itemBuilder: (context) {
                                  final menu = <PopupMenuEntry<String>>[
                                    PopupMenuItem<String>(
                                      value: 'view',
                                      child: Text(
                                        isSwahili ? 'Tazama' : 'View',
                                      ),
                                    ),
                                  ];
                                  if (isDraft) {
                                    menu.addAll([
                                      PopupMenuItem<String>(
                                        value: 'edit',
                                        child: Text(
                                          isSwahili ? 'Hariri' : 'Edit',
                                        ),
                                      ),
                                      PopupMenuItem<String>(
                                        value: 'delete',
                                        child: Text(
                                          isSwahili ? 'Futa' : 'Delete',
                                        ),
                                      ),
                                    ]);
                                  }
                                  return menu;
                                },
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            children: [
                              _StatusChip(label: status),
                              _InfoChip(
                                icon: Icons.calendar_today_outlined,
                                label: _text(item['issue_date']),
                              ),
                              if (_text(project['project_name']).isNotEmpty &&
                                  _text(project['project_name']) != '-')
                                _InfoChip(
                                  icon: Icons.apartment_outlined,
                                  label: _text(project['project_name']),
                                ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Wrap(
                            spacing: 12,
                            runSpacing: 8,
                            children: [
                              _MetricText(
                                label: isSwahili ? 'Jumla' : 'Total',
                                value: _money(item['total_amount']),
                              ),
                              _MetricText(
                                label: isSwahili ? 'Malipo' : 'Paid',
                                value: _money(item['paid_amount']),
                              ),
                              _MetricText(
                                label: isSwahili ? 'Salio' : 'Balance',
                                value: _money(item['balance_amount']),
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

Future<void> _openInvoiceForm(
  BuildContext context,
  WidgetRef ref, {
  Map<String, dynamic>? quotation,
}) async {
  final refs = await ref.read(_invoiceRefsProvider.future);
  var initialQuotation = quotation;
  final quotationId = _toInt(quotation?['id']);
  final hasItems = quotation?['items'] is List;

  if (quotation != null && quotationId > 0 && !hasItems) {
    initialQuotation = await ref.read(_invoiceDetailProvider(quotationId).future);
  }

  if (!context.mounted) return;

  final result = await showModalBottomSheet<bool>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.94,
      child: _QuotationFormSheet(
        refs: refs,
        quotation: initialQuotation,
      ),
    ),
  );

  if (result == true) {
    ref.invalidate(_invoiceListProvider);
    if (quotationId > 0) {
      ref.invalidate(_invoiceDetailProvider(quotationId));
    }
  }
}

Future<void> _deleteInvoice(
  BuildContext context,
  WidgetRef ref,
  Map<String, dynamic> quotation,
) async {
  final isSwahili = ref.read(isSwahiliProvider);
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (dialogContext) => AlertDialog(
      scrollable: true,
      title: Text(isSwahili ? 'Futa Invoice' : 'Delete Invoice'),
      content: Text(
        isSwahili
            ? 'Una uhakika unataka kufuta ${_text(quotation['document_number'])}?'
            : 'Are you sure you want to delete ${_text(quotation['document_number'])}?',
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
    await ref.read(apiClientProvider).delete(
          '/billing/documents/${quotation['id']}',
        );
    ref.invalidate(_invoiceListProvider);

    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.success,
          content: Text(
            isSwahili ? 'Invoice imefutwa' : 'Invoice deleted',
          ),
        ),
      );
    }
  } catch (error) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.error,
          content: Text(vatErrorMessage(error, isSwahili: isSwahili)),
        ),
      );
    }
  }
}

void _showInvoiceSheet(
  BuildContext context,
  WidgetRef ref,
  int id,
) {
  if (id <= 0) return;

  showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.88,
      child: Consumer(
        builder: (context, ref, _) {
          final detailAsync = ref.watch(_invoiceDetailProvider(id));
          final isSwahili = ref.watch(isSwahiliProvider);
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
              child: detailAsync.when(
                loading: () => const _BottomSheetLoading(),
                error: (error, _) => _QuotationErrorView(
                  isSwahili: isSwahili,
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  onRetry: () => ref.invalidate(_invoiceDetailProvider(id)),
                ),
                data: (detail) {
                  final client =
                      detail['client'] as Map<String, dynamic>? ?? const {};
                  final project =
                      detail['project'] as Map<String, dynamic>? ?? const {};
                  final items = (detail['items'] as List? ?? const [])
                      .whereType<Map>()
                      .map((item) => Map<String, dynamic>.from(item))
                      .toList();
                  final isDraft = _text(detail['status']).toLowerCase() == 'draft';

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
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        _text(detail['document_number']),
                                        style: const TextStyle(
                                          fontSize: 20,
                                          fontWeight: FontWeight.w700,
                                        ),
                                      ),
                                      const SizedBox(height: 8),
                                      Wrap(
                                        spacing: 8,
                                        runSpacing: 8,
                                        children: [
                                          _StatusChip(label: _text(detail['status'])),
                                          _InfoChip(
                                            icon: Icons.event_outlined,
                                            label: _text(detail['issue_date']),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                                if (isDraft)
                                  IconButton(
                                    tooltip: isSwahili ? 'Hariri' : 'Edit',
                                    onPressed: () async {
                                      Navigator.of(context).pop();
                                      await _openInvoiceForm(
                                        context,
                                        ref,
                                        quotation: detail,
                                      );
                                    },
                                    icon: const Icon(Icons.edit_outlined),
                                  ),
                              ],
                            ),
                            const SizedBox(height: 18),
                            _SectionCard(
                              title: isSwahili ? 'Maelezo' : 'Details',
                              children: [
                                _QuotationDetailRow(
                                  label: isSwahili ? 'Mteja' : 'Client',
                                  value: client['full_name']?.toString() ??
                                      client['name']?.toString() ??
                                      '-',
                                ),
                                _QuotationDetailRow(
                                  label: isSwahili ? 'Mradi' : 'Project',
                                  value: project['project_name']?.toString() ?? '-',
                                ),
                                _QuotationDetailRow(
                                  label: isSwahili ? 'Issue Date' : 'Issue Date',
                                  value: _text(detail['issue_date']),
                                ),
                                _QuotationDetailRow(
                                  label: isSwahili ? 'Due Date' : 'Due Date',
                                  value: _text(detail['due_date']),
                                ),
                                _QuotationDetailRow(
                                  label: isSwahili ? 'Valid Until' : 'Valid Until',
                                  value: _text(detail['valid_until_date']),
                                ),
                                _QuotationDetailRow(
                                  label: isSwahili ? 'Payment Terms' : 'Payment Terms',
                                  value: _text(detail['payment_terms']),
                                ),
                              ],
                            ),
                            const SizedBox(height: 14),
                            _SectionCard(
                              title: isSwahili ? 'Fedha' : 'Amounts',
                              children: [
                                _QuotationDetailRow(
                                  label: isSwahili ? 'Subtotal' : 'Subtotal',
                                  value: _money(detail['subtotal_amount']),
                                ),
                                _QuotationDetailRow(
                                  label: isSwahili ? 'Discount' : 'Discount',
                                  value: _money(detail['discount_amount']),
                                ),
                                _QuotationDetailRow(
                                  label: isSwahili ? 'Tax' : 'Tax',
                                  value: _money(detail['tax_amount']),
                                ),
                                _QuotationDetailRow(
                                  label: isSwahili ? 'Total' : 'Total',
                                  value: _money(detail['total_amount']),
                                ),
                              ],
                            ),
                            if (_text(detail['notes']).isNotEmpty &&
                                _text(detail['notes']) != '-') ...[
                              const SizedBox(height: 14),
                              _SectionCard(
                                title: isSwahili ? 'Notes' : 'Notes',
                                children: [
                                  Text(
                                    _text(detail['notes']),
                                    style: const TextStyle(height: 1.45),
                                  ),
                                ],
                              ),
                            ],
                            if (_text(detail['terms_conditions']).isNotEmpty &&
                                _text(detail['terms_conditions']) != '-') ...[
                              const SizedBox(height: 14),
                              _SectionCard(
                                title: isSwahili
                                    ? 'Sheria na Masharti'
                                    : 'Terms & Conditions',
                                children: [
                                  Text(
                                    _text(detail['terms_conditions']),
                                    style: const TextStyle(height: 1.45),
                                  ),
                                ],
                              ),
                            ],
                            if (items.isNotEmpty) ...[
                              const SizedBox(height: 14),
                              _SectionCard(
                                title: isSwahili ? 'Vitu' : 'Items',
                                children: items
                                    .map(
                                      (item) => Padding(
                                        padding: const EdgeInsets.only(bottom: 10),
                                        child: Container(
                                          width: double.infinity,
                                          padding: const EdgeInsets.all(12),
                                          decoration: BoxDecoration(
                                            color: Colors.grey.withValues(
                                              alpha: 0.08,
                                            ),
                                            borderRadius:
                                                BorderRadius.circular(12),
                                          ),
                                          child: Column(
                                            crossAxisAlignment:
                                                CrossAxisAlignment.start,
                                            children: [
                                              Text(
                                                _text(item['description']),
                                                style: const TextStyle(
                                                  fontWeight: FontWeight.w600,
                                                ),
                                              ),
                                              const SizedBox(height: 6),
                                              Text(
                                                '${_decimalText(item['quantity'])} ${_text(item['unit'])} x ${_money(item['unit_price'])}',
                                                style: const TextStyle(
                                                  color: AppColors.textSecondary,
                                                ),
                                              ),
                                              const SizedBox(height: 4),
                                              Text(
                                                '${isSwahili ? 'Total' : 'Total'}: ${_money(item['total_amount'])}',
                                                style: const TextStyle(
                                                  fontWeight: FontWeight.w600,
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ),
                                    )
                                    .toList(),
                              ),
                            ],
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

class _QuotationFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? quotation;

  const _QuotationFormSheet({
    required this.refs,
    this.quotation,
  });

  @override
  ConsumerState<_QuotationFormSheet> createState() => _QuotationFormSheetState();
}

class _QuotationFormSheetState extends ConsumerState<_QuotationFormSheet> {
  final _formKey = GlobalKey<FormState>();

  late final TextEditingController _issueDateController =
      TextEditingController(text: _dateValue(widget.quotation?['issue_date']));
  late final TextEditingController _dueDateController =
      TextEditingController(
        text: _dateValue(widget.quotation?['due_date']),
      );
  late final TextEditingController _validUntilController =
      TextEditingController(
        text: _dateValue(widget.quotation?['valid_until_date']),
      );
  late final TextEditingController _paymentTermsController =
      TextEditingController(
        text: widget.quotation?['payment_terms']?.toString() ?? '',
      );
  late final TextEditingController _notesController = TextEditingController(
    text: widget.quotation?['notes']?.toString() ?? '',
  );
  late final TextEditingController _termsController = TextEditingController(
    text: widget.quotation?['terms_conditions']?.toString() ?? '',
  );

  int? _clientId;
  int? _projectId;
  bool _saving = false;
  late final List<_QuotationItemState> _items;

  @override
  void initState() {
    super.initState();
    _clientId = _toNullableInt(widget.quotation?['client_id']);
    _projectId = _toNullableInt(widget.quotation?['project_id']);
    final sourceItems = (widget.quotation?['items'] as List? ?? const [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
    _items = sourceItems.isNotEmpty
        ? sourceItems.map(_QuotationItemState.fromMap).toList()
        : [_QuotationItemState.empty()];
  }

  @override
  void dispose() {
    _issueDateController.dispose();
    _dueDateController.dispose();
    _validUntilController.dispose();
    _paymentTermsController.dispose();
    _notesController.dispose();
    _termsController.dispose();
    for (final item in _items) {
      item.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final clients = (widget.refs['clients'] as List? ?? const [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
    final projects = (widget.refs['projects'] as List? ?? const [])
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
                      widget.quotation == null
                          ? (isSwahili ? 'Invoice Mpya' : 'New Invoice')
                          : (isSwahili ? 'Hariri Invoice' : 'Edit Invoice'),
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 18),
                    _buildDropdownField(
                      label: isSwahili ? 'Mteja *' : 'Client *',
                      isDarkMode: isDarkMode,
                      value: clients.any((item) => _toNullableInt(item['id']) == _clientId)
                          ? _clientId
                          : null,
                      items: clients
                          .map(
                            (item) => DropdownMenuItem<int?>(
                              value: _toNullableInt(item['id']),
                              child: Text(
                                _text(item['name']),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: (value) => setState(() => _clientId = value),
                    ),
                    const SizedBox(height: 12),
                    _buildDropdownField(
                      label: isSwahili ? 'Mradi' : 'Project',
                      isDarkMode: isDarkMode,
                      value:
                          projects.any((item) => _toNullableInt(item['id']) == _projectId)
                              ? _projectId
                              : null,
                      items: [
                        DropdownMenuItem<int?>(
                          value: null,
                          child: Text(isSwahili ? 'Hakuna' : 'None'),
                        ),
                        ...projects.map(
                          (item) => DropdownMenuItem<int?>(
                            value: _toNullableInt(item['id']),
                            child: Text(
                              _text(item['name']),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ),
                      ],
                      onChanged: (value) => setState(() => _projectId = value),
                      isRequired: false,
                    ),
                    const SizedBox(height: 12),
                    _buildDateField(
                      context,
                      controller: _issueDateController,
                      label: isSwahili ? 'Issue Date *' : 'Issue Date *',
                      isDarkMode: isDarkMode,
                    ),
                    const SizedBox(height: 12),
                    _buildDateField(
                      context,
                      controller: _dueDateController,
                      label: isSwahili ? 'Due Date' : 'Due Date',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    const SizedBox(height: 12),
                    _buildDateField(
                      context,
                      controller: _validUntilController,
                      label: isSwahili ? 'Valid Until' : 'Valid Until',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    const SizedBox(height: 12),
                    _buildTextField(
                      controller: _paymentTermsController,
                      label: isSwahili ? 'Masharti ya Malipo' : 'Payment Terms',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                    ),
                    const SizedBox(height: 18),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            isSwahili ? 'Vitu vya invoice' : 'Invoice Items',
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                        TextButton.icon(
                          onPressed: () => setState(
                            () => _items.add(_QuotationItemState.empty()),
                          ),
                          icon: const Icon(Icons.add),
                          label: Text(isSwahili ? 'Ongeza' : 'Add'),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    ..._buildItemForms(isDarkMode, isSwahili),
                    const SizedBox(height: 6),
                    _buildTextField(
                      controller: _notesController,
                      label: isSwahili ? 'Notes' : 'Notes',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                      maxLines: 4,
                    ),
                    const SizedBox(height: 12),
                    _buildTextField(
                      controller: _termsController,
                      label: isSwahili
                          ? 'Sheria na Masharti'
                          : 'Terms & Conditions',
                      isDarkMode: isDarkMode,
                      isRequired: false,
                      maxLines: 5,
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
                          : Text(
                              widget.quotation == null
                                  ? (isSwahili ? 'Hifadhi' : 'Save')
                                  : (isSwahili ? 'Sasisha' : 'Update'),
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

  List<Widget> _buildItemForms(bool isDarkMode, bool isSwahili) {
    return List<Widget>.generate(_items.length, (index) {
      final item = _items[index];
      final canRemove = _items.length > 1;

      return Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: isDarkMode ? Colors.white12 : Colors.black12,
            ),
            color: isDarkMode
                ? Colors.white.withValues(alpha: 0.03)
                : Colors.grey.withValues(alpha: 0.05),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      '${isSwahili ? 'Kipengee' : 'Item'} ${index + 1}',
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                  ),
                  if (canRemove)
                    IconButton(
                      onPressed: () {
                        setState(() {
                          final removed = _items.removeAt(index);
                          removed.dispose();
                        });
                      },
                      icon: const Icon(
                        Icons.delete_outline,
                        color: AppColors.error,
                      ),
                    ),
                ],
              ),
              _buildTextField(
                controller: item.descriptionController,
                label: isSwahili ? 'Description *' : 'Description *',
                isDarkMode: isDarkMode,
              ),
              const SizedBox(height: 10),
              _buildTextField(
                controller: item.quantityController,
                label: isSwahili ? 'Quantity *' : 'Quantity *',
                isDarkMode: isDarkMode,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
              ),
              const SizedBox(height: 10),
              _buildTextField(
                controller: item.unitController,
                label: isSwahili ? 'Unit' : 'Unit',
                isDarkMode: isDarkMode,
                isRequired: false,
              ),
              const SizedBox(height: 10),
              _buildTextField(
                controller: item.unitPriceController,
                label: isSwahili ? 'Unit Price *' : 'Unit Price *',
                isDarkMode: isDarkMode,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
              ),
              const SizedBox(height: 10),
              _buildTextField(
                controller: item.discountController,
                label: isSwahili ? 'Discount %' : 'Discount %',
                isDarkMode: isDarkMode,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                isRequired: false,
              ),
              const SizedBox(height: 10),
              _buildTextField(
                controller: item.taxController,
                label: isSwahili ? 'Tax %' : 'Tax %',
                isDarkMode: isDarkMode,
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                isRequired: false,
              ),
            ],
          ),
        ),
      );
    });
  }

  Future<void> _submit() async {
    final isSwahili = ref.read(isSwahiliProvider);
    if (!_formKey.currentState!.validate()) return;

    if (_clientId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.error,
          content: Text(
            isSwahili ? 'Tafadhali chagua mteja' : 'Please select a client',
          ),
        ),
      );
      return;
    }

    final items = _items.map((item) => item.toPayload()).toList();
    if (items.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.error,
          content: Text(
            isSwahili
                ? 'Ongeza angalau item moja'
                : 'Add at least one invoice item',
          ),
        ),
      );
      return;
    }

    final payload = <String, dynamic>{
      'document_type': 'invoice',
      'client_id': _clientId,
      'project_id': _projectId,
      'issue_date': _issueDateController.text.trim(),
      'due_date': _blankToNull(_dueDateController.text),
      'valid_until_date': _blankToNull(_validUntilController.text),
      'payment_terms': _blankToNull(_paymentTermsController.text),
      'notes': _blankToNull(_notesController.text),
      'terms_conditions': _blankToNull(_termsController.text),
      'items': items,
    };

    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final quotationId = _toInt(widget.quotation?['id']);
      if (quotationId > 0) {
        await api.put('/billing/documents/$quotationId', data: payload);
      } else {
        await api.post('/billing/documents', data: payload);
      }

      if (!mounted) return;
      Navigator.of(context).pop(true);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.success,
          content: Text(
            quotationId > 0
                ? (isSwahili ? 'Invoice imesasishwa' : 'Invoice updated')
                : (isSwahili ? 'Invoice imehifadhiwa' : 'Invoice created'),
          ),
        ),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.error,
          content: Text(vatErrorMessage(error, isSwahili: isSwahili)),
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _saving = false);
      }
    }
  }
}

class _QuotationItemState {
  final TextEditingController descriptionController;
  final TextEditingController quantityController;
  final TextEditingController unitController;
  final TextEditingController unitPriceController;
  final TextEditingController discountController;
  final TextEditingController taxController;

  _QuotationItemState({
    required this.descriptionController,
    required this.quantityController,
    required this.unitController,
    required this.unitPriceController,
    required this.discountController,
    required this.taxController,
  });

  factory _QuotationItemState.empty() {
    return _QuotationItemState(
      descriptionController: TextEditingController(),
      quantityController: TextEditingController(text: '1'),
      unitController: TextEditingController(),
      unitPriceController: TextEditingController(),
      discountController: TextEditingController(text: '0'),
      taxController: TextEditingController(text: '0'),
    );
  }

  factory _QuotationItemState.fromMap(Map<String, dynamic> map) {
    return _QuotationItemState(
      descriptionController:
          TextEditingController(text: map['description']?.toString() ?? ''),
      quantityController:
          TextEditingController(text: _decimalText(map['quantity'])),
      unitController: TextEditingController(text: map['unit']?.toString() ?? ''),
      unitPriceController:
          TextEditingController(text: _decimalText(map['unit_price'])),
      discountController:
          TextEditingController(text: _decimalText(map['discount_percentage'])),
      taxController:
          TextEditingController(text: _decimalText(map['tax_percentage'])),
    );
  }

  Map<String, dynamic> toPayload() {
    return {
      'description': descriptionController.text.trim(),
      'quantity': _toDouble(quantityController.text),
      'unit': _blankToNull(unitController.text),
      'unit_price': _toDouble(unitPriceController.text),
      'discount_percentage': _toDouble(discountController.text),
      'tax_percentage': _toDouble(taxController.text),
    };
  }

  void dispose() {
    descriptionController.dispose();
    quantityController.dispose();
    unitController.dispose();
    unitPriceController.dispose();
    discountController.dispose();
    taxController.dispose();
  }
}

class _QuotationDetailRow extends StatelessWidget {
  final String label;
  final String value;

  const _QuotationDetailRow({
    required this.label,
    required this.value,
  });

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
          Text(
            value,
            style: const TextStyle(fontSize: 14, height: 1.4),
          ),
        ],
      ),
    );
  }
}

class _SectionCard extends StatelessWidget {
  final String title;
  final List<Widget> children;

  const _SectionCard({
    required this.title,
    required this.children,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(14),
        color: Colors.grey.withValues(alpha: 0.08),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 8),
          ...children,
        ],
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  final String label;

  const _StatusChip({required this.label});

  @override
  Widget build(BuildContext context) {
    final normalized = label.toLowerCase();
    Color color;
    if (normalized == 'draft') {
      color = AppColors.warning;
    } else if (normalized == 'paid' || normalized == 'accepted') {
      color = AppColors.success;
    } else if (normalized == 'sent' || normalized == 'viewed') {
      color = AppColors.info;
    } else {
      color = AppColors.textSecondary;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label.toUpperCase(),
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.w700,
          fontSize: 11,
        ),
      ),
    );
  }
}

class _InfoChip extends StatelessWidget {
  final IconData icon;
  final String label;

  const _InfoChip({
    required this.icon,
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.grey.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: AppColors.textSecondary),
          const SizedBox(width: 6),
          Flexible(
            child: Text(
              label,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 12),
            ),
          ),
        ],
      ),
    );
  }
}

class _MetricText extends StatelessWidget {
  final String label;
  final String value;

  const _MetricText({
    required this.label,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return RichText(
      text: TextSpan(
        style: DefaultTextStyle.of(context).style.copyWith(fontSize: 13),
        children: [
          TextSpan(
            text: '$label: ',
            style: const TextStyle(color: AppColors.textSecondary),
          ),
          TextSpan(
            text: value,
            style: const TextStyle(fontWeight: FontWeight.w700),
          ),
        ],
      ),
    );
  }
}

class _QuotationErrorView extends StatelessWidget {
  final bool isSwahili;
  final String message;
  final VoidCallback onRetry;

  const _QuotationErrorView({
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
            const Icon(
              Icons.error_outline,
              size: 52,
              color: AppColors.error,
            ),
            const SizedBox(height: 12),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(height: 1.5),
            ),
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

class _BottomSheetLoading extends StatelessWidget {
  const _BottomSheetLoading();

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
        const Expanded(
          child: Center(child: CircularProgressIndicator()),
        ),
      ],
    );
  }
}

Widget _buildTextField({
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
      if ((value ?? '').trim().isEmpty) {
        return 'Required';
      }
      return null;
    },
  );
}

Widget _buildDropdownField({
  required String label,
  required bool isDarkMode,
  required int? value,
  required List<DropdownMenuItem<int?>> items,
  required ValueChanged<int?> onChanged,
  bool isRequired = true,
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
    validator: (selected) {
      if (isRequired && selected == null) {
        return 'Required';
      }
      return null;
    },
    onChanged: onChanged,
  );
}

Widget _buildDateField(
  BuildContext context, {
  required TextEditingController controller,
  required String label,
  required bool isDarkMode,
  bool isRequired = true,
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
    validator: (value) {
      if (!isRequired) return null;
      if ((value ?? '').trim().isEmpty) {
        return 'Required';
      }
      return null;
    },
    onTap: () async {
      final initialDate =
          DateTime.tryParse(controller.text) ?? DateTime.now();
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

String _money(dynamic value) {
  final amount = _toDouble(value);
  return 'TZS ${amount.toStringAsFixed(2)}';
}

String _text(dynamic value) {
  final text = value?.toString().trim() ?? '';
  return text.isEmpty ? '-' : text;
}

String _decimalText(dynamic value) {
  if (value == null) return '0';
  final amount = _toDouble(value);
  if (amount == amount.truncateToDouble()) {
    return amount.toInt().toString();
  }
  return amount.toStringAsFixed(2);
}

String _dateValue(dynamic value) {
  final text = value?.toString() ?? '';
  if (text.isEmpty) return '';
  return text.split('T').first;
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
