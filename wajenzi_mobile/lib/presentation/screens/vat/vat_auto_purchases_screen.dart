import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/theme_config.dart';
import '../../../core/network/api_client.dart';
import '../../providers/settings_provider.dart';
import 'vat_shared.dart';

final _autoStartProvider = StateProvider.autoDispose<DateTime>(
  (ref) => DateTime(DateTime.now().year, DateTime.now().month, 1),
);
final _autoEndProvider = StateProvider.autoDispose<DateTime>(
  (ref) => DateTime.now(),
);

final _autoDataProvider = FutureProvider.autoDispose<Map<String, dynamic>>((
  ref,
) async {
  final api = ref.watch(apiClientProvider);
  final s = ref.watch(_autoStartProvider);
  final e = ref.watch(_autoEndProvider);
  final resp = await api.get(
    '/vat/auto-purchases',
    queryParameters: {'start_date': vatDateFmt(s), 'end_date': vatDateFmt(e)},
  );
  return resp.data['data'] as Map<String, dynamic>;
});

class VatAutoPurchasesScreen extends ConsumerWidget {
  const VatAutoPurchasesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isDark = ref.watch(isDarkModeProvider);
    final isSwahili = ref.watch(isSwahiliProvider);
    final dataAsync = ref.watch(_autoDataProvider);
    final bottomPad = MediaQuery.of(context).padding.bottom + 90;

    return Scaffold(
      backgroundColor: isDark ? vatDarkBg : AppColors.background,
      appBar: AppBar(
        title: Text(isSwahili ? 'Manunuzi ya EFD' : 'EFD Auto Purchases'),
        backgroundColor: isDark ? vatDarkCard : null,
      ),
      floatingActionButton: Padding(
        padding: const EdgeInsets.only(bottom: 80),
        child: FloatingActionButton(
          onPressed: () => _showAutoPurchaseForm(context, ref, isDark, isSwahili),
          child: const Icon(Icons.add),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(_autoDataProvider.future),
        child: dataAsync.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => VatErrorBody(
            onRetry: () => ref.invalidate(_autoDataProvider),
            isSwahili: isSwahili,
          ),
          data: (data) {
            final receipts =
                (data['receipts'] as List?)?.cast<Map<String, dynamic>>() ?? [];
            final totals = data['totals'] as Map<String, dynamic>? ?? {};

            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: EdgeInsets.fromLTRB(16, 12, 16, bottomPad),
              children: [
                VatDateRangeBar(
                  startProvider: _autoStartProvider,
                  endProvider: _autoEndProvider,
                  isDark: isDark,
                  isSwahili: isSwahili,
                ),
                const SizedBox(height: 12),
                _AutoSummaryGrid(
                  totals: totals,
                  isDark: isDark,
                  isSwahili: isSwahili,
                ),
                const SizedBox(height: 14),
                VatCountBadge(
                  count: receipts.length,
                  label: isSwahili ? 'Risiti za EFD' : 'EFD Receipts',
                  isDark: isDark,
                ),
                const SizedBox(height: 8),
                if (receipts.isEmpty)
                  VatEmptyState(isDark: isDark, isSwahili: isSwahili)
                else
                  ...receipts.map(
                    (r) => _ReceiptCard(
                      receipt: r,
                      isDark: isDark,
                      isSwahili: isSwahili,
                      onEdit: () => _showAutoPurchaseForm(
                        context,
                        ref,
                        isDark,
                        isSwahili,
                        receipt: r,
                      ),
                      onDelete: () async {
                        final ok = await vatDelete(
                          context,
                          ref,
                          '/vat/auto-purchases',
                          r['id'] as int,
                          isSwahili: isSwahili,
                        );
                        if (ok) {
                          ref.invalidate(_autoDataProvider);
                        }
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

Future<void> _showAutoPurchaseForm(
  BuildContext context,
  WidgetRef ref,
  bool isDark,
  bool isSwahili, {
  Map<String, dynamic>? receipt,
}) async {
  final isEdit = receipt != null;
  final companyCtrl =
      TextEditingController(text: receipt?['company_name'] as String? ?? '');
  final vrnCtrl = TextEditingController(text: receipt?['vrn'] as String? ?? '');
  final receiptNoCtrl = TextEditingController(
    text: receipt?['receipt_number'] as String? ?? '',
  );
  final receiptDateCtrl = TextEditingController(
    text: receipt?['receipt_date'] as String? ?? vatDateFmt(DateTime.now()),
  );
  final receiptTimeCtrl = TextEditingController(
    text: receipt?['receipt_time'] as String? ?? TimeOfDay.now().format(context),
  );
  final verificationCtrl = TextEditingController(
    text: receipt?['receipt_verification_code'] as String? ?? '',
  );
  final amountVatExcCtrl = TextEditingController(
    text: receipt?['amount_vat_exc']?.toString() ?? '',
  );
  final vatAmountCtrl = TextEditingController(
    text: receipt?['vat_amount']?.toString() ?? '',
  );
  final totalAmountCtrl = TextEditingController(
    text: receipt?['total_amount']?.toString() ?? '',
  );
  final discountCtrl = TextEditingController(
    text: receipt?['discount']?.toString() ?? '',
  );
  final formKey = GlobalKey<FormState>();
  String isExpense = receipt?['is_expense'] as String? ?? 'NO';
  final initialItems = ((receipt?['items'] as List?) ?? [])
      .cast<Map<String, dynamic>>()
      .map(
        (item) => {
          'description': TextEditingController(
            text: item['description'] as String? ?? '',
          ),
          'qty': TextEditingController(text: item['qty']?.toString() ?? '1'),
          'amount': TextEditingController(
            text: item['amount']?.toString() ?? '',
          ),
        },
      )
      .toList();
  final itemControllers = initialItems.isEmpty
      ? [_buildItemController()]
      : initialItems;

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
                          ? (isSwahili
                              ? 'Hariri Manunuzi ya EFD'
                              : 'Edit EFD Auto Purchase')
                          : (isSwahili
                              ? 'Ongeza Manunuzi ya EFD'
                              : 'Add EFD Auto Purchase'),
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                        color: isDark ? Colors.white : AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 20),
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
                    vatTextField(
                      controller: companyCtrl,
                      label: isSwahili ? 'Jina la Muuzaji *' : 'Supplier Name *',
                      isDark: isDark,
                      validator: (v) =>
                          (v == null || v.trim().isEmpty) ? 'Required' : null,
                    ),
                    vatTextField(
                      controller: vrnCtrl,
                      label: 'VRN',
                      isDark: isDark,
                    ),
                    vatTextField(
                      controller: receiptNoCtrl,
                      label: isSwahili ? 'Namba ya Risiti *' : 'Receipt Number *',
                      isDark: isDark,
                      validator: (v) =>
                          (v == null || v.trim().isEmpty) ? 'Required' : null,
                    ),
                    vatTextField(
                      controller: receiptDateCtrl,
                      label: isSwahili ? 'Tarehe ya Risiti *' : 'Receipt Date *',
                      isDark: isDark,
                      readOnly: true,
                      onTap: () async {
                        final picked = await vatPickDate(
                          ctx,
                          DateTime.tryParse(receiptDateCtrl.text) ?? DateTime.now(),
                        );
                        if (picked != null) {
                          receiptDateCtrl.text = vatDateFmt(picked);
                        }
                      },
                      validator: (v) =>
                          (v == null || v.trim().isEmpty) ? 'Required' : null,
                    ),
                    vatTextField(
                      controller: receiptTimeCtrl,
                      label: isSwahili ? 'Muda wa Risiti' : 'Receipt Time',
                      isDark: isDark,
                    ),
                    vatTextField(
                      controller: verificationCtrl,
                      label: isSwahili
                          ? 'Namba ya Uhakiki'
                          : 'Verification Code',
                      isDark: isDark,
                    ),
                    vatTextField(
                      controller: amountVatExcCtrl,
                      label: isSwahili ? 'Kiasi Bila VAT *' : 'Amount VAT Excl. *',
                      isDark: isDark,
                      keyboardType: TextInputType.number,
                      validator: (v) =>
                          (v == null || v.trim().isEmpty) ? 'Required' : null,
                    ),
                    vatTextField(
                      controller: vatAmountCtrl,
                      label: 'VAT Amount',
                      isDark: isDark,
                      keyboardType: TextInputType.number,
                    ),
                    vatTextField(
                      controller: totalAmountCtrl,
                      label: isSwahili ? 'Jumla *' : 'Total Amount *',
                      isDark: isDark,
                      keyboardType: TextInputType.number,
                      validator: (v) =>
                          (v == null || v.trim().isEmpty) ? 'Required' : null,
                    ),
                    vatTextField(
                      controller: discountCtrl,
                      label: isSwahili ? 'Punguzo' : 'Discount',
                      isDark: isDark,
                      keyboardType: TextInputType.number,
                    ),
                    const SizedBox(height: 6),
                    Text(
                      isSwahili ? 'Bidhaa za Risiti' : 'Receipt Items',
                      style: TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: isDark ? Colors.white : AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 10),
                    ...List.generate(itemControllers.length, (index) {
                      final item = itemControllers[index];
                      return _ItemEditorCard(
                        index: index,
                        isDark: isDark,
                        isSwahili: isSwahili,
                        descriptionCtrl:
                            item['description']! as TextEditingController,
                        qtyCtrl: item['qty']! as TextEditingController,
                        amountCtrl: item['amount']! as TextEditingController,
                        canRemove: itemControllers.length > 1,
                        onRemove: () {
                          setState(() {
                            itemControllers.removeAt(index);
                          });
                        },
                      );
                    }),
                    const SizedBox(height: 6),
                    OutlinedButton.icon(
                      onPressed: () {
                        setState(() {
                          itemControllers.add(_buildItemController());
                        });
                      },
                      icon: const Icon(Icons.add_rounded),
                      label: Text(isSwahili ? 'Ongeza Bidhaa' : 'Add Item'),
                    ),
                    const SizedBox(height: 12),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: () async {
                          if (!formKey.currentState!.validate()) return;

                          final items = itemControllers
                              .map(
                                (item) => {
                                  'description':
                                      (item['description']! as TextEditingController)
                                          .text
                                          .trim(),
                                  'qty': int.tryParse(
                                        (item['qty']! as TextEditingController)
                                            .text
                                            .trim(),
                                      ) ??
                                      1,
                                  'amount': double.tryParse(
                                        (item['amount']! as TextEditingController)
                                            .text
                                            .trim(),
                                      ) ??
                                      0,
                                },
                              )
                              .where((item) => (item['description'] as String).isNotEmpty)
                              .toList();

                          final fields = {
                            'company_name': companyCtrl.text.trim(),
                            'vrn': vrnCtrl.text.trim(),
                            'receipt_number': receiptNoCtrl.text.trim(),
                            'receipt_date': receiptDateCtrl.text.trim(),
                            'receipt_time': receiptTimeCtrl.text.trim(),
                            'receipt_verification_code':
                                verificationCtrl.text.trim(),
                            'receipt_total_excl_of_tax':
                                double.tryParse(amountVatExcCtrl.text.trim()) ?? 0,
                            'receipt_total_tax':
                                double.tryParse(vatAmountCtrl.text.trim()) ?? 0,
                            'receipt_total_incl_of_tax':
                                double.tryParse(totalAmountCtrl.text.trim()) ?? 0,
                            'receipt_total_discount':
                                double.tryParse(discountCtrl.text.trim()) ?? 0,
                            'is_expense': isExpense,
                            'items': items,
                          };

                          try {
                            final api = ref.read(apiClientProvider);
                            if (isEdit) {
                              await api.put(
                                '/vat/auto-purchases/${receipt['id']}',
                                data: fields,
                              );
                            } else {
                              await api.post('/vat/auto-purchases', data: fields);
                            }
                            ref.invalidate(_autoDataProvider);
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

Map<String, TextEditingController> _buildItemController() => {
      'description': TextEditingController(),
      'qty': TextEditingController(text: '1'),
      'amount': TextEditingController(),
    };

class _AutoSummaryGrid extends StatelessWidget {
  final Map<String, dynamic> totals;
  final bool isDark;
  final bool isSwahili;

  const _AutoSummaryGrid({
    required this.totals,
    required this.isDark,
    required this.isSwahili,
  });

  @override
  Widget build(BuildContext context) {
    final chips = [
      VatSummaryChip(
        label: 'Total',
        value: vatMoney(totals['total_amount']),
        color: vatAccentBlue,
        isDark: isDark,
      ),
      VatSummaryChip(
        label: 'VAT Exc.',
        value: vatMoney(totals['amount_vat_exc']),
        color: vatAccentTeal,
        isDark: isDark,
      ),
      VatSummaryChip(
        label: 'VAT',
        value: vatMoney(totals['vat_amount']),
        color: const Color(0xFFF59E0B),
        isDark: isDark,
      ),
      VatSummaryChip(
        label: isSwahili ? 'Punguzo' : 'Discount',
        value: vatMoney(totals['discount']),
        color: const Color(0xFF8B5CF6),
        isDark: isDark,
      ),
    ];

    return LayoutBuilder(
      builder: (context, constraints) {
        final width = constraints.maxWidth;
        final itemWidth = width < 640 ? width : (width - 8) / 2;

        return Wrap(
          spacing: 8,
          runSpacing: 8,
          children: chips
              .map(
                (chip) => SizedBox(
                  width: itemWidth,
                  child: chip,
                ),
              )
              .toList(),
        );
      },
    );
  }
}

class _ReceiptCard extends StatelessWidget {
  final Map<String, dynamic> receipt;
  final bool isDark;
  final bool isSwahili;
  final VoidCallback onEdit;
  final VoidCallback onDelete;

  const _ReceiptCard({
    required this.receipt,
    required this.isDark,
    required this.isSwahili,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    final items =
        (receipt['items'] as List?)?.cast<Map<String, dynamic>>() ?? const [];
    final itemsSummary = receipt['items_summary'] as String? ?? '';
    final verificationCode =
        receipt['receipt_verification_code'] as String? ?? '';
    final vrn = receipt['vrn'] as String? ?? '';

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
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(
                  Icons.receipt_long_rounded,
                  size: 16,
                  color: const Color(0xFF66BB6A),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        receipt['company_name'] as String? ?? '-',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: isDark ? Colors.white : AppColors.textPrimary,
                        ),
                      ),
                      if (itemsSummary.isNotEmpty) ...[
                        const SizedBox(height: 4),
                        Text(
                          itemsSummary,
                          style: TextStyle(
                            fontSize: 11,
                            color: isDark
                                ? Colors.white54
                                : AppColors.textSecondary,
                          ),
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 3,
                      ),
                      decoration: BoxDecoration(
                        color: const Color(0xFF3B82F6).withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Text(
                        'EFD',
                        style: TextStyle(
                          fontSize: 9,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF3B82F6),
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
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
              ],
            ),
            const SizedBox(height: 10),
            Wrap(
              spacing: 12,
              runSpacing: 8,
              children: [
                _InfoCell(
                  label: 'Date',
                  value: receipt['receipt_date'] as String? ?? '-',
                  isDark: isDark,
                ),
                _InfoCell(
                  label: 'Receipt #',
                  value: receipt['receipt_number'] as String? ?? '-',
                  isDark: isDark,
                ),
                _InfoCell(
                  label: 'Items',
                  value: '${items.length}',
                  isDark: isDark,
                ),
              ],
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 12,
              runSpacing: 8,
              children: [
                _MoneyCell(
                  label: 'Total',
                  value: vatMoney(receipt['total_amount']),
                  isDark: isDark,
                ),
                _MoneyCell(
                  label: 'VAT Exc.',
                  value: vatMoney(receipt['amount_vat_exc']),
                  isDark: isDark,
                ),
                _MoneyCell(
                  label: 'VAT',
                  value: vatMoney(receipt['vat_amount']),
                  isDark: isDark,
                ),
                _MoneyCell(
                  label: isSwahili ? 'Punguzo' : 'Discount',
                  value: vatMoney(receipt['discount']),
                  isDark: isDark,
                ),
              ],
            ),
            if (vrn.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                'VRN: $vrn',
                style: TextStyle(
                  fontSize: 10,
                  color: isDark ? Colors.white38 : AppColors.textHint,
                ),
              ),
            ],
            if (verificationCode.isNotEmpty) ...[
              const SizedBox(height: 4),
              Row(
                children: [
                  const Icon(
                    Icons.verified_rounded,
                    size: 12,
                    color: Color(0xFF10B981),
                  ),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      'Verified: $verificationCode',
                      style: const TextStyle(
                        fontSize: 10,
                        color: Color(0xFF10B981),
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                ],
              ),
            ],
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: BoxDecoration(
                color: (receipt['is_expense'] == 'YES'
                        ? Colors.orange
                        : Colors.green)
                    .withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(4),
              ),
              child: Text(
                receipt['is_expense'] == 'YES'
                    ? (isSwahili ? 'Gharama' : 'Expense')
                    : (isSwahili ? 'Sio Gharama' : 'Not Expense'),
                style: TextStyle(
                  fontSize: 9,
                  fontWeight: FontWeight.w600,
                  color: receipt['is_expense'] == 'YES'
                      ? Colors.orange
                      : Colors.green,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ItemEditorCard extends StatelessWidget {
  final int index;
  final bool isDark;
  final bool isSwahili;
  final TextEditingController descriptionCtrl;
  final TextEditingController qtyCtrl;
  final TextEditingController amountCtrl;
  final bool canRemove;
  final VoidCallback onRemove;

  const _ItemEditorCard({
    required this.index,
    required this.isDark,
    required this.isSwahili,
    required this.descriptionCtrl,
    required this.qtyCtrl,
    required this.amountCtrl,
    required this.canRemove,
    required this.onRemove,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color:
            isDark ? const Color(0xFF0F1923) : Colors.grey.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isDark ? vatDarkBorder : Colors.grey.withValues(alpha: 0.15),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  '${isSwahili ? 'Bidhaa' : 'Item'} ${index + 1}',
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w700,
                    color: isDark ? Colors.white : AppColors.textPrimary,
                  ),
                ),
              ),
              if (canRemove)
                IconButton(
                  onPressed: onRemove,
                  icon: const Icon(Icons.delete_outline_rounded),
                  color: Colors.red.withValues(alpha: 0.8),
                  visualDensity: VisualDensity.compact,
                ),
            ],
          ),
          vatTextField(
            controller: descriptionCtrl,
            label: isSwahili ? 'Maelezo ya Bidhaa' : 'Description',
            isDark: isDark,
          ),
          Row(
            children: [
              Expanded(
                child: vatTextField(
                  controller: qtyCtrl,
                  label: isSwahili ? 'Idadi' : 'Qty',
                  isDark: isDark,
                  keyboardType: TextInputType.number,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: vatTextField(
                  controller: amountCtrl,
                  label: isSwahili ? 'Kiasi' : 'Amount',
                  isDark: isDark,
                  keyboardType: TextInputType.number,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _InfoCell extends StatelessWidget {
  final String label;
  final String value;
  final bool isDark;

  const _InfoCell({
    required this.label,
    required this.value,
    required this.isDark,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 92,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 9,
              color: isDark ? Colors.white38 : AppColors.textHint,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            value,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w500,
              color: isDark ? Colors.white70 : AppColors.textSecondary,
            ),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}

class _MoneyCell extends StatelessWidget {
  final String label;
  final String value;
  final bool isDark;

  const _MoneyCell({
    required this.label,
    required this.value,
    required this.isDark,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 92,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: TextStyle(
              fontSize: 9,
              color: isDark ? Colors.white38 : AppColors.textHint,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            value,
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: isDark ? vatAccentTeal : vatAccentBlue,
            ),
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}
