import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _proformaSearchProvider = StateProvider.autoDispose<String>((ref) => '');

final _proformaStatusProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);

final _proformaListProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get(
        '/billing/documents',
        queryParameters: {'document_type': 'proforma', 'per_page': 100},
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

final _proformaRefsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final response = await api.get('/billing/reference-data');
  final data = response.data is Map<String, dynamic>
      ? response.data as Map<String, dynamic>
      : const <String, dynamic>{};
  return data['data'] is Map
      ? Map<String, dynamic>.from(data['data'] as Map)
      : const <String, dynamic>{};
});

final _proformaDetailProvider = FutureProvider.autoDispose
    .family<Map<String, dynamic>, int>((ref, id) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get('/billing/documents/$id');
      final data = response.data is Map<String, dynamic>
          ? response.data as Map<String, dynamic>
          : const <String, dynamic>{};
      return data['data'] is Map
          ? Map<String, dynamic>.from(data['data'] as Map)
          : <String, dynamic>{};
    });

class BillingProformasScreen extends ConsumerStatefulWidget {
  const BillingProformasScreen({super.key});

  @override
  ConsumerState<BillingProformasScreen> createState() =>
      _BillingProformasScreenState();
}

class _BillingProformasScreenState
    extends ConsumerState<BillingProformasScreen> {
  final _searchController = TextEditingController();

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final itemsAsync = ref.watch(_proformaListProvider);
    final search = ref.watch(_proformaSearchProvider);
    final status = ref.watch(_proformaStatusProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Proformas' : 'Proformas'),
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
                      ref.read(_proformaSearchProvider.notifier).state = value,
                  decoration: InputDecoration(
                    hintText: isSwahili
                        ? 'Tafuta proforma...'
                        : 'Search proforma...',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: search.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () {
                              _searchController.clear();
                              ref.read(_proformaSearchProvider.notifier).state =
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
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: Row(
                    children: [
                      _FilterChip(
                        label: 'All',
                        isSelected: status == null,
                        onTap: () =>
                            ref.read(_proformaStatusProvider.notifier).state =
                                null,
                        isDarkMode: isDarkMode,
                        color: AppColors.primary,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Draft',
                        isSelected: status == 'draft',
                        onTap: () =>
                            ref.read(_proformaStatusProvider.notifier).state =
                                'draft',
                        isDarkMode: isDarkMode,
                        color: Colors.grey,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Pending',
                        isSelected: status == 'pending',
                        onTap: () =>
                            ref.read(_proformaStatusProvider.notifier).state =
                                'pending',
                        isDarkMode: isDarkMode,
                        color: Colors.blue,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Sent',
                        isSelected: status == 'sent',
                        onTap: () =>
                            ref.read(_proformaStatusProvider.notifier).state =
                                'sent',
                        isDarkMode: isDarkMode,
                        color: Colors.blue,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Accepted',
                        isSelected: status == 'accepted',
                        onTap: () =>
                            ref.read(_proformaStatusProvider.notifier).state =
                                'accepted',
                        isDarkMode: isDarkMode,
                        color: AppColors.success,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Rejected',
                        isSelected: status == 'rejected',
                        onTap: () =>
                            ref.read(_proformaStatusProvider.notifier).state =
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
              onRefresh: () async => ref.invalidate(_proformaListProvider),
              child: itemsAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => _ProformaErrorView(
                  isSwahili: isSwahili,
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  onRetry: () => ref.invalidate(_proformaListProvider),
                  isDarkMode: isDarkMode,
                ),
                data: (items) {
                  final filteredItems = items.where((item) {
                    if (status != null &&
                        _text(item['status']).toLowerCase() != status) {
                      return false;
                    }
                    if (search.isEmpty) return true;
                    final query = search.toLowerCase();
                    final docNumber = (_text(
                      item['document_number'],
                    )).toLowerCase();
                    final client =
                        item['client'] as Map<String, dynamic>? ?? {};
                    final clientName = (_text(
                      client['full_name'] ?? client['name'],
                    )).toLowerCase();
                    return docNumber.contains(query) ||
                        clientName.contains(query);
                  }).toList();

                  if (filteredItems.isEmpty) {
                    return ListView(
                      children: [
                        SizedBox(
                          height: MediaQuery.of(context).size.height * 0.2,
                        ),
                        Icon(
                          Icons.request_quote_outlined,
                          size: 64,
                          color: Colors.grey[400],
                        ),
                        const SizedBox(height: 16),
                        Text(
                          isSwahili ? 'Hakuna proforma' : 'No proformas found',
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
                    itemCount: filteredItems.length,
                    itemBuilder: (context, index) {
                      final item = filteredItems[index];
                      final id = _toInt(item['id']);
                      final itemStatus = _text(item['status']);
                      final isDraft = itemStatus.toLowerCase() == 'draft';

                      return _ProformaCard(
                        item: item,
                        index: index + 1,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onTap: id > 0
                            ? () => _showProformaSheet(context, ref, id)
                            : null,
                        onEdit: isDraft
                            ? () => _openProformaForm(
                                context,
                                ref,
                                proforma: item,
                              )
                            : null,
                        onDelete: isDraft
                            ? () => _deleteProforma(context, ref, item)
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
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 70),
        child: FloatingActionButton(
          onPressed: () => _openProformaForm(context, ref),
          backgroundColor: AppColors.primary,
          child: const Icon(Icons.add, color: Colors.white),
        ),
      ),
    );
  }
}

Future<void> _openProformaForm(
  BuildContext context,
  WidgetRef ref, {
  Map<String, dynamic>? proforma,
}) async {
  final refs = await ref.read(_proformaRefsProvider.future);
  var initialProforma = proforma;
  final proformaId = _toInt(proforma?['id']);
  final hasItems = proforma?['items'] is List;

  if (proforma != null && proformaId > 0 && !hasItems) {
    initialProforma = await ref.read(
      _proformaDetailProvider(proformaId).future,
    );
  }

  if (!context.mounted) return;

  final result = await showModalBottomSheet<bool>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.94,
      child: _ProformaFormSheet(refs: refs, proforma: initialProforma),
    ),
  );

  if (result == true) {
    ref.invalidate(_proformaListProvider);
    if (proformaId > 0) {
      ref.invalidate(_proformaDetailProvider(proformaId));
    }
  }
}

Future<void> _deleteProforma(
  BuildContext context,
  WidgetRef ref,
  Map<String, dynamic> quotation,
) async {
  final isSwahili = ref.read(isSwahiliProvider);
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (dialogContext) => AlertDialog(
      scrollable: true,
      title: Text(isSwahili ? 'Futa Proforma' : 'Delete Proforma'),
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
    await ref
        .read(apiClientProvider)
        .delete('/billing/documents/${quotation['id']}');
    ref.invalidate(_proformaListProvider);

    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.success,
          content: Text(isSwahili ? 'Proforma imefutwa' : 'Proforma deleted'),
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

void _showProformaSheet(BuildContext context, WidgetRef ref, int id) {
  if (id <= 0) return;

  showModalBottomSheet<void>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.88,
      child: Consumer(
        builder: (context, ref, _) {
          final detailAsync = ref.watch(_proformaDetailProvider(id));
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
                  onRetry: () => ref.invalidate(_proformaDetailProvider(id)),
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
                  final isDraft =
                      _text(detail['status']).toLowerCase() == 'draft';

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
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
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
                                          _StatusChip(
                                            label: _text(detail['status']),
                                          ),
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
                                      await _openProformaForm(
                                        context,
                                        ref,
                                        proforma: detail,
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
                                  value:
                                      client['full_name']?.toString() ??
                                      client['name']?.toString() ??
                                      '-',
                                ),
                                _QuotationDetailRow(
                                  label: isSwahili ? 'Mradi' : 'Project',
                                  value:
                                      project['project_name']?.toString() ??
                                      '-',
                                ),
                                _QuotationDetailRow(
                                  label: isSwahili
                                      ? 'Issue Date'
                                      : 'Issue Date',
                                  value: _text(detail['issue_date']),
                                ),
                                _QuotationDetailRow(
                                  label: isSwahili
                                      ? 'Valid Until'
                                      : 'Valid Until',
                                  value: _text(detail['valid_until_date']),
                                ),
                                _QuotationDetailRow(
                                  label: isSwahili
                                      ? 'Payment Terms'
                                      : 'Payment Terms',
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
                                        padding: const EdgeInsets.only(
                                          bottom: 10,
                                        ),
                                        child: Container(
                                          width: double.infinity,
                                          padding: const EdgeInsets.all(12),
                                          decoration: BoxDecoration(
                                            color: Colors.grey.withValues(
                                              alpha: 0.08,
                                            ),
                                            borderRadius: BorderRadius.circular(
                                              12,
                                            ),
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
                                                  color:
                                                      AppColors.textSecondary,
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

class _ProformaFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? proforma;

  const _ProformaFormSheet({required this.refs, this.proforma});

  @override
  ConsumerState<_ProformaFormSheet> createState() => _ProformaFormSheetState();
}

class _ProformaFormSheetState extends ConsumerState<_ProformaFormSheet> {
  final _formKey = GlobalKey<FormState>();

  late final TextEditingController _issueDateController = TextEditingController(
    text: _dateValue(widget.proforma?['issue_date']),
  );
  late final TextEditingController _validUntilController =
      TextEditingController(
        text: _dateValue(widget.proforma?['valid_until_date']),
      );
  late final TextEditingController _paymentTermsController =
      TextEditingController(
        text: widget.proforma?['payment_terms']?.toString() ?? '',
      );
  late final TextEditingController _notesController = TextEditingController(
    text: widget.proforma?['notes']?.toString() ?? '',
  );
  late final TextEditingController _termsController = TextEditingController(
    text: widget.proforma?['terms_conditions']?.toString() ?? '',
  );

  int? _clientId;
  int? _projectId;
  bool _saving = false;
  late final List<_ProformaItemState> _items;

  @override
  void initState() {
    super.initState();
    _clientId = _toNullableInt(widget.proforma?['client_id']);
    _projectId = _toNullableInt(widget.proforma?['project_id']);
    final sourceItems = (widget.proforma?['items'] as List? ?? const [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
    _items = sourceItems.isNotEmpty
        ? sourceItems.map(_ProformaItemState.fromMap).toList()
        : [_ProformaItemState.empty()];
  }

  @override
  void dispose() {
    _issueDateController.dispose();
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
                      widget.proforma == null
                          ? (isSwahili ? 'Proforma Mpya' : 'New Proforma')
                          : (isSwahili ? 'Hariri Proforma' : 'Edit Proforma'),
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
                      value:
                          clients.any(
                            (item) => _toNullableInt(item['id']) == _clientId,
                          )
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
                          projects.any(
                            (item) => _toNullableInt(item['id']) == _projectId,
                          )
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
                            isSwahili ? 'Vitu vya proforma' : 'Proforma Items',
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                        TextButton.icon(
                          onPressed: () => setState(
                            () => _items.add(_ProformaItemState.empty()),
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
                              widget.proforma == null
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
                : 'Add at least one proforma item',
          ),
        ),
      );
      return;
    }

    final payload = <String, dynamic>{
      'document_type': 'proforma',
      'client_id': _clientId,
      'project_id': _projectId,
      'issue_date': _issueDateController.text.trim(),
      'valid_until_date': _blankToNull(_validUntilController.text),
      'payment_terms': _blankToNull(_paymentTermsController.text),
      'notes': _blankToNull(_notesController.text),
      'terms_conditions': _blankToNull(_termsController.text),
      'items': items,
    };

    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final proformaId = _toInt(widget.proforma?['id']);
      if (proformaId > 0) {
        await api.put('/billing/documents/$proformaId', data: payload);
      } else {
        await api.post('/billing/documents', data: payload);
      }

      if (!mounted) return;
      Navigator.of(context).pop(true);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.success,
          content: Text(
            proformaId > 0
                ? (isSwahili ? 'Proforma imesasishwa' : 'Proforma updated')
                : (isSwahili ? 'Proforma imehifadhiwa' : 'Proforma created'),
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

class _ProformaItemState {
  final TextEditingController descriptionController;
  final TextEditingController quantityController;
  final TextEditingController unitController;
  final TextEditingController unitPriceController;
  final TextEditingController discountController;
  final TextEditingController taxController;

  _ProformaItemState({
    required this.descriptionController,
    required this.quantityController,
    required this.unitController,
    required this.unitPriceController,
    required this.discountController,
    required this.taxController,
  });

  factory _ProformaItemState.empty() {
    return _ProformaItemState(
      descriptionController: TextEditingController(),
      quantityController: TextEditingController(text: '1'),
      unitController: TextEditingController(),
      unitPriceController: TextEditingController(),
      discountController: TextEditingController(text: '0'),
      taxController: TextEditingController(text: '0'),
    );
  }

  factory _ProformaItemState.fromMap(Map<String, dynamic> map) {
    return _ProformaItemState(
      descriptionController: TextEditingController(
        text: map['description']?.toString() ?? '',
      ),
      quantityController: TextEditingController(
        text: _decimalText(map['quantity']),
      ),
      unitController: TextEditingController(
        text: map['unit']?.toString() ?? '',
      ),
      unitPriceController: TextEditingController(
        text: _decimalText(map['unit_price']),
      ),
      discountController: TextEditingController(
        text: _decimalText(map['discount_percentage']),
      ),
      taxController: TextEditingController(
        text: _decimalText(map['tax_percentage']),
      ),
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

  const _QuotationDetailRow({required this.label, required this.value});

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
          Text(value, style: const TextStyle(fontSize: 14, height: 1.4)),
        ],
      ),
    );
  }
}

class _FilterChip extends StatelessWidget {
  final String label;
  final bool isSelected;
  final VoidCallback onTap;
  final bool isDarkMode;
  final Color color;

  const _FilterChip({
    required this.label,
    required this.isSelected,
    required this.onTap,
    required this.isDarkMode,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected
              ? color
              : (isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100]),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: isSelected ? color : Colors.transparent),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: isSelected
                ? Colors.white
                : (isDarkMode ? Colors.white54 : Colors.grey[600]),
          ),
        ),
      ),
    );
  }
}

class _ProformaCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final int index;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback? onTap;
  final VoidCallback? onEdit;
  final VoidCallback? onDelete;

  const _ProformaCard({
    required this.item,
    required this.index,
    required this.isSwahili,
    required this.isDarkMode,
    this.onTap,
    this.onEdit,
    this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final status = _text(item['status']);
    final client = item['client'] as Map<String, dynamic>? ?? {};
    final project = item['project'] as Map<String, dynamic>? ?? {};

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
                  color: AppColors.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Text(
                    '$index',
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: AppColors.primary,
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
                      _text(item['document_number']),
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
                      client['full_name']?.toString() ??
                          client['name']?.toString() ??
                          '-',
                      style: TextStyle(
                        fontSize: 12,
                        color: isDarkMode
                            ? Colors.white54
                            : AppColors.textSecondary,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        _StatusChip(label: status),
                        const SizedBox(width: 8),
                        if (_text(project['project_name']).isNotEmpty &&
                            _text(project['project_name']) != '-')
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 6,
                              vertical: 2,
                            ),
                            decoration: BoxDecoration(
                              color: AppColors.primary.withValues(alpha: 0.1),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              _text(project['project_name']),
                              style: const TextStyle(
                                fontSize: 10,
                                color: AppColors.primary,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      _money(item['total_amount']),
                      style: TextStyle(
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                        color: isDarkMode
                            ? Colors.white
                            : AppColors.textPrimary,
                      ),
                    ),
                  ],
                ),
              ),
              PopupMenuButton<String>(
                onSelected: (value) {
                  if (value == 'view') {
                    onTap?.call();
                  } else if (value == 'edit') {
                    onEdit?.call();
                  } else if (value == 'delete') {
                    onDelete?.call();
                  }
                },
                itemBuilder: (_) => [
                  PopupMenuItem(
                    value: 'view',
                    child: Row(
                      children: [
                        const Icon(Icons.visibility_outlined, size: 20),
                        const SizedBox(width: 10),
                        Text(isSwahili ? 'Tazama' : 'View'),
                      ],
                    ),
                  ),
                  if (onEdit != null)
                    PopupMenuItem(
                      value: 'edit',
                      child: Row(
                        children: [
                          const Icon(Icons.edit_outlined, size: 20),
                          const SizedBox(width: 10),
                          Text(isSwahili ? 'Hariri' : 'Edit'),
                        ],
                      ),
                    ),
                  if (onDelete != null)
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
                            isSwahili ? 'Futa' : 'Delete',
                            style: const TextStyle(color: AppColors.error),
                          ),
                        ],
                      ),
                    ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ProformaErrorView extends StatelessWidget {
  final bool isSwahili;
  final String message;
  final VoidCallback onRetry;
  final bool isDarkMode;

  const _ProformaErrorView({
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
            Text(
              message,
              textAlign: TextAlign.center,
              style: TextStyle(
                color: isDarkMode ? Colors.white54 : AppColors.textSecondary,
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
}

class _SectionCard extends StatelessWidget {
  final String title;
  final List<Widget> children;

  const _SectionCard({required this.title, required this.children});

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
            style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700),
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

  const _InfoChip({required this.icon, required this.label});

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
            const Icon(Icons.error_outline, size: 52, color: AppColors.error),
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
        const Expanded(child: Center(child: CircularProgressIndicator())),
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
