import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import 'vat_shared.dart';

final _startProvider = StateProvider.autoDispose<DateTime>(
    (ref) => DateTime(DateTime.now().year, DateTime.now().month, 1));
final _endProvider =
    StateProvider.autoDispose<DateTime>((ref) => DateTime.now());

final _dataProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final api = ref.watch(apiClientProvider);
  final s = ref.watch(_startProvider);
  final e = ref.watch(_endProvider);
  final resp = await api.get('/vat/purchases',
      queryParameters: {'start_date': vatDateFmt(s), 'end_date': vatDateFmt(e)});
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
                    )),
                    const SizedBox(width: 8),
                    Expanded(
                        child: VatSummaryChip(
                      label: 'VAT Exc.',
                      value: vatMoney(totals['amount_vat_exc']),
                      color: vatAccentTeal,
                      isDark: isDark,
                    )),
                    const SizedBox(width: 8),
                    Expanded(
                        child: VatSummaryChip(
                      label: 'VAT',
                      value: vatMoney(totals['vat_amount']),
                      color: const Color(0xFFF59E0B),
                      isDark: isDark,
                    )),
                  ],
                ),
                const SizedBox(height: 14),
                VatCountBadge(
                    count: purchases.length,
                    label: isSwahili ? 'Manunuzi' : 'Purchases',
                    isDark: isDark),
                const SizedBox(height: 8),
                if (purchases.isEmpty)
                  VatEmptyState(isDark: isDark, isSwahili: isSwahili)
                else
                  ...purchases.map((p) => _PurchaseCard(
                        purchase: p,
                        isDark: isDark,
                        isSwahili: isSwahili,
                        onEdit: () => _showPurchaseForm(
                            context, ref, isDark, isSwahili,
                            purchase: p),
                        onDelete: () async {
                          final ok = await vatDelete(
                              context, ref, '/vat/purchases', p['id'],
                              isSwahili: isSwahili);
                          if (ok) ref.invalidate(_dataProvider);
                        },
                      )),
              ],
            );
          },
        ),
      ),
    );
  }
}

Future<void> _showPurchaseForm(BuildContext context, WidgetRef ref, bool isDark,
    bool isSwahili,
    {Map<String, dynamic>? purchase}) async {
  // Load reference data before opening sheet
  Map<String, dynamic>? refDataMap;
  try {
    final api = ref.read(apiClientProvider);
    final resp = await api.get('/vat/reference-data');
    refDataMap = resp.data['data'] as Map<String, dynamic>;
  } catch (e) {
    if (context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error loading form data: $e'),
            backgroundColor: Colors.red),
      );
    }
    return;
  }
  if (!context.mounted) return;

  final isEdit = purchase != null;
  final totalCtrl =
      TextEditingController(text: purchase?['total_amount']?.toString() ?? '');
  final taxInvoiceCtrl =
      TextEditingController(text: purchase?['tax_invoice'] ?? '');
  final invoiceDateCtrl = TextEditingController(
      text: purchase?['invoice_date'] ?? vatDateFmt(DateTime.now()));
  final dateCtrl = TextEditingController(
      text: purchase?['date'] ?? vatDateFmt(DateTime.now()));
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
      return StatefulBuilder(builder: (ctx, setState) {
        return Padding(
          padding: EdgeInsets.fromLTRB(
              20, 16, 20, MediaQuery.of(ctx).viewInsets.bottom + 100),
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
                          borderRadius: BorderRadius.circular(2)),
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
                            color: isDark
                                ? Colors.white54
                                : AppColors.textHint),
                      ),
                      const Spacer(),
                      Switch(
                        value: isExpense == 'YES',
                        onChanged: (v) => setState(
                            () => isExpense = v ? 'YES' : 'NO'),
                        activeTrackColor: vatAccentTeal,
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  vatDropdown<int>(
                    value: selectedSupplierId,
                    items: suppliers
                        .map((s) => s['id'] as int)
                        .toList(),
                    label:
                        isSwahili ? 'Mtoa Huduma *' : 'Supplier *',
                    isDark: isDark,
                    labelBuilder: (id) => suppliers.firstWhere(
                        (s) => s['id'] == id)['name'] as String,
                    onChanged: (v) =>
                        setState(() => selectedSupplierId = v),
                    validator: (v) =>
                        v == null ? 'Required' : null,
                  ),
                  vatDropdown<int>(
                    value: selectedItemId,
                    items:
                        items.map((i) => i['id'] as int).toList(),
                    label: isSwahili ? 'Bidhaa *' : 'Item/Goods *',
                    isDark: isDark,
                    labelBuilder: (id) => items.firstWhere(
                        (i) => i['id'] == id)['name'] as String,
                    onChanged: (v) =>
                        setState(() => selectedItemId = v),
                    validator: (v) =>
                        v == null ? 'Required' : null,
                  ),
                  vatDropdown<int>(
                    value: selectedPurchaseType,
                    items: purchaseTypes
                        .map((p) => p['id'] as int)
                        .toList(),
                    label: isSwahili
                        ? 'Aina ya Ununuzi *'
                        : 'Purchase Type *',
                    isDark: isDark,
                    labelBuilder: (id) =>
                        purchaseTypes.firstWhere(
                            (p) => p['id'] == id)['name'] as String,
                    onChanged: (v) =>
                        setState(() => selectedPurchaseType = v),
                    validator: (v) =>
                        v == null ? 'Required' : null,
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
                      final picked = await vatPickDate(ctx,
                          DateTime.tryParse(invoiceDateCtrl.text) ??
                              DateTime.now());
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
                          DateTime.tryParse(dateCtrl.text) ?? DateTime.now());
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
                                  fields, selectedFile);
                              await api.uploadFile(
                                  '/vat/purchases/${purchase['id']}',
                                  data: data);
                            } else {
                              await api.put(
                                  '/vat/purchases/${purchase['id']}',
                                  data: fields);
                            }
                          } else {
                            if (selectedFile != null) {
                              final data = await vatBuildFormData(
                                  fields, selectedFile);
                              await api.uploadFile('/vat/purchases',
                                  data: data);
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
                                  content: Text('Error: $e'),
                                  backgroundColor: Colors.red),
                            );
                          }
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(10)),
                      ),
                      child: Text(
                          isEdit
                              ? (isSwahili ? 'Sasisha' : 'Update')
                              : (isSwahili ? 'Hifadhi' : 'Save'),
                          style: const TextStyle(
                              fontSize: 14, fontWeight: FontWeight.w600)),
                    ),
                  ),
                ],
              ),
            ),
          ),
        );
      });
    },
  );
}

class _PurchaseCard extends StatelessWidget {
  final Map<String, dynamic> purchase;
  final bool isDark;
  final bool isSwahili;
  final VoidCallback onEdit;
  final VoidCallback onDelete;
  const _PurchaseCard({
    required this.purchase,
    required this.isDark,
    required this.isSwahili,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onEdit,
      child: Container(
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.all(14),
        decoration: vatCardDeco(isDark),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.shopping_bag_rounded,
                    size: 16, color: const Color(0xFF66BB6A)),
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
                    status: purchase['status'] as String? ?? ''),
                const SizedBox(width: 6),
                GestureDetector(
                  onTap: onDelete,
                  child: Icon(Icons.delete_outline_rounded,
                      size: 18,
                      color: Colors.red.withValues(alpha: 0.7)),
                ),
              ],
            ),
            if (purchase['goods'] != null) ...[
              const SizedBox(height: 6),
              Row(
                children: [
                  Icon(Icons.inventory_2_rounded,
                      size: 12,
                      color: isDark ? Colors.white38 : AppColors.textHint),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      purchase['goods'],
                      style: TextStyle(
                        fontSize: 11,
                        color:
                            isDark ? Colors.white54 : AppColors.textSecondary,
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
                    isDark: isDark),
                VatInfoCol(
                    label: 'Total',
                    value: vatMoney(purchase['total_amount']),
                    isDark: isDark,
                    isMoney: true),
              ],
            ),
            const SizedBox(height: 6),
            Row(
              children: [
                VatInfoCol(
                    label: 'VAT Exc.',
                    value: vatMoney(purchase['amount_vat_exc']),
                    isDark: isDark,
                    isMoney: true),
                VatInfoCol(
                    label: 'VAT',
                    value: vatMoney(purchase['vat_amount']),
                    isDark: isDark,
                    isMoney: true),
              ],
            ),
            if (purchase['supplier_vrn'] != null &&
                (purchase['supplier_vrn'] as String).isNotEmpty) ...[
              const SizedBox(height: 6),
              Text(
                'VRN: ${purchase['supplier_vrn']}',
                style: TextStyle(
                    fontSize: 10,
                    color: isDark ? Colors.white38 : AppColors.textHint),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
