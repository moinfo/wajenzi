import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../../core/router/app_router.dart';
import '../../providers/settings_provider.dart';
import '../vat/vat_shared.dart';

final _invoiceSearchProvider = StateProvider.autoDispose<String>((ref) => '');

final _invoiceStatusProvider = StateProvider.autoDispose<String?>(
  (ref) => null,
);

final _invoiceListProvider =
    FutureProvider.autoDispose<List<Map<String, dynamic>>>((ref) async {
      final api = ref.watch(apiClientProvider);
      final response = await api.get(
        '/billing/documents',
        queryParameters: {'document_type': 'invoice', 'per_page': 100},
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

final _invoiceRefsProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
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

final _invoiceDetailProvider = FutureProvider.autoDispose
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

class BillingInvoicesScreen extends ConsumerStatefulWidget {
  const BillingInvoicesScreen({super.key});

  @override
  ConsumerState<BillingInvoicesScreen> createState() =>
      _BillingInvoicesScreenState();
}

class _BillingInvoicesScreenState extends ConsumerState<BillingInvoicesScreen> {
  final _searchController = TextEditingController();

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final itemsAsync = ref.watch(_invoiceListProvider);
    final search = ref.watch(_invoiceSearchProvider);
    final status = ref.watch(_invoiceStatusProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Invoices' : 'Invoices'),
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
                      ref.read(_invoiceSearchProvider.notifier).state = value,
                  decoration: InputDecoration(
                    hintText: isSwahili
                        ? 'Tafuta invoice...'
                        : 'Search invoice...',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: search.isNotEmpty
                        ? IconButton(
                            icon: const Icon(Icons.clear),
                            onPressed: () {
                              _searchController.clear();
                              ref.read(_invoiceSearchProvider.notifier).state =
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
                            ref.read(_invoiceStatusProvider.notifier).state =
                                null,
                        isDarkMode: isDarkMode,
                        color: AppColors.primary,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Draft',
                        isSelected: status == 'draft',
                        onTap: () =>
                            ref.read(_invoiceStatusProvider.notifier).state =
                                'draft',
                        isDarkMode: isDarkMode,
                        color: Colors.grey,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Pending',
                        isSelected: status == 'pending',
                        onTap: () =>
                            ref.read(_invoiceStatusProvider.notifier).state =
                                'pending',
                        isDarkMode: isDarkMode,
                        color: Colors.blue,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Sent',
                        isSelected: status == 'sent',
                        onTap: () =>
                            ref.read(_invoiceStatusProvider.notifier).state =
                                'sent',
                        isDarkMode: isDarkMode,
                        color: Colors.blue,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Partial',
                        isSelected: status == 'partial_paid',
                        onTap: () =>
                            ref.read(_invoiceStatusProvider.notifier).state =
                                'partial_paid',
                        isDarkMode: isDarkMode,
                        color: Colors.orange,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Paid',
                        isSelected: status == 'paid',
                        onTap: () =>
                            ref.read(_invoiceStatusProvider.notifier).state =
                                'paid',
                        isDarkMode: isDarkMode,
                        color: AppColors.success,
                      ),
                      const SizedBox(width: 8),
                      _FilterChip(
                        label: 'Overdue',
                        isSelected: status == 'overdue',
                        onTap: () =>
                            ref.read(_invoiceStatusProvider.notifier).state =
                                'overdue',
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
              onRefresh: () async => ref.invalidate(_invoiceListProvider),
              child: itemsAsync.when(
                loading: () => const Center(child: CircularProgressIndicator()),
                error: (error, _) => _InvoiceErrorView(
                  isSwahili: isSwahili,
                  message: vatErrorMessage(error, isSwahili: isSwahili),
                  onRetry: () => ref.invalidate(_invoiceListProvider),
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
                          Icons.receipt_long_outlined,
                          size: 64,
                          color: Colors.grey[400],
                        ),
                        const SizedBox(height: 16),
                        Text(
                          isSwahili ? 'Hakuna invoice' : 'No invoices found',
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

                      return _InvoiceCard(
                        item: item,
                        index: index + 1,
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                        onTap: id > 0
                            ? () => _showInvoiceSheet(context, ref, id)
                            : null,
                        onEdit: isDraft
                            ? () =>
                                  _openInvoiceForm(context, ref, invoice: item)
                            : null,
                        onDelete: isDraft
                            ? () => _deleteInvoice(context, ref, item)
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
          onPressed: () => _openInvoiceForm(context, ref),
          backgroundColor: AppColors.primary,
          child: const Icon(Icons.add, color: Colors.white),
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

class _InvoiceCard extends StatelessWidget {
  final Map<String, dynamic> item;
  final int index;
  final bool isSwahili;
  final bool isDarkMode;
  final VoidCallback? onTap;
  final VoidCallback? onEdit;
  final VoidCallback? onDelete;

  const _InvoiceCard({
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

Future<void> _openInvoiceForm(
  BuildContext context,
  WidgetRef ref, {
  Map<String, dynamic>? invoice,
}) async {
  final refs = await ref.read(_invoiceRefsProvider.future);
  var initialInvoice = invoice;
  final invoiceId = _toInt(invoice?['id']);
  final hasItems = invoice?['items'] is List;

  if (invoice != null && invoiceId > 0 && !hasItems) {
    initialInvoice = await ref.read(_invoiceDetailProvider(invoiceId).future);
  }

  if (!context.mounted) return;

  final result = await showModalBottomSheet<bool>(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (_) => FractionallySizedBox(
      heightFactor: 0.94,
      child: _InvoiceFormSheet(refs: refs, invoice: initialInvoice),
    ),
  );

  if (result == true) {
    ref.invalidate(_invoiceListProvider);
    if (invoiceId > 0) {
      ref.invalidate(_invoiceDetailProvider(invoiceId));
    }
  }
}

Future<void> _deleteInvoice(
  BuildContext context,
  WidgetRef ref,
  Map<String, dynamic> invoice,
) async {
  final isSwahili = ref.read(isSwahiliProvider);
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (dialogContext) => AlertDialog(
      backgroundColor: ref.read(isDarkModeProvider)
          ? const Color(0xFF1A1A2E)
          : null,
      title: Text(
        isSwahili ? 'Thibitisha Kufuta' : 'Confirm Delete',
        style: TextStyle(
          color: ref.read(isDarkModeProvider) ? Colors.white : null,
        ),
      ),
      content: Text(
        isSwahili
            ? 'Je, una uhakika unataka kufuta "${_text(invoice['document_number'])}"?'
            : 'Are you sure you want to delete "${_text(invoice['document_number'])}"?',
        style: TextStyle(
          color: ref.read(isDarkModeProvider) ? Colors.white70 : null,
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, false),
          child: Text(isSwahili ? 'Hapana' : 'No'),
        ),
        TextButton(
          onPressed: () => Navigator.pop(dialogContext, true),
          child: Text(
            isSwahili ? 'Ndiyo, Futa' : 'Yes, Delete',
            style: const TextStyle(color: AppColors.error),
          ),
        ),
      ],
    ),
  );

  if (confirmed != true) return;

  try {
    await ref
        .read(apiClientProvider)
        .delete('/billing/documents/${invoice['id']}');
    ref.invalidate(_invoiceListProvider);

    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.success,
          content: Text(isSwahili ? 'Invoice imefutwa' : 'Invoice deleted'),
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

void _showInvoiceSheet(BuildContext context, WidgetRef ref, int id) {
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
                    child: detailAsync.when(
                      loading: () =>
                          const Center(child: CircularProgressIndicator()),
                      error: (error, _) => _InvoiceErrorView(
                        isSwahili: isSwahili,
                        message: vatErrorMessage(error, isSwahili: isSwahili),
                        onRetry: () =>
                            ref.invalidate(_invoiceDetailProvider(id)),
                        isDarkMode: isDarkMode,
                      ),
                      data: (detail) {
                        final client =
                            detail['client'] as Map<String, dynamic>? ??
                            const {};
                        final project =
                            detail['project'] as Map<String, dynamic>? ??
                            const {};
                        final items = (detail['items'] as List? ?? const [])
                            .whereType<Map>()
                            .map((item) => Map<String, dynamic>.from(item))
                            .toList();
                        final isDraft =
                            _text(detail['status']).toLowerCase() == 'draft';

                        return ListView(
                          padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
                          children: [
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                CircleAvatar(
                                  radius: 24,
                                  backgroundColor: AppColors.primary.withValues(
                                    alpha: 0.1,
                                  ),
                                  child: const Icon(
                                    Icons.receipt_long,
                                    color: AppColors.primary,
                                  ),
                                ),
                                const SizedBox(width: 16),
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
                                      _StatusChip(
                                        label: _text(detail['status']),
                                      ),
                                    ],
                                  ),
                                ),
                                if (isDraft)
                                  IconButton(
                                    onPressed: () async {
                                      Navigator.of(context).pop();
                                      await _openInvoiceForm(
                                        context,
                                        ref,
                                        invoice: detail,
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
                                _InvoiceDetailRow(
                                  label: isSwahili ? 'Mteja' : 'Client',
                                  value:
                                      client['full_name']?.toString() ??
                                      client['name']?.toString() ??
                                      '-',
                                  isDarkMode: isDarkMode,
                                ),
                                _InvoiceDetailRow(
                                  label: isSwahili ? 'Mradi' : 'Project',
                                  value:
                                      project['project_name']?.toString() ??
                                      '-',
                                  isDarkMode: isDarkMode,
                                ),
                                _InvoiceDetailRow(
                                  label: isSwahili ? 'Tarehe' : 'Issue Date',
                                  value: _text(detail['issue_date']),
                                  isDarkMode: isDarkMode,
                                ),
                                _InvoiceDetailRow(
                                  label: isSwahili ? 'Due Date' : 'Due Date',
                                  value: _text(detail['due_date']),
                                  isDarkMode: isDarkMode,
                                ),
                              ],
                            ),
                            const SizedBox(height: 14),
                            _SectionCard(
                              title: isSwahili ? 'Fedha' : 'Amounts',
                              children: [
                                _InvoiceDetailRow(
                                  label: isSwahili ? 'Jumla' : 'Total',
                                  value: _money(detail['total_amount']),
                                  isDarkMode: isDarkMode,
                                  valueColor: AppColors.primary,
                                ),
                                _InvoiceDetailRow(
                                  label: isSwahili ? 'Malipo' : 'Paid',
                                  value: _money(detail['paid_amount']),
                                  isDarkMode: isDarkMode,
                                ),
                                _InvoiceDetailRow(
                                  label: isSwahili ? 'Salio' : 'Balance',
                                  value: _money(detail['balance_amount']),
                                  isDarkMode: isDarkMode,
                                  valueColor: AppColors.error,
                                ),
                              ],
                            ),
                            if (items.isNotEmpty) ...[
                              const SizedBox(height: 14),
                              _SectionCard(
                                title: isSwahili ? 'Vitu' : 'Items',
                                children: items
                                    .map(
                                      (item) => Container(
                                        margin: const EdgeInsets.only(
                                          bottom: 8,
                                        ),
                                        padding: const EdgeInsets.all(12),
                                        decoration: BoxDecoration(
                                          color: isDarkMode
                                              ? Colors.white.withValues(
                                                  alpha: 0.05,
                                                )
                                              : Colors.grey.withValues(
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
                                            const SizedBox(height: 4),
                                            Text(
                                              '${_decimalText(item['quantity'])} ${_text(item['unit'])} x ${_money(item['unit_price'])}',
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
                                    )
                                    .toList(),
                              ),
                            ],
                          ],
                        );
                      },
                    ),
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

class _InvoiceDetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDarkMode;
  final Color? valueColor;

  const _InvoiceDetailRow({
    required this.label,
    required this.value,
    required this.isDarkMode,
    this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
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
                    color:
                        valueColor ??
                        (isDarkMode ? Colors.white : AppColors.textPrimary),
                    fontWeight: valueColor != null
                        ? FontWeight.w600
                        : FontWeight.normal,
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
      color = Colors.grey;
    } else if (normalized == 'paid') {
      color = AppColors.success;
    } else if (normalized == 'partial_paid' || normalized == 'partial') {
      color = Colors.orange;
    } else if (normalized == 'overdue') {
      color = AppColors.error;
    } else if (normalized == 'sent' ||
        normalized == 'viewed' ||
        normalized == 'pending') {
      color = Colors.blue;
    } else if (normalized == 'cancelled' || normalized == 'refunded') {
      color = AppColors.warning;
    } else {
      color = AppColors.textSecondary;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label.toUpperCase(),
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.w700,
          fontSize: 10,
        ),
      ),
    );
  }
}

class _InvoiceErrorView extends StatelessWidget {
  final bool isSwahili;
  final String message;
  final VoidCallback onRetry;
  final bool isDarkMode;

  const _InvoiceErrorView({
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

class _InvoiceFormSheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> refs;
  final Map<String, dynamic>? invoice;

  const _InvoiceFormSheet({required this.refs, this.invoice});

  @override
  ConsumerState<_InvoiceFormSheet> createState() => _InvoiceFormSheetState();
}

class _InvoiceFormSheetState extends ConsumerState<_InvoiceFormSheet> {
  final _formKey = GlobalKey<FormState>();

  late final TextEditingController _issueDateController = TextEditingController(
    text: _dateValue(widget.invoice?['issue_date']),
  );
  late final TextEditingController _dueDateController = TextEditingController(
    text: _dateValue(widget.invoice?['due_date']),
  );
  late final TextEditingController _paymentTermsController =
      TextEditingController(
        text: widget.invoice?['payment_terms']?.toString() ?? '',
      );
  late final TextEditingController _notesController = TextEditingController(
    text: widget.invoice?['notes']?.toString() ?? '',
  );
  late final TextEditingController _termsController = TextEditingController(
    text: widget.invoice?['terms_conditions']?.toString() ?? '',
  );

  int? _clientId;
  int? _projectId;
  bool _saving = false;
  late final List<_InvoiceItemState> _items;

  @override
  void initState() {
    super.initState();
    _clientId = _toNullableInt(widget.invoice?['client_id']);
    _projectId = _toNullableInt(widget.invoice?['project_id']);
    final sourceItems = (widget.invoice?['items'] as List? ?? const [])
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
    _items = sourceItems.isNotEmpty
        ? sourceItems.map(_InvoiceItemState.fromMap).toList()
        : [_InvoiceItemState.empty()];
  }

  @override
  void dispose() {
    _issueDateController.dispose();
    _dueDateController.dispose();
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
                    Row(
                      children: [
                        CircleAvatar(
                          radius: 24,
                          backgroundColor: AppColors.primary.withValues(
                            alpha: 0.1,
                          ),
                          child: Icon(
                            widget.invoice == null ? Icons.add : Icons.edit,
                            color: AppColors.primary,
                          ),
                        ),
                        const SizedBox(width: 16),
                        Text(
                          widget.invoice == null
                              ? (isSwahili ? 'Invoice Mpya' : 'New Invoice')
                              : (isSwahili ? 'Hariri Invoice' : 'Edit Invoice'),
                          style: const TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 18),
                    _buildDropdownField(
                      label: isSwahili ? 'Mteja *' : 'Client *',
                      isDarkMode: isDarkMode,
                      value: _clientId,
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
                    _buildDateField(
                      context,
                      controller: _issueDateController,
                      label: isSwahili ? 'Tarehe *' : 'Issue Date *',
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
                    const SizedBox(height: 18),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            isSwahili ? 'Vitu' : 'Items',
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                        TextButton.icon(
                          onPressed: () {
                            setState(() {
                              _items.add(_InvoiceItemState.empty());
                            });
                          },
                          icon: const Icon(Icons.add, size: 18),
                          label: Text(isSwahili ? 'Ongeza' : 'Add'),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    ...List.generate(_items.length, (index) {
                      final item = _items[index];
                      return Container(
                        margin: const EdgeInsets.only(bottom: 12),
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: isDarkMode
                              ? Colors.white.withValues(alpha: 0.05)
                              : Colors.grey.withValues(alpha: 0.08),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: isDarkMode
                                ? Colors.white12
                                : Colors.grey[300]!,
                          ),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    '${isSwahili ? 'Kipengee' : 'Item'} ${index + 1}',
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ),
                                if (_items.length > 1)
                                  IconButton(
                                    icon: const Icon(Icons.close, size: 18),
                                    color: AppColors.error,
                                    onPressed: () {
                                      setState(() {
                                        _items[index].dispose();
                                        _items.removeAt(index);
                                      });
                                    },
                                  ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            TextFormField(
                              controller: item.descriptionController,
                              decoration: _inputDecoration(
                                label: isSwahili
                                    ? 'Maelezo *'
                                    : 'Description *',
                                isDarkMode: isDarkMode,
                              ),
                              validator: (v) => v == null || v.trim().isEmpty
                                  ? (isSwahili ? 'Inahitajika' : 'Required')
                                  : null,
                            ),
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                Expanded(
                                  child: TextFormField(
                                    controller: item.quantityController,
                                    keyboardType:
                                        const TextInputType.numberWithOptions(
                                          decimal: true,
                                        ),
                                    decoration: _inputDecoration(
                                      label: isSwahili ? 'Kiasi *' : 'Qty *',
                                      isDarkMode: isDarkMode,
                                    ),
                                    validator: (v) {
                                      if (v == null || v.trim().isEmpty) {
                                        return isSwahili
                                            ? 'Inahitajika'
                                            : 'Required';
                                      }
                                      final qty = double.tryParse(v);
                                      if (qty == null || qty <= 0) {
                                        return isSwahili
                                            ? 'Nambari batili'
                                            : 'Invalid';
                                      }
                                      return null;
                                    },
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: TextFormField(
                                    controller: item.unitController,
                                    decoration: _inputDecoration(
                                      label: isSwahili ? 'Unit' : 'Unit',
                                      isDarkMode: isDarkMode,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            TextFormField(
                              controller: item.unitPriceController,
                              keyboardType:
                                  const TextInputType.numberWithOptions(
                                    decimal: true,
                                  ),
                              decoration: _inputDecoration(
                                label: isSwahili ? 'Bei *' : 'Price *',
                                isDarkMode: isDarkMode,
                              ),
                              validator: (v) {
                                if (v == null || v.trim().isEmpty) {
                                  return isSwahili ? 'Inahitajika' : 'Required';
                                }
                                return null;
                              },
                            ),
                          ],
                        ),
                      );
                    }),
                    const SizedBox(height: 6),
                    _buildTextField(
                      controller: _notesController,
                      label: isSwahili ? 'Notes' : 'Notes',
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
                          : Text(
                              widget.invoice == null
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

  InputDecoration _inputDecoration({
    required String label,
    required bool isDarkMode,
  }) {
    return InputDecoration(
      labelText: label,
      filled: true,
      fillColor: isDarkMode
          ? Colors.white.withValues(alpha: 0.05)
          : Colors.grey.withValues(alpha: 0.08),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide.none,
      ),
    );
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
      'notes': _blankToNull(_notesController.text),
      'items': items,
    };

    setState(() => _saving = true);
    try {
      final api = ref.read(apiClientProvider);
      final invoiceId = _toInt(widget.invoice?['id']);
      if (invoiceId > 0) {
        await api.put('/billing/documents/$invoiceId', data: payload);
      } else {
        await api.post('/billing/documents', data: payload);
      }

      if (!mounted) return;
      Navigator.of(context).pop(true);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          backgroundColor: AppColors.success,
          content: Text(
            invoiceId > 0
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

class _InvoiceItemState {
  final TextEditingController descriptionController;
  final TextEditingController quantityController;
  final TextEditingController unitController;
  final TextEditingController unitPriceController;

  _InvoiceItemState({
    required this.descriptionController,
    required this.quantityController,
    required this.unitController,
    required this.unitPriceController,
  });

  factory _InvoiceItemState.empty() {
    return _InvoiceItemState(
      descriptionController: TextEditingController(),
      quantityController: TextEditingController(text: '1'),
      unitController: TextEditingController(),
      unitPriceController: TextEditingController(),
    );
  }

  factory _InvoiceItemState.fromMap(Map<String, dynamic> map) {
    return _InvoiceItemState(
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
    );
  }

  Map<String, dynamic> toPayload() {
    return {
      'description': descriptionController.text.trim(),
      'quantity': _toDouble(quantityController.text),
      'unit': _blankToNull(unitController.text),
      'unit_price': _toDouble(unitPriceController.text),
    };
  }

  void dispose() {
    descriptionController.dispose();
    quantityController.dispose();
    unitController.dispose();
    unitPriceController.dispose();
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
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide.none,
      ),
    ),
    validator: isRequired
        ? (value) {
            if ((value ?? '').trim().isEmpty) {
              return 'Required';
            }
            return null;
          }
        : null,
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
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide.none,
      ),
    ),
    onTap: () async {
      final date = await showDatePicker(
        context: context,
        initialDate: DateTime.now(),
        firstDate: DateTime(2020),
        lastDate: DateTime(2100),
      );
      if (date != null) {
        controller.text =
            '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
      }
    },
    validator: isRequired
        ? (value) {
            if ((value ?? '').trim().isEmpty) {
              return 'Required';
            }
            return null;
          }
        : null,
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
        borderRadius: BorderRadius.circular(12),
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

String _text(dynamic value) => value?.toString() ?? '';

String _money(dynamic value) {
  final amount = value is num
      ? value.toDouble()
      : (double.tryParse(value?.toString() ?? '') ?? 0);
  return 'TZS ${amount.toStringAsFixed(2).replaceAllMapped(RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'), (m) => '${m[1]},')}';
}

String _decimalText(dynamic value) {
  if (value == null) return '';
  if (value is num) return value.toString();
  return value.toString();
}

int _toInt(dynamic value) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value) ?? 0;
  return 0;
}

int? _toNullableInt(dynamic value) {
  if (value == null) return null;
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value);
  return null;
}

String? _blankToNull(String? value) {
  if (value == null || value.trim().isEmpty) return null;
  return value.trim();
}

double _toDouble(String? value) {
  if (value == null || value.trim().isEmpty) return 0;
  return double.tryParse(value) ?? 0;
}

String _dateValue(dynamic value) {
  if (value == null) return '';
  return value.toString();
}
