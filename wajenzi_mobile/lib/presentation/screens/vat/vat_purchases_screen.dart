import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import 'vat_shared.dart';

final _startProvider = StateProvider.autoDispose<DateTime>(
  (ref) => DateTime(DateTime.now().year, DateTime.now().month, 1),
);
final _endProvider = StateProvider.autoDispose<DateTime>(
  (ref) => DateTime.now(),
);

final _dataProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final s = ref.watch(_startProvider);
  final e = ref.watch(_endProvider);
  final resp = await api.get(
    '/vat/purchases',
    queryParameters: {'start_date': vatDateFmt(s), 'end_date': vatDateFmt(e)},
  );
  return resp.data['data'] as Map<String, dynamic>;
});

class VatPurchasesScreen extends ConsumerWidget {
  const VatPurchasesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isDark = ref.watch(isDarkModeProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final dataAsync = ref.watch(_dataProvider);
    final bottomPad = MediaQuery.of(context).padding.bottom + 90;

    return Scaffold(
      backgroundColor: isDark ? vatDarkBg : AppColors.background,
      appBar: AppBar(
        title: Text(isSwahili ? 'Manunuzi' : 'Purchases'),
        backgroundColor: isDark ? vatDarkCard : null,
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _showPurchaseForm(context, ref, isDark, isSwahili),
          child: const Icon(Icons.add),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_dataProvider.future),
        child: dataAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => VatErrorBody(
            onRetry: () => ref.invalidate(_dataProvider),
            isSwahili: isSwahili,
          ),
          data: (data) {
            final purchases =
                (data['purchases'] as List?)?.cast<Map<String, dynamic>>() ??
                [];
            final totals = data['totals'] as Map<String, dynamic>? ?? {};

            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: EdgeInsets.fromLTRB(16, 12, 16, bottomPad),
              children: [
                VatDateRangeBar(
                  startProvider: _startProvider,
                  endProvider: _endProvider,
                  isDark: isDark,
                  isSwahili: isSwahili,
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: VatSummaryChip(
                        label: 'Total',
                        value: vatMoney(totals['total_amount']),
                        color: vatAccentBlue,
                        isDark: isDark,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: VatSummaryChip(
                        label: 'VAT Exc.',
                        value: vatMoney(totals['amount_vat_exc']),
                        color: vatAccentTeal,
                        isDark: isDark,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: VatSummaryChip(
                        label: 'VAT',
                        value: vatMoney(totals['vat_amount']),
                        color: const Color(0xFFF59E0B),
                        isDark: isDark,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 14),
                VatCountBadge(
                  count: purchases.length,
                  label: isSwahili ? 'Manunuzi' : 'Purchases',
                  isDark: isDark,
                ),
                const SizedBox(height: 8),
                if (purchases.isEmpty)
                  VatEmptyState(isDark: isDark, isSwahili: isSwahili)
                else
                  ...purchases.map(
                    (p) => _PurchaseCard(
                      purchase: p,
                      isDark: isDark,
                      isSwahili: isSwahili,
                      onTap: () => _showPurchaseDetails(
                        context,
                        ref,
                        p,
                        isDark: isDark,
                        isSwahili: isSwahili,
                      ),
                      onEdit: () => _showPurchaseForm(
                        context,
                        ref,
                        isDark,
                        isSwahili,
                        purchase: p,
                      ),
                      onDelete: () async {
                        final ok = await vatDelete(
                          context,
                          ref,
                          '/vat/purchases',
                          p['id'],
                          isSwahili: isSwahili,
                        );
                        if (ok) ref.invalidate(_dataProvider);
                      },
                    ),
                  ),
              ],
            );
          },
        ),
      ),
    );
  }
}

Future<void> _showPurchaseDetails(
  BuildContext context,
  WidgetRef ref,
  Map<String, dynamic> purchase, {
  required bool isDark,
  required bool isSwahili,
}) async {
  var detail = purchase;

  try {
    final api = ref.read(apiClientProvider);
    final response = await api.get('/purchases/${purchase['id']}');
    final data = response.data['data'];
    if (data is Map<String, dynamic>) {
      detail = data;
    }
  } catch (_) {
    try {
      final api = ref.read(apiClientProvider);
      final response = await api.get('/vat/purchases/${purchase['id']}');
      final data = response.data['data'];
      if (data is Map<String, dynamic>) {
        detail = data;
      }
    } catch (_) {}
  }

  if (!context.mounted) return;

  final purchaseItems =
      (detail['purchase_items'] as List?)?.cast<Map<String, dynamic>>() ?? [];
  final approvalFlow =
      detail['approval_flow'] as Map<String, dynamic>? ?? const {};
  final approvalSteps =
      (approvalFlow['steps'] as List?)?.cast<Map<String, dynamic>>() ?? [];
  final itemsSubtotal = purchaseItems.fold<double>(
    0,
    (sum, item) => sum + _toDouble(item['total_price']),
  );

  showModalBottomSheet(
    context: context,
    backgroundColor: Colors.transparent,
    isScrollControlled: true,
    builder: (ctx) => Container(
      height: MediaQuery.of(context).size.height * 0.78,
      decoration: BoxDecoration(
        color: isDark ? vatDarkCard : Colors.white,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      child: SafeArea(
        top: false,
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                children: [
                  Container(
                    width: 42,
                    height: 4,
                    decoration: BoxDecoration(
                      color: isDark ? Colors.white24 : Colors.grey[300],
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          isSwahili ? 'Maelezo ya Ununuzi' : 'Purchase Details',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                            color: isDark
                                ? Colors.white
                                : AppColors.textPrimary,
                          ),
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.close),
                        onPressed: () => Navigator.pop(ctx),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                children: [
                  _VatSectionTitle(title: 'Project Details', isDark: isDark),
                  _VatDetailRow(
                    label: 'Supplier',
                    value:
                        ((detail['supplier']
                                    as Map<String, dynamic>?)?['name'] ??
                                detail['supplier_name'] ??
                                '-')
                            as String,
                    isDark: isDark,
                  ),
                  _VatDetailRow(
                    label: 'Supplier VRN',
                    value:
                        ((detail['supplier']
                                    as Map<String, dynamic>?)?['vrn'] ??
                                detail['supplier_vrn'] ??
                                '-')
                            as String,
                    isDark: isDark,
                  ),
                  _VatDetailRow(
                    label: 'Tax Invoice',
                    value: (detail['tax_invoice'] ?? '-') as String,
                    isDark: isDark,
                  ),
                  _VatDetailRow(
                    label: 'Invoice Date',
                    value: _formatVatDetailDate(
                      detail['invoice_date'] as String?,
                    ),
                    isDark: isDark,
                  ),
                  _VatDetailRow(
                    label: 'Goods',
                    value: (detail['goods'] ?? '-') as String,
                    isDark: isDark,
                  ),
                  _VatDetailRow(
                    label: 'Total Amount',
                    value: _formatVatDetailMoney(
                      _toDouble(detail['total_amount']),
                    ),
                    isDark: isDark,
                    valueColor: vatAccentBlue,
                  ),
                  _VatDetailRow(
                    label: 'Amount VAT EXC',
                    value: _formatVatDetailMoney(
                      _toDouble(detail['amount_vat_exc']),
                    ),
                    isDark: isDark,
                  ),
                  _VatDetailRow(
                    label: 'Date',
                    value: _formatVatDetailDate(detail['date'] as String?),
                    isDark: isDark,
                  ),
                  _VatDetailRow(
                    label: 'VAT Amount',
                    value: _formatVatDetailMoney(
                      _toDouble(detail['vat_amount']),
                    ),
                    isDark: isDark,
                  ),
                  _VatDetailRow(
                    label: 'Status',
                    value:
                        ((detail['approval_status'] ??
                                detail['status'] ??
                                'PENDING'))
                            .toString()
                            .toUpperCase(),
                    isDark: isDark,
                  ),
                  if (purchaseItems.isNotEmpty) ...[
                    const SizedBox(height: 16),
                    _VatSectionTitle(title: 'Order Items', isDark: isDark),
                    const SizedBox(height: 12),
                    _VatOrderItemsTable(items: purchaseItems, isDark: isDark),
                    const SizedBox(height: 8),
                    _VatDetailRow(
                      label: 'Subtotal',
                      value: _formatVatDetailMoney(itemsSubtotal),
                      isDark: isDark,
                    ),
                    _VatDetailRow(
                      label: 'VAT (18%)',
                      value: _formatVatDetailMoney(
                        _toDouble(detail['vat_amount']),
                      ),
                      isDark: isDark,
                    ),
                    _VatDetailRow(
                      label: 'Grand Total',
                      value: _formatVatDetailMoney(
                        _toDouble(detail['total_amount']),
                      ),
                      isDark: isDark,
                      valueColor: vatAccentBlue,
                    ),
                  ],
                  const SizedBox(height: 16),
                  _VatSectionTitle(title: 'Approval Flow', isDark: isDark),
                  _VatFlowStatusCard(
                    statusLabel:
                        (approvalFlow['status_label'] ??
                                detail['approval_summary'] ??
                                'Not Submitted')
                            .toString(),
                    isDark: isDark,
                  ),
                  const SizedBox(height: 12),
                  if (approvalSteps.isNotEmpty) ...[
                    Text(
                      'Approvals',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w700,
                        color: isDark ? Colors.white : AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 10),
                    _VatApprovalFlowTable(steps: approvalSteps, isDark: isDark),
                    const SizedBox(height: 10),
                    Text(
                      (approvalFlow['is_completed'] == true)
                          ? 'Approval completed!'
                          : (approvalFlow['status_label'] ??
                                    detail['approval_summary'] ??
                                    'In Progress')
                                .toString(),
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: isDark
                            ? Colors.white70
                            : AppColors.textSecondary,
                      ),
                    ),
                  ] else ...[
                    Text(
                      (detail['approval_summary'] ??
                              'Waiting for submission/approval')
                          .toString(),
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: isDark
                            ? Colors.white70
                            : AppColors.textSecondary,
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
}

Future<void> _showPurchaseForm(
  BuildContext context,
  WidgetRef ref,
  bool isDark,
  bool isSwahili, {
  Map<String, dynamic>? purchase,
}) async {
  // Load reference data before opening sheet
  Map<String, dynamic>? refDataMap;
  try {
    final api = ref.read(apiClientProvider);
    final resp = await api.get('/vat/reference-data');
    refDataMap = resp.data['data'] as Map<String, dynamic>;
  } catch (e) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(vatErrorMessage(e, isSwahili: isSwahili)),
          backgroundColor: Colors.red,
        ),
      );
    }
    return;
  }
  if (!context.mounted) return;

  final isEdit = purchase != null;
  final totalCtrl = TextEditingController(
    text: purchase?['total_amount']?.toString() ?? '',
  );
  final taxInvoiceCtrl = TextEditingController(
    text: purchase?['tax_invoice'] ?? '',
  );
  final invoiceDateCtrl = TextEditingController(
    text: purchase?['invoice_date'] ?? vatDateFmt(DateTime.now()),
  );
  final dateCtrl = TextEditingController(
    text: purchase?['date'] ?? vatDateFmt(DateTime.now()),
  );
  int? selectedSupplierId = purchase?['supplier_id'];
  int? selectedItemId = purchase?['item_id'];
  int? selectedPurchaseType = purchase?['purchase_type'];
  String isExpense = purchase?['is_expense'] ?? 'NO';
  File? selectedFile;
  final formKey = GlobalKey<FormState>();
  final suppliers = (refDataMap['suppliers'] as List?) ?? [];
  final items = (refDataMap['items'] as List?) ?? [];
  final purchaseTypes = (refDataMap['purchase_types'] as List?) ?? [];

  showModalBottomSheet(
    context: context,
    isScrollControlled: true,
    backgroundColor: isDark ? vatDarkCard : Colors.white,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
    ),
    builder: (ctx) {
      return StatefulBuilder(
        builder: (ctx, setState) {
          return Padding(
            padding: EdgeInsets.fromLTRB(
              20,
              16,
              20,
              MediaQuery.of(ctx).viewInsets.bottom + 100,
            ),
            child: Form(
              key: formKey,
              child: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Center(
                      child: Container(
                        width: 40,
                        height: 4,
                        decoration: BoxDecoration(
                          color: Colors.grey[400],
                          borderRadius: BorderRadius.circular(2),
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      isEdit
                          ? (isSwahili ? 'Hariri Manunuzi' : 'Edit Purchase')
                          : (isSwahili ? 'Ongeza Manunuzi' : 'Add Purchase'),
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                        color: isDark ? Colors.white : AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 20),
                    // Is Expense toggle
                    Row(
                      children: [
                        Text(
                          isSwahili ? 'Ni Gharama?' : 'Is Expense?',
                          style: TextStyle(
                            fontSize: 12,
                            color: isDark ? Colors.white54 : AppColors.textHint,
                          ),
                        ),
                        const Spacer(),
                        Switch(
                          value: isExpense == 'YES',
                          onChanged: (v) =>
                              setState(() => isExpense = v ? 'YES' : 'NO'),
                          activeTrackColor: vatAccentTeal,
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    vatDropdown<int>(
                      value: selectedSupplierId,
                      items: suppliers.map((s) => s['id'] as int).toList(),
                      label: isSwahili ? 'Mtoa Huduma *' : 'Supplier *',
                      isDark: isDark,
                      labelBuilder: (id) =>
                          suppliers.firstWhere((s) => s['id'] == id)['name']
                              as String,
                      onChanged: (v) => setState(() => selectedSupplierId = v),
                      validator: (v) => v == null ? 'Required' : null,
                    ),
                    vatDropdown<int>(
                      value: selectedItemId,
                      items: items.map((i) => i['id'] as int).toList(),
                      label: isSwahili ? 'Bidhaa *' : 'Item/Goods *',
                      isDark: isDark,
                      labelBuilder: (id) =>
                          items.firstWhere((i) => i['id'] == id)['name']
                              as String,
                      onChanged: (v) => setState(() => selectedItemId = v),
                      validator: (v) => v == null ? 'Required' : null,
                    ),
                    vatDropdown<int>(
                      value: selectedPurchaseType,
                      items: purchaseTypes.map((p) => p['id'] as int).toList(),
                      label: isSwahili
                          ? 'Aina ya Ununuzi *'
                          : 'Purchase Type *',
                      isDark: isDark,
                      labelBuilder: (id) =>
                          purchaseTypes.firstWhere((p) => p['id'] == id)['name']
                              as String,
                      onChanged: (v) =>
                          setState(() => selectedPurchaseType = v),
                      validator: (v) => v == null ? 'Required' : null,
                    ),
                    vatTextField(
                      controller: totalCtrl,
                      label: isSwahili ? 'Kiasi *' : 'Total Amount *',
                      isDark: isDark,
                      keyboardType: TextInputType.number,
                      validator: (v) =>
                          (v == null || v.isEmpty) ? 'Required' : null,
                    ),
                    vatTextField(
                      controller: taxInvoiceCtrl,
                      label: 'Tax Invoice *',
                      isDark: isDark,
                      validator: (v) =>
                          (v == null || v.isEmpty) ? 'Required' : null,
                    ),
                    vatTextField(
                      controller: invoiceDateCtrl,
                      label: 'Invoice Date *',
                      isDark: isDark,
                      readOnly: true,
                      onTap: () async {
                        final picked = await vatPickDate(
                          ctx,
                          DateTime.tryParse(invoiceDateCtrl.text) ??
                              DateTime.now(),
                        );
                        if (picked != null) {
                          invoiceDateCtrl.text = vatDateFmt(picked);
                        }
                      },
                      validator: (v) =>
                          (v == null || v.isEmpty) ? 'Required' : null,
                    ),
                    vatTextField(
                      controller: dateCtrl,
                      label: isSwahili ? 'Tarehe *' : 'Date *',
                      isDark: isDark,
                      readOnly: true,
                      onTap: () async {
                        final picked = await vatPickDate(
                          ctx,
                          DateTime.tryParse(dateCtrl.text) ?? DateTime.now(),
                        );
                        if (picked != null) {
                          dateCtrl.text = vatDateFmt(picked);
                        }
                      },
                      validator: (v) =>
                          (v == null || v.isEmpty) ? 'Required' : null,
                    ),
                    VatFilePicker(
                      file: selectedFile,
                      isDark: isDark,
                      isSwahili: isSwahili,
                      onPicked: (f) => setState(() => selectedFile = f),
                    ),
                    const SizedBox(height: 8),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: () async {
                          if (!formKey.currentState!.validate()) return;

                          final fields = {
                            'supplier_id': selectedSupplierId,
                            'item_id': selectedItemId,
                            'purchase_type': selectedPurchaseType,
                            'is_expense': isExpense,
                            'total_amount':
                                double.tryParse(totalCtrl.text) ?? 0,
                            'tax_invoice': taxInvoiceCtrl.text,
                            'invoice_date': invoiceDateCtrl.text,
                            'date': dateCtrl.text,
                          };

                          try {
                            final api = ref.read(apiClientProvider);
                            if (isEdit) {
                              if (selectedFile != null) {
                                fields['_method'] = 'PUT';
                                final data = await vatBuildFormData(
                                  fields,
                                  selectedFile,
                                );
                                await api.uploadFile(
                                  '/vat/purchases/${purchase['id']}',
                                  data: data,
                                );
                              } else {
                                await api.put(
                                  '/vat/purchases/${purchase['id']}',
                                  data: fields,
                                );
                              }
                            } else {
                              if (selectedFile != null) {
                                final data = await vatBuildFormData(
                                  fields,
                                  selectedFile,
                                );
                                await api.uploadFile(
                                  '/vat/purchases',
                                  data: data,
                                );
                              } else {
                                await api.post('/vat/purchases', data: fields);
                              }
                            }
                            ref.invalidate(_dataProvider);
                            if (ctx.mounted) Navigator.pop(ctx);
                          } catch (e) {
                            if (ctx.mounted) {
                              ScaffoldMessenger.of(ctx).showSnackBar(
                                SnackBar(
                                  content: Text(
                                    vatErrorMessage(e, isSwahili: isSwahili),
                                  ),
                                  backgroundColor: Colors.red,
                                ),
                              );
                            }
                          }
                        },
                        style: ElevatedButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10),
                          ),
                        ),
                        child: Text(
                          isEdit
                              ? (isSwahili ? 'Sasisha' : 'Update')
                              : (isSwahili ? 'Hifadhi' : 'Save'),
                          style: const TextStyle(
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      );
    },
  );
}

class _PurchaseCard extends StatelessWidget {
  final Map<String, dynamic> purchase;
  final bool isDark;
  final bool isSwahili;
  final VoidCallback onTap;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  const _PurchaseCard({
    required this.purchase,
    required this.isDark,
    required this.isSwahili,
    required this.onTap,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.all(14),
        decoration: vatCardDeco(isDark),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(
                  Icons.shopping_bag_rounded,
                  size: 16,
                  color: const Color(0xFF66BB6A),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    purchase['supplier_name'] ?? '-',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: isDark ? Colors.white : AppColors.textPrimary,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                VatStatusBadge(
                  status:
                      (purchase['approval_status'] ?? purchase['status'] ?? '')
                          .toString(),
                ),
                const SizedBox(width: 6),
                GestureDetector(
                  onTap: onEdit,
                  child: Icon(
                    Icons.edit_outlined,
                    size: 18,
                    color: AppColors.primary.withValues(alpha: 0.8),
                  ),
                ),
                const SizedBox(width: 6),
                GestureDetector(
                  onTap: onDelete,
                  child: Icon(
                    Icons.delete_outline_rounded,
                    size: 18,
                    color: Colors.red.withValues(alpha: 0.7),
                  ),
                ),
              ],
            ),
            if (purchase['goods'] != null) ...[
              const SizedBox(height: 6),
              Row(
                children: [
                  Icon(
                    Icons.inventory_2_rounded,
                    size: 12,
                    color: isDark ? Colors.white38 : AppColors.textHint,
                  ),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      purchase['goods'],
                      style: TextStyle(
                        fontSize: 11,
                        color: isDark
                            ? Colors.white54
                            : AppColors.textSecondary,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ],
            const SizedBox(height: 8),
            Row(
              children: [
                VatInfoCol(
                  label: 'Date',
                  value: purchase['date'] ?? '-',
                  isDark: isDark,
                ),
                VatInfoCol(
                  label: 'Total',
                  value: vatMoney(purchase['total_amount']),
                  isDark: isDark,
                  isMoney: true,
                ),
              ],
            ),
            const SizedBox(height: 6),
            Row(
              children: [
                VatInfoCol(
                  label: 'VAT Exc.',
                  value: vatMoney(purchase['amount_vat_exc']),
                  isDark: isDark,
                  isMoney: true,
                ),
                VatInfoCol(
                  label: 'VAT',
                  value: vatMoney(purchase['vat_amount']),
                  isDark: isDark,
                  isMoney: true,
                ),
              ],
            ),
            if (purchase['supplier_vrn'] != null &&
                (purchase['supplier_vrn'] as String).isNotEmpty) ...[
              const SizedBox(height: 6),
              Text(
                'VRN: ${purchase['supplier_vrn']}',
                style: TextStyle(
                  fontSize: 10,
                  color: isDark ? Colors.white38 : AppColors.textHint,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

double _toDouble(dynamic value) {
  if (value is num) return value.toDouble();
  return double.tryParse('$value') ?? 0;
}

String _formatVatDetailDate(String? date) {
  if (date == null || date.isEmpty) return '-';
  try {
    return DateFormat('dd MMMM, yyyy').format(DateTime.parse(date));
  } catch (_) {
    return date;
  }
}

String _formatVatDetailMoney(double amount) {
  return NumberFormat('#,##0.00', 'en').format(amount);
}

class _VatSectionTitle extends StatelessWidget {
  final String title;
  final bool isDark;
  const _VatSectionTitle({required this.title, required this.isDark});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Text(
        title,
        style: TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w800,
          color: isDark ? Colors.white : AppColors.textPrimary,
        ),
      ),
    );
  }
}

class _VatDetailRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isDark;
  final Color? valueColor;
  const _VatDetailRow({
    required this.label,
    required this.value,
    required this.isDark,
    this.valueColor,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: TextStyle(
                fontSize: 13,
                color: isDark ? Colors.white54 : AppColors.textSecondary,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color:
                    valueColor ??
                    (isDark ? Colors.white : AppColors.textPrimary),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _VatOrderItemsTable extends StatelessWidget {
  final List<Map<String, dynamic>> items;
  final bool isDark;
  const _VatOrderItemsTable({required this.items, required this.isDark});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: DataTable(
        headingRowHeight: 36,
        dataRowMinHeight: 40,
        dataRowMaxHeight: 56,
        headingTextStyle: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w700,
          color: isDark ? Colors.white70 : AppColors.textPrimary,
        ),
        dataTextStyle: TextStyle(
          fontSize: 12,
          color: isDark ? Colors.white : AppColors.textPrimary,
        ),
        columns: [
          const DataColumn(label: Text('#')),
          DataColumn(
            label: Text(
              _trLocale(context, en: 'Description', sw: 'Maelezo', ar: 'الوصف'),
            ),
          ),
          DataColumn(
            label: Text(
              _trLocale(
                context,
                en: 'BOQ Item',
                sw: 'Kipengee cha BOQ',
                ar: 'عنصر BOQ',
              ),
            ),
          ),
          DataColumn(
            label: Text(
              _trLocale(context, en: 'Unit', sw: 'Kipimo', ar: 'الوحدة'),
            ),
          ),
          DataColumn(
            label: Text(
              _trLocale(context, en: 'Qty', sw: 'Kiasi', ar: 'الكمية'),
            ),
          ),
          DataColumn(
            label: Text(
              _trLocale(
                context,
                en: 'Unit Price',
                sw: 'Bei ya Kipimo',
                ar: 'سعر الوحدة',
              ),
            ),
          ),
          DataColumn(
            label: Text(
              _trLocale(context, en: 'Total', sw: 'Jumla', ar: 'الإجمالي'),
            ),
          ),
        ],
        rows: List.generate(items.length, (index) {
          final item = items[index];
          final boqItem = item['boq_item'] as Map<String, dynamic>?;
          return DataRow(
            cells: [
              DataCell(Text((index + 1).toString())),
              DataCell(Text((item['description'] ?? '-') as String)),
              DataCell(Text((boqItem?['description'] ?? '-') as String)),
              DataCell(Text((item['unit'] ?? '-') as String)),
              DataCell(Text(_toDouble(item['quantity']).toStringAsFixed(2))),
              DataCell(
                Text(_formatVatDetailMoney(_toDouble(item['unit_price']))),
              ),
              DataCell(
                Text(_formatVatDetailMoney(_toDouble(item['total_price']))),
              ),
            ],
          );
        }),
      ),
    );
  }
}

class _VatFlowStatusCard extends StatelessWidget {
  final String statusLabel;
  final bool isDark;
  const _VatFlowStatusCard({required this.statusLabel, required this.isDark});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: isDark ? vatDarkBg : const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isDark ? vatDarkBorder : Colors.grey.withValues(alpha: 0.15),
        ),
      ),
      child: Text(
        statusLabel,
        style: TextStyle(
          fontSize: 14,
          fontWeight: FontWeight.w700,
          color: isDark ? Colors.white : AppColors.textPrimary,
        ),
      ),
    );
  }
}

class _VatApprovalFlowTable extends StatelessWidget {
  final List<Map<String, dynamic>> steps;
  final bool isDark;
  const _VatApprovalFlowTable({required this.steps, required this.isDark});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: DataTable(
        headingRowHeight: 36,
        dataRowMinHeight: 42,
        dataRowMaxHeight: 64,
        headingTextStyle: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w700,
          color: isDark ? Colors.white70 : AppColors.textPrimary,
        ),
        dataTextStyle: TextStyle(
          fontSize: 12,
          color: isDark ? Colors.white : AppColors.textPrimary,
        ),
        columns: [
          DataColumn(
            label: Text(
              _trLocale(context, en: 'By:', sw: 'Na:', ar: 'بواسطة:'),
            ),
          ),
          DataColumn(
            label: Text(
              _trLocale(context, en: 'Action', sw: 'Hatua', ar: 'الإجراء'),
            ),
          ),
          DataColumn(
            label: Text(
              _trLocale(context, en: 'Date', sw: 'Tarehe', ar: 'التاريخ'),
            ),
          ),
        ],
        rows: steps.map((step) {
          return DataRow(
            cells: [
              DataCell(
                Text(
                  ((step['approver_name'] ?? step['role_name'] ?? '-')
                      as String),
                ),
              ),
              DataCell(Text((step['action'] ?? '-') as String)),
              DataCell(Text((step['date'] ?? '-') as String)),
            ],
          );
        }).toList(),
      ),
    );
  }
}

String _trLocale(
  BuildContext context, {
  required String en,
  required String sw,
  required String ar,
}) {
  final code = Localizations.localeOf(context).languageCode.toLowerCase();
  if (code == 'ar') return ar;
  if (code == 'sw') return sw;
  return en;
}
