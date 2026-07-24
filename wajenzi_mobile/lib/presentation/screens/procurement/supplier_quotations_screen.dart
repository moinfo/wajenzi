import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/config/theme_config.dart';
import '../../../core/router/app_router.dart';
import '../../../data/models/procurement/quotation_models.dart';
import '../../../data/repositories/quotations_repository.dart';
import '../../providers/settings_provider.dart';
import 'procurement_shared.dart';

final _searchProvider = StateProvider.autoDispose<String>((_) => '');
final _statusFilterProvider = StateProvider.autoDispose<String?>((_) => null);

final _quotationsProvider =
    FutureProvider.autoDispose<List<SupplierQuotationDto>>((ref) async {
  final repo = ref.watch(quotationsRepositoryProvider);
  final search = ref.watch(_searchProvider);
  final status = ref.watch(_statusFilterProvider);
  return repo.quotations(search: search, status: status);
});

final _referenceDataProvider =
    FutureProvider.autoDispose<QuotationReferenceData>((ref) async {
  return ref.watch(quotationsRepositoryProvider).referenceData();
});

class SupplierQuotationsScreen extends ConsumerWidget {
  const SupplierQuotationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final rootScaffoldKey = ref.read(rootScaffoldKeyProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    final quotationsAsync = ref.watch(_quotationsProvider);
    final search = ref.watch(_searchProvider);
    final status = ref.watch(_statusFilterProvider);
    final canAdd = hasPermission(ref, 'Add Supplier Quotation');

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.menu_rounded),
          onPressed: () => rootScaffoldKey.currentState?.openDrawer(),
        ),
        title: Text(isSwahili ? 'Nukuu za Wasambazaji' : 'Supplier Quotations'),
      ),
      floatingActionButton: canAdd
          ? Padding(
              padding: const EdgeInsets.only(bottom: 72),
              child: FloatingActionButton.extended(
                onPressed: () => _openForm(context, ref, isSwahili),
                icon: const Icon(Icons.add),
                label: Text(isSwahili ? 'Nukuu Mpya' : 'New Quote'),
              ),
            )
          : null,
      body: Column(
        children: [
          _SearchAndFilter(
            search: search,
            status: status,
            isSwahili: isSwahili,
            isDarkMode: isDarkMode,
            onSearchChange: (v) => ref.read(_searchProvider.notifier).state = v,
            onStatusChange: (v) =>
                ref.read(_statusFilterProvider.notifier).state = v,
          ),
          Expanded(
            child: RefreshIndicator(
              onRefresh: () async {
                ref.invalidate(_quotationsProvider);
                await ref.read(_quotationsProvider.future);
              },
              child: quotationsAsync.when(
                loading: () =>
                    const Center(child: CircularProgressIndicator()),
                error: (e, _) => _ErrorView(
                  error: e,
                  isSwahili: isSwahili,
                  onRetry: () => ref.invalidate(_quotationsProvider),
                ),
                data: (quotations) {
                  if (quotations.isEmpty) {
                    return _EmptyView(isSwahili: isSwahili);
                  }
                  final groups = _groupByRequest(quotations);
                  return ListView.builder(
                    padding: const EdgeInsets.fromLTRB(12, 8, 12, 140),
                    itemCount: groups.length,
                    itemBuilder: (context, index) {
                      return _GroupCard(
                        quotations: groups[index],
                        isSwahili: isSwahili,
                        isDarkMode: isDarkMode,
                      );
                    },
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }

  List<List<SupplierQuotationDto>> _groupByRequest(
      List<SupplierQuotationDto> quotations) {
    final map = <int, List<SupplierQuotationDto>>{};
    for (final q in quotations) {
      map.putIfAbsent(q.materialRequestId ?? 0, () => []).add(q);
    }
    final groups = map.values.toList();
    for (final g in groups) {
      g.sort((a, b) => a.grandTotal.compareTo(b.grandTotal));
    }
    return groups;
  }

  static void _openForm(
    BuildContext context,
    WidgetRef ref,
    bool isSwahili, {
    SupplierQuotationDto? existing,
  }) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => DraggableScrollableSheet(
        initialChildSize: 0.92,
        minChildSize: 0.5,
        maxChildSize: 0.95,
        expand: false,
        builder: (_, controller) => ClipRRect(
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          child: Material(
            color: Theme.of(context).scaffoldBackgroundColor,
            child: _QuotationFormSheet(
              scrollController: controller,
              existing: existing,
            ),
          ),
        ),
      ),
    ).then((saved) {
      if (saved == true) ref.invalidate(_quotationsProvider);
    });
  }
}

// ── Search + filter ──────────────────────────────────────────────────────────
class _SearchAndFilter extends StatelessWidget {
  final String search;
  final String? status;
  final bool isSwahili;
  final bool isDarkMode;
  final ValueChanged<String> onSearchChange;
  final ValueChanged<String?> onStatusChange;

  const _SearchAndFilter({
    required this.search,
    required this.status,
    required this.isSwahili,
    required this.isDarkMode,
    required this.onSearchChange,
    required this.onStatusChange,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1A1A2E) : Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          TextField(
            decoration: InputDecoration(
              hintText: isSwahili
                  ? 'Tafuta nambari, msambazaji…'
                  : 'Search number, supplier…',
              prefixIcon: const Icon(Icons.search),
              suffixIcon: search.isNotEmpty
                  ? IconButton(
                      icon: const Icon(Icons.clear),
                      onPressed: () => onSearchChange(''),
                    )
                  : null,
              filled: true,
              fillColor: isDarkMode ? const Color(0xFF2A2A3E) : Colors.grey[100],
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide.none,
              ),
            ),
            onChanged: onSearchChange,
          ),
          const SizedBox(height: 12),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                _StatusChip(
                  label: isSwahili ? 'Zote' : 'All',
                  selected: status == null,
                  onTap: () => onStatusChange(null),
                  color: Colors.grey,
                ),
                const SizedBox(width: 8),
                _StatusChip(
                  label: isSwahili ? 'Zilizopokelewa' : 'Received',
                  selected: status == 'received',
                  onTap: () => onStatusChange('received'),
                  color: AppColors.warning,
                ),
                const SizedBox(width: 8),
                _StatusChip(
                  label: isSwahili ? 'Zilizochaguliwa' : 'Selected',
                  selected: status == 'selected',
                  onTap: () => onStatusChange('selected'),
                  color: AppColors.success,
                ),
                const SizedBox(width: 8),
                _StatusChip(
                  label: isSwahili ? 'Zilizokataliwa' : 'Rejected',
                  selected: status == 'rejected',
                  onTap: () => onStatusChange('rejected'),
                  color: AppColors.error,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _StatusChip extends StatelessWidget {
  final String label;
  final bool selected;
  final VoidCallback onTap;
  final Color color;

  const _StatusChip({
    required this.label,
    required this.selected,
    required this.onTap,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: selected ? color.withValues(alpha: 0.18) : Colors.transparent,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: selected ? color : Colors.grey.withValues(alpha: 0.4),
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: selected ? color : Colors.grey,
            fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
            fontSize: 13,
          ),
        ),
      ),
    );
  }
}

// ── Group card ───────────────────────────────────────────────────────────────
class _GroupCard extends ConsumerWidget {
  final List<SupplierQuotationDto> quotations;
  final bool isSwahili;
  final bool isDarkMode;

  const _GroupCard({
    required this.quotations,
    required this.isSwahili,
    required this.isDarkMode,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final head = quotations.first;
    final count = quotations.length;
    const minRequired = 3;
    final progress = (count / minRequired).clamp(0.0, 1.0);
    final canCompare = count >= minRequired &&
        !quotations.any((q) => q.isSelected) &&
        hasAnyPermission(ref, ['Add Quotation Comparison', 'Quotation Comparisons']);
    final lowest =
        quotations.map((q) => q.grandTotal).reduce((a, b) => a < b ? a : b);

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: isDarkMode ? const Color(0xFF1E1E32) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.grey.withValues(alpha: 0.18)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 14, 14, 8),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        head.materialRequestNumber.isEmpty
                            ? (isSwahili ? 'Ombi lisilojulikana' : 'Unlinked request')
                            : head.materialRequestNumber,
                        style: const TextStyle(
                            fontSize: 15, fontWeight: FontWeight.w700),
                      ),
                    ),
                    Text(
                      '$count / $minRequired',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: count >= minRequired
                            ? AppColors.success
                            : AppColors.warning,
                      ),
                    ),
                  ],
                ),
                if (head.projectName.isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 2),
                    child: Text(
                      head.projectName,
                      style: TextStyle(
                          fontSize: 12, color: AppColors.textSecondary),
                    ),
                  ),
                const SizedBox(height: 8),
                ClipRRect(
                  borderRadius: BorderRadius.circular(4),
                  child: LinearProgressIndicator(
                    value: progress,
                    minHeight: 5,
                    backgroundColor: Colors.grey.withValues(alpha: 0.2),
                    valueColor: AlwaysStoppedAnimation(
                      count >= minRequired
                          ? AppColors.success
                          : AppColors.warning,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1),
          ...quotations.map((q) => _QuotationRow(
                quotation: q,
                isLowest: q.grandTotal <= lowest,
                isSwahili: isSwahili,
              )),
          if (canCompare)
            Padding(
              padding: const EdgeInsets.fromLTRB(8, 0, 8, 6),
              child: Align(
                alignment: Alignment.centerRight,
                child: TextButton.icon(
                  onPressed: () => context.push(
                      '/quotation-comparison-create/${head.materialRequestId}'),
                  icon: const Icon(Icons.compare_arrows, size: 18),
                  label: Text(isSwahili ? 'Linganisha' : 'Compare'),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _QuotationRow extends ConsumerWidget {
  final SupplierQuotationDto quotation;
  final bool isLowest;
  final bool isSwahili;

  const _QuotationRow({
    required this.quotation,
    required this.isLowest,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return InkWell(
      onTap: () => _openDetail(context, ref),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Flexible(
                        child: Text(
                          quotation.supplierName.isEmpty
                              ? quotation.quotationNumber
                              : quotation.supplierName,
                          style: const TextStyle(
                              fontSize: 14, fontWeight: FontWeight.w600),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      if (isLowest) ...[
                        const SizedBox(width: 6),
                        _MiniBadge(
                          label: isSwahili ? 'CHINI' : 'LOWEST',
                          color: AppColors.success,
                        ),
                      ],
                      if (quotation.isExpired) ...[
                        const SizedBox(width: 6),
                        _MiniBadge(
                          label: isSwahili ? 'IMEISHA' : 'EXPIRED',
                          color: AppColors.error,
                        ),
                      ],
                    ],
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '${quotation.quotationNumber} • ${fmtDate(quotation.quotationDate)}',
                    style: TextStyle(
                        fontSize: 11, color: AppColors.textSecondary),
                  ),
                ],
              ),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  fmtMoney(quotation.grandTotal),
                  style: const TextStyle(
                      fontSize: 14, fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 3),
                ProcurementStatusChip(status: quotation.status),
              ],
            ),
          ],
        ),
      ),
    );
  }

  void _openDetail(BuildContext context, WidgetRef ref) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => DraggableScrollableSheet(
        initialChildSize: 0.7,
        minChildSize: 0.4,
        maxChildSize: 0.95,
        expand: false,
        builder: (_, controller) => ClipRRect(
          borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
          child: Material(
            color: Theme.of(context).scaffoldBackgroundColor,
            child: _QuotationDetailSheet(
              quotationId: quotation.id,
              scrollController: controller,
            ),
          ),
        ),
      ),
    );
  }
}

class _MiniBadge extends StatelessWidget {
  final String label;
  final Color color;
  const _MiniBadge({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        label,
        style: TextStyle(
            fontSize: 9, fontWeight: FontWeight.w800, color: color),
      ),
    );
  }
}

// ── Detail sheet ─────────────────────────────────────────────────────────────
final _quotationDetailProvider = FutureProvider.autoDispose
    .family<SupplierQuotationDto, int>((ref, id) async {
  return ref.watch(quotationsRepositoryProvider).quotation(id);
});

class _QuotationDetailSheet extends ConsumerWidget {
  final int quotationId;
  final ScrollController scrollController;

  const _QuotationDetailSheet({
    required this.quotationId,
    required this.scrollController,
  });

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final async = ref.watch(_quotationDetailProvider(quotationId));

    return async.when(
      loading: () => const SizedBox(
        height: 240,
        child: Center(child: CircularProgressIndicator()),
      ),
      error: (e, _) => Padding(
        padding: const EdgeInsets.all(24),
        child: Text(procurementErrorMessage(e)),
      ),
      data: (q) {
        final editable = q.status == 'received';
        final canEdit = editable && hasPermission(ref, 'Edit Supplier Quotation');
        final canDelete =
            editable && hasPermission(ref, 'Delete Supplier Quotation');
        return ListView(
          controller: scrollController,
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey.withValues(alpha: 0.4),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: Text(
                    q.quotationNumber,
                    style: const TextStyle(
                        fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                ),
                ProcurementStatusChip(status: q.status),
              ],
            ),
            const SizedBox(height: 4),
            Text(q.supplierName,
                style: TextStyle(color: AppColors.textSecondary)),
            const Divider(height: 28),
            _kv(isSwahili ? 'Ombi' : 'Request', q.materialRequestNumber),
            _kv(isSwahili ? 'Mradi' : 'Project', q.projectName),
            _kv(isSwahili ? 'Tarehe' : 'Date', fmtDate(q.quotationDate)),
            _kv(isSwahili ? 'Halali hadi' : 'Valid until',
                q.validUntil == null ? '-' : fmtDate(q.validUntil)),
            _kv(isSwahili ? 'Siku za usambazaji' : 'Delivery days',
                q.deliveryTimeDays?.toString() ?? '-'),
            _kv(isSwahili ? 'Masharti ya malipo' : 'Payment terms',
                q.paymentTerms ?? '-'),
            const Divider(height: 28),
            _kv(isSwahili ? 'Jumla ndogo' : 'Subtotal', fmtMoney(q.totalAmount)),
            _kv('VAT', fmtMoney(q.vatAmount)),
            _kv(isSwahili ? 'Jumla kuu' : 'Grand total', fmtMoney(q.grandTotal),
                bold: true),
            const SizedBox(height: 12),
            Text(
              isSwahili ? 'Bidhaa' : 'Items',
              style: const TextStyle(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 6),
            ...q.items.map((it) => Padding(
                  padding: const EdgeInsets.symmetric(vertical: 4),
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          it.description.isEmpty
                              ? (isSwahili ? 'Bidhaa' : 'Item')
                              : it.description,
                          style: const TextStyle(fontSize: 13),
                        ),
                      ),
                      Text(
                        '${fmtQty(it.quantity)} ${it.unit ?? ''} × ${fmtMoney(it.unitPrice)}',
                        style: TextStyle(
                            fontSize: 12, color: AppColors.textSecondary),
                      ),
                      const SizedBox(width: 8),
                      Text(fmtMoney(it.totalPrice),
                          style: const TextStyle(
                              fontSize: 13, fontWeight: FontWeight.w600)),
                    ],
                  ),
                )),
            if (q.notes != null && q.notes!.isNotEmpty) ...[
              const Divider(height: 28),
              Text(isSwahili ? 'Maelezo' : 'Notes',
                  style: const TextStyle(fontWeight: FontWeight.w700)),
              const SizedBox(height: 4),
              Text(q.notes!),
            ],
            const SizedBox(height: 20),
            if (canEdit || canDelete)
              Row(
                children: [
                  if (canEdit)
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () {
                          Navigator.of(context).pop();
                          SupplierQuotationsScreen._openForm(
                              context, ref, isSwahili,
                              existing: q);
                        },
                        icon: const Icon(Icons.edit_outlined, size: 18),
                        label: Text(isSwahili ? 'Hariri' : 'Edit'),
                      ),
                    ),
                  if (canEdit && canDelete) const SizedBox(width: 12),
                  if (canDelete)
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () => _confirmDelete(context, ref, q, isSwahili),
                        style: OutlinedButton.styleFrom(
                            foregroundColor: AppColors.error),
                        icon: const Icon(Icons.delete_outline, size: 18),
                        label: Text(isSwahili ? 'Futa' : 'Delete'),
                      ),
                    ),
                ],
              ),
          ],
        );
      },
    );
  }

  Widget _kv(String k, String v, {bool bold = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 3),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 140,
            child: Text(k,
                style: TextStyle(
                    fontSize: 13, color: AppColors.textSecondary)),
          ),
          Expanded(
            child: Text(
              v,
              style: TextStyle(
                fontSize: 13,
                fontWeight: bold ? FontWeight.w800 : FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _confirmDelete(
    BuildContext context,
    WidgetRef ref,
    SupplierQuotationDto q,
    bool isSwahili,
  ) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isSwahili ? 'Futa Nukuu' : 'Delete Quotation'),
        content: Text(isSwahili
            ? 'Una uhakika unataka kufuta nukuu hii?'
            : 'Are you sure you want to delete this quotation?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: Text(isSwahili ? 'Ghairi' : 'Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.error,
                foregroundColor: Colors.white),
            onPressed: () => Navigator.of(ctx).pop(true),
            child: Text(isSwahili ? 'Futa' : 'Delete'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;
    try {
      await ref.read(quotationsRepositoryProvider).deleteQuotation(q.id);
      ref.invalidate(_quotationsProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(isSwahili ? 'Imefutwa' : 'Quotation deleted'),
            backgroundColor: AppColors.success,
          ),
        );
        Navigator.of(context).pop();
      }
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(procurementErrorMessage(e)),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }
}

// ── Create / edit form ───────────────────────────────────────────────────────
class _LineState {
  final int mrItemId;
  final int? boqItemId;
  final String description;
  final double quantity;
  final String? unit;
  final TextEditingController price;

  _LineState({
    required this.mrItemId,
    required this.boqItemId,
    required this.description,
    required this.quantity,
    required this.unit,
    required this.price,
  });
}

class _QuotationFormSheet extends ConsumerStatefulWidget {
  final ScrollController scrollController;
  final SupplierQuotationDto? existing;

  const _QuotationFormSheet({
    required this.scrollController,
    this.existing,
  });

  @override
  ConsumerState<_QuotationFormSheet> createState() =>
      _QuotationFormSheetState();
}

class _QuotationFormSheetState extends ConsumerState<_QuotationFormSheet> {
  ReferenceMaterialRequestDto? _selectedMr;
  int? _supplierId;
  DateTime _quotationDate = DateTime.now();
  DateTime? _validUntil = DateTime.now().add(const Duration(days: 30));
  final _deliveryCtrl = TextEditingController(text: '7');
  final _paymentCtrl = TextEditingController();
  final _notesCtrl = TextEditingController();
  final _vatRateCtrl = TextEditingController(text: '18');
  bool _applyVat = true;
  List<_LineState> _lines = [];
  String? _filePath;
  String? _fileName;
  bool _submitting = false;

  bool get _isEdit => widget.existing != null;

  @override
  void initState() {
    super.initState();
    if (_isEdit) {
      final q = widget.existing!;
      _supplierId = q.supplierId;
      _quotationDate = DateTime.tryParse(q.quotationDate ?? '') ?? DateTime.now();
      _validUntil =
          q.validUntil == null ? null : DateTime.tryParse(q.validUntil!);
      _deliveryCtrl.text = q.deliveryTimeDays?.toString() ?? '';
      _paymentCtrl.text = q.paymentTerms ?? '';
      _notesCtrl.text = q.notes ?? '';
      _applyVat = q.vatAmount > 0;
      if (q.totalAmount > 0 && q.vatAmount > 0) {
        _vatRateCtrl.text =
            (q.vatAmount / q.totalAmount * 100).toStringAsFixed(0);
      }
      _lines = q.items
          .map((it) => _LineState(
                mrItemId: it.materialRequestItemId ?? 0,
                boqItemId: it.boqItemId,
                description: it.description,
                quantity: it.quantity,
                unit: it.unit,
                price: TextEditingController(
                    text: it.unitPrice == 0 ? '' : it.unitPrice.toString()),
              ))
          .toList();
    }
  }

  @override
  void dispose() {
    _deliveryCtrl.dispose();
    _paymentCtrl.dispose();
    _notesCtrl.dispose();
    _vatRateCtrl.dispose();
    for (final l in _lines) {
      l.price.dispose();
    }
    super.dispose();
  }

  void _applyMr(ReferenceMaterialRequestDto mr) {
    for (final l in _lines) {
      l.price.dispose();
    }
    setState(() {
      _selectedMr = mr;
      _lines = mr.items
          .map((it) => _LineState(
                mrItemId: it.id,
                boqItemId: it.boqItemId,
                description: it.description,
                quantity: it.effectiveQuantity,
                unit: it.unit,
                price: TextEditingController(),
              ))
          .toList();
    });
  }

  double get _subtotal {
    var sum = 0.0;
    for (final l in _lines) {
      final price = double.tryParse(l.price.text) ?? 0;
      sum += price * l.quantity;
    }
    return sum;
  }

  double get _vatAmount {
    if (!_applyVat) return 0;
    final rate = double.tryParse(_vatRateCtrl.text) ?? 0;
    return _subtotal * rate / 100;
  }

  @override
  Widget build(BuildContext context) {
    final isSwahili = ref.watch(isSwahiliProvider);
    final refDataAsync = ref.watch(_referenceDataProvider);

    return refDataAsync.when(
      loading: () => const SizedBox(
        height: 300,
        child: Center(child: CircularProgressIndicator()),
      ),
      error: (e, _) => Padding(
        padding: const EdgeInsets.all(24),
        child: Text(procurementErrorMessage(e)),
      ),
      data: (refData) {
        return ListView(
          controller: widget.scrollController,
          padding: EdgeInsets.fromLTRB(
              20, 16, 20, MediaQuery.of(context).viewInsets.bottom + 24),
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey.withValues(alpha: 0.4),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 14),
            Text(
              _isEdit
                  ? (isSwahili ? 'Hariri Nukuu' : 'Edit Quotation')
                  : (isSwahili ? 'Nukuu Mpya' : 'New Quotation'),
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 16),
            if (_isEdit)
              _readonlyField(
                isSwahili ? 'Ombi' : 'Material Request',
                widget.existing!.materialRequestNumber,
              )
            else
              DropdownButtonFormField<int>(
                initialValue: _selectedMr?.id,
                isExpanded: true,
                decoration: InputDecoration(
                  labelText: isSwahili ? 'Ombi la Vifaa' : 'Material Request',
                  border: const OutlineInputBorder(),
                ),
                items: refData.materialRequests
                    .map((mr) => DropdownMenuItem(
                          value: mr.id,
                          child: Text(
                            '${mr.requestNumber} — ${mr.projectName}',
                            overflow: TextOverflow.ellipsis,
                          ),
                        ))
                    .toList(),
                onChanged: (id) {
                  final mr = refData.materialRequests
                      .firstWhere((m) => m.id == id);
                  _applyMr(mr);
                },
              ),
            const SizedBox(height: 12),
            if (_isEdit)
              _readonlyField(isSwahili ? 'Msambazaji' : 'Supplier',
                  widget.existing!.supplierName)
            else
              DropdownButtonFormField<int>(
                initialValue: _supplierId,
                isExpanded: true,
                decoration: InputDecoration(
                  labelText: isSwahili ? 'Msambazaji' : 'Supplier',
                  border: const OutlineInputBorder(),
                ),
                items: refData.suppliers
                    .map((s) => DropdownMenuItem(
                          value: s.id,
                          child: Text(s.name, overflow: TextOverflow.ellipsis),
                        ))
                    .toList(),
                onChanged: (id) => setState(() => _supplierId = id),
              ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: _dateField(
                    label: isSwahili ? 'Tarehe' : 'Quote date',
                    value: _quotationDate,
                    onPick: (d) => setState(() => _quotationDate = d),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _dateField(
                    label: isSwahili ? 'Halali hadi' : 'Valid until',
                    value: _validUntil,
                    onPick: (d) => setState(() => _validUntil = d),
                    clearable: true,
                    onClear: () => setState(() => _validUntil = null),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _deliveryCtrl,
                    keyboardType: TextInputType.number,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Siku za usambazaji' : 'Delivery days',
                      border: const OutlineInputBorder(),
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: TextField(
                    controller: _paymentCtrl,
                    decoration: InputDecoration(
                      labelText: isSwahili ? 'Masharti ya malipo' : 'Payment terms',
                      border: const OutlineInputBorder(),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 18),
            Text(
              isSwahili ? 'Bei za Bidhaa' : 'Item Prices',
              style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
            ),
            const SizedBox(height: 8),
            if (_lines.isEmpty)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 12),
                child: Text(
                  isSwahili
                      ? 'Chagua ombi ili kuonyesha bidhaa.'
                      : 'Select a request to load items.',
                  style: TextStyle(color: AppColors.textSecondary),
                ),
              ),
            ..._lines.map(_lineRow),
            const SizedBox(height: 12),
            Row(
              children: [
                Checkbox(
                  value: _applyVat,
                  onChanged: (v) => setState(() => _applyVat = v ?? false),
                ),
                Text(isSwahili ? 'Weka VAT' : 'Apply VAT'),
                const SizedBox(width: 8),
                if (_applyVat)
                  SizedBox(
                    width: 90,
                    child: TextField(
                      controller: _vatRateCtrl,
                      keyboardType: TextInputType.number,
                      onChanged: (_) => setState(() {}),
                      decoration: const InputDecoration(
                        labelText: 'VAT %',
                        isDense: true,
                        border: OutlineInputBorder(),
                      ),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            _totalRow(isSwahili ? 'Jumla ndogo' : 'Subtotal', _subtotal),
            _totalRow('VAT', _vatAmount),
            _totalRow(isSwahili ? 'Jumla kuu' : 'Grand total',
                _subtotal + _vatAmount,
                bold: true),
            const SizedBox(height: 14),
            OutlinedButton.icon(
              onPressed: _pickFile,
              icon: const Icon(Icons.attach_file, size: 18),
              label: Text(
                _fileName ??
                    (isSwahili ? 'Ambatanisha faili (hiari)' : 'Attach file (optional)'),
                overflow: TextOverflow.ellipsis,
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _notesCtrl,
              maxLines: 2,
              decoration: InputDecoration(
                labelText: isSwahili ? 'Maelezo (hiari)' : 'Notes (optional)',
                border: const OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 20),
            SizedBox(
              height: 48,
              child: ElevatedButton(
                onPressed: _submitting ? null : () => _submit(isSwahili),
                child: _submitting
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : Text(_isEdit
                        ? (isSwahili ? 'Hifadhi Mabadiliko' : 'Save Changes')
                        : (isSwahili ? 'Tuma Nukuu' : 'Submit Quotation')),
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _readonlyField(String label, String value) {
    return InputDecorator(
      decoration: InputDecoration(
        labelText: label,
        border: const OutlineInputBorder(),
      ),
      child: Text(value.isEmpty ? '-' : value),
    );
  }

  Widget _lineRow(_LineState line) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 5),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  line.description.isEmpty ? 'Item' : line.description,
                  style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                ),
                Text(
                  '${fmtQty(line.quantity)} ${line.unit ?? ''}',
                  style: TextStyle(fontSize: 11, color: AppColors.textSecondary),
                ),
              ],
            ),
          ),
          const SizedBox(width: 8),
          SizedBox(
            width: 110,
            child: TextField(
              controller: line.price,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              onChanged: (_) => setState(() {}),
              decoration: const InputDecoration(
                labelText: 'Unit price',
                isDense: true,
                border: OutlineInputBorder(),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _dateField({
    required String label,
    required DateTime? value,
    required ValueChanged<DateTime> onPick,
    bool clearable = false,
    VoidCallback? onClear,
  }) {
    return InkWell(
      onTap: () async {
        final picked = await showDatePicker(
          context: context,
          initialDate: value ?? DateTime.now(),
          firstDate: DateTime(2020),
          lastDate: DateTime(2035),
        );
        if (picked != null) onPick(picked);
      },
      child: InputDecorator(
        decoration: InputDecoration(
          labelText: label,
          border: const OutlineInputBorder(),
          suffixIcon: clearable && value != null
              ? IconButton(
                  icon: const Icon(Icons.clear, size: 18),
                  onPressed: onClear,
                )
              : const Icon(Icons.calendar_today, size: 18),
        ),
        child: Text(value == null ? '-' : fmtDate(value)),
      ),
    );
  }

  Widget _totalRow(String label, double value, {bool bold = false}) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 2),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label,
              style: TextStyle(
                  fontSize: 13,
                  color: AppColors.textSecondary,
                  fontWeight: bold ? FontWeight.w700 : FontWeight.w400)),
          Text(fmtMoney(value),
              style: TextStyle(
                  fontSize: 14,
                  fontWeight: bold ? FontWeight.w800 : FontWeight.w600)),
        ],
      ),
    );
  }

  Future<void> _pickFile() async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'],
    );
    if (result != null && result.files.single.path != null) {
      setState(() {
        _filePath = result.files.single.path;
        _fileName = result.files.single.name;
      });
    }
  }

  String _fmtDateApi(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  Future<void> _submit(bool isSwahili) async {
    final mrId = _isEdit ? widget.existing!.materialRequestId : _selectedMr?.id;
    final supplierId = _isEdit ? widget.existing!.supplierId : _supplierId;

    if (mrId == null || supplierId == null) {
      _snack(isSwahili ? 'Chagua ombi na msambazaji.' : 'Select request and supplier.',
          AppColors.error);
      return;
    }
    if (_lines.isEmpty) {
      _snack(isSwahili ? 'Hakuna bidhaa.' : 'No items to quote.', AppColors.error);
      return;
    }
    final hasPrice = _lines.any((l) => (double.tryParse(l.price.text) ?? 0) > 0);
    if (!hasPrice) {
      _snack(isSwahili ? 'Weka bei angalau moja.' : 'Enter at least one price.',
          AppColors.error);
      return;
    }

    final items = _lines
        .map((l) => {
              'material_request_item_id': l.mrItemId,
              'boq_item_id': l.boqItemId,
              'description': l.description,
              'quantity': l.quantity,
              'unit': l.unit,
              'unit_price': double.tryParse(l.price.text) ?? 0,
            })
        .toList();

    final body = <String, dynamic>{
      'material_request_id': mrId,
      'supplier_id': supplierId,
      'quotation_date': _fmtDateApi(_quotationDate),
      if (_validUntil != null) 'valid_until': _fmtDateApi(_validUntil!),
      if (_deliveryCtrl.text.trim().isNotEmpty)
        'delivery_time_days': int.tryParse(_deliveryCtrl.text.trim()) ?? 0,
      if (_paymentCtrl.text.trim().isNotEmpty)
        'payment_terms': _paymentCtrl.text.trim(),
      'vat_amount': _vatAmount,
      if (_notesCtrl.text.trim().isNotEmpty) 'notes': _notesCtrl.text.trim(),
      'items': items,
    };

    setState(() => _submitting = true);
    try {
      final repo = ref.read(quotationsRepositoryProvider);
      if (_isEdit) {
        await repo.updateQuotation(widget.existing!.id, body, filePath: _filePath);
      } else {
        await repo.createQuotation(body, filePath: _filePath);
      }
      if (mounted) {
        Navigator.of(context).pop(true);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_isEdit
                ? (isSwahili ? 'Imehifadhiwa' : 'Quotation updated')
                : (isSwahili ? 'Nukuu imeongezwa' : 'Quotation created')),
            backgroundColor: AppColors.success,
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        setState(() => _submitting = false);
        _snack(procurementErrorMessage(e), AppColors.error);
      }
    }
  }

  void _snack(String msg, Color color) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), backgroundColor: color),
    );
  }
}

// ── Shared small widgets ─────────────────────────────────────────────────────
class _EmptyView extends StatelessWidget {
  final bool isSwahili;
  const _EmptyView({required this.isSwahili});

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 120),
        Center(child: Icon(Icons.request_quote_outlined, size: 64, color: AppColors.textHint)),
        const SizedBox(height: 12),
        Center(
          child: Text(
            isSwahili ? 'Hakuna nukuu bado' : 'No quotations yet',
            style: TextStyle(color: AppColors.textSecondary),
          ),
        ),
      ],
    );
  }
}

class _ErrorView extends StatelessWidget {
  final Object error;
  final bool isSwahili;
  final VoidCallback onRetry;

  const _ErrorView({
    required this.error,
    required this.isSwahili,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 120),
        Center(child: Icon(Icons.error_outline, size: 56, color: AppColors.error)),
        const SizedBox(height: 12),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 32),
          child: Text(
            procurementErrorMessage(error),
            textAlign: TextAlign.center,
            style: TextStyle(color: AppColors.textSecondary),
          ),
        ),
        const SizedBox(height: 16),
        Center(
          child: OutlinedButton(
            onPressed: onRetry,
            child: Text(isSwahili ? 'Jaribu tena' : 'Retry'),
          ),
        ),
      ],
    );
  }
}
